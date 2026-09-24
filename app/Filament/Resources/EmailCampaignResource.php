<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailCampaignResource\Pages;
use App\Filament\Resources\EmailCampaignResource\RelationManagers\RecipientsRelationManager;
use App\Models\EmailCampaign;
use App\Services\CampaignAudience;
use App\Services\CampaignSender;
use App\Support\Campaigns\AudienceField;
use App\Support\Campaigns\AudienceSchema;
use App\Support\Campaigns\MergeTags;
use Filament\Actions;
use Filament\Actions\MountableAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class EmailCampaignResource extends Resource
{
    protected static ?string $model = EmailCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?string $navigationLabel = 'Email Campaigns';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Message')
                ->description('What people will actually receive.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Campaign name')
                        ->helperText('Internal only — nobody receiving this will see it.')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\TextInput::make('subject')
                        ->label('Subject line')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('preheader')
                        ->label('Preview text')
                        ->helperText('The line the inbox shows under the subject. Left empty, the client picks the first words of the message.')
                        ->maxLength(150),

                    Forms\Components\RichEditor::make('body')
                        ->label('Body')
                        ->helperText(MergeTags::hint())
                        ->required()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike', 'link',
                            'h2', 'h3', 'bulletList', 'orderedList', 'blockquote', 'undo', 'redo',
                        ])
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('cta_label')
                        ->label('Button label')
                        ->placeholder('Shop the sale')
                        ->maxLength(60),

                    Forms\Components\TextInput::make('cta_url')
                        ->label('Button link')
                        ->url()
                        ->placeholder('https://…')
                        ->requiredWith('cta_label')
                        ->maxLength(300),
                ])
                ->columns(2),

            Forms\Components\Section::make('Audience')
                ->description('Who it goes to. People who have unsubscribed are always left out.')
                ->schema([
                    Forms\Components\Select::make('audience')
                        ->label('Send to')
                        ->options(AudienceSchema::labels())
                        ->default(EmailCampaign::AUDIENCE_BUYERS)
                        ->required()
                        ->live()
                        // The conditions below are written against one
                        // audience's fields and mean nothing against another.
                        ->afterStateUpdated(fn (Set $set) => $set('filters', null))
                        ->selectablePlaceholder(false)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('manual_recipients')
                        ->label('Addresses')
                        ->helperText('One per line, or separated by commas. "Name <address>" is understood too.')
                        ->rows(6)
                        ->required()
                        ->visible(fn (Get $get) => $get('audience') === EmailCampaign::AUDIENCE_MANUAL)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('filters.match')
                        ->label('Recipients must match')
                        ->options(['all' => 'ALL of the conditions', 'any' => 'ANY of the conditions'])
                        ->default('all')
                        ->selectablePlaceholder(false)
                        ->visible(fn (Get $get) => $get('audience') !== EmailCampaign::AUDIENCE_MANUAL),

                    Forms\Components\Builder::make('filters.rules')
                        ->label('Conditions')
                        ->helperText('No conditions means everyone in the audience.')
                        ->addActionLabel('Add a condition')
                        ->collapsible()
                        // The audience is read here and nowhere deeper. `$get`
                        // resolves against the component's own container, so
                        // asking for it from inside a block would look for
                        // `filters.rules.<uuid>.audience` and find nothing —
                        // which is an empty field list, not an error.
                        ->blocks(fn (Get $get) => self::filterBlocks(
                            AudienceSchema::for($get('audience') ?: EmailCampaign::AUDIENCE_BUYERS),
                        ))
                        ->visible(fn (Get $get) => $get('audience') !== EmailCampaign::AUDIENCE_MANUAL)
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('audience_count')
                        ->label('This currently reaches')
                        ->content(function (Get $get, ?EmailCampaign $record) {
                            $preview = new EmailCampaign([
                                'audience'          => $get('audience') ?? EmailCampaign::AUDIENCE_BUYERS,
                                'filters'           => $get('filters'),
                                'manual_recipients' => $get('manual_recipients'),
                            ]);

                            $count = app(CampaignAudience::class)->count($preview);

                            return $count === 1 ? '1 person' : number_format($count) . ' people';
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /**
     * The two kinds of row a condition list can hold: a plain condition, and a
     * group of them with its own any/all. Both are built from an audience that
     * has already been resolved, so nothing below has to go looking for it.
     */
    protected static function filterBlocks(AudienceSchema $schema): array
    {
        return [
            Forms\Components\Builder\Block::make('condition')
                ->label('Condition')
                ->icon('heroicon-o-funnel')
                ->schema(self::conditionSchema($schema))
                ->columns(3),

            Forms\Components\Builder\Block::make('group')
                ->label('Group')
                ->icon('heroicon-o-rectangle-group')
                ->schema([
                    Forms\Components\Select::make('match')
                        ->label('Within this group, match')
                        ->options(['any' => 'ANY of the conditions', 'all' => 'ALL of the conditions'])
                        ->default('any')
                        ->selectablePlaceholder(false),

                    Forms\Components\Builder::make('rules')
                        ->label('')
                        ->addActionLabel('Add a condition to the group')
                        ->blocks([
                            Forms\Components\Builder\Block::make('condition')
                                ->label('Condition')
                                ->schema(self::conditionSchema($schema))
                                ->columns(3),
                        ]),
                ]),
        ];
    }

    /**
     * One row of the query builder. The value input follows the chosen field,
     * because a date and a list of brands cannot share one box — and the old
     * value is cleared when the field changes, so a number cannot be left
     * behind under a date.
     */
    protected static function conditionSchema(AudienceSchema $schema): array
    {
        $fieldFor = fn (Get $get) => $schema->field((string) $get('field'));
        $typeIs   = fn (string $type) => fn (Get $get) => ($fieldFor($get)?->type) === $type;

        return [
            Forms\Components\Select::make('field')
                ->label('Field')
                ->options(collect($schema->fields())->map(fn (AudienceField $field) => $field->label)->all())
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                    $set('operator', null);
                    $set('value_number', null);
                    $set('value_date', null);
                    $set('value_days', null);
                    $set('value_select', null);
                    $set('value_boolean', null);
                }),

            Forms\Components\Select::make('operator')
                ->label('Is')
                ->options(fn (Get $get) => $fieldFor($get)?->operators() ?? [])
                ->required()
                ->live(),

            Forms\Components\TextInput::make('value_number')
                ->label(fn (Get $get) => $fieldFor($get)?->suffix
                    ? 'Amount (' . $fieldFor($get)->suffix . ')'
                    : 'Amount')
                ->numeric()
                ->visible($typeIs(AudienceField::TYPE_NUMBER)),

            Forms\Components\TextInput::make('value_days')
                ->label('Days')
                ->numeric()
                ->minValue(1)
                ->visible(fn (Get $get) => $typeIs(AudienceField::TYPE_DATE)($get)
                    && str_contains((string) $get('operator'), 'last_days')),

            Forms\Components\DatePicker::make('value_date')
                ->label('Date')
                ->visible(fn (Get $get) => $typeIs(AudienceField::TYPE_DATE)($get)
                    && in_array($get('operator'), ['before', 'after'], true)),

            Forms\Components\Select::make('value_select')
                ->label('Any of')
                ->multiple()
                ->options(fn (Get $get) => AudienceSchema::options($fieldFor($get)?->options ?? ''))
                ->visible($typeIs(AudienceField::TYPE_SELECT)),

            Forms\Components\Toggle::make('value_boolean')
                ->label('True')
                ->default(true)
                ->visible($typeIs(AudienceField::TYPE_BOOLEAN)),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Campaign')
                    ->searchable()
                    ->description(fn (EmailCampaign $record) => $record->subject)
                    ->wrap(),

                Tables\Columns\TextColumn::make('audience')
                    ->label('Audience')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (EmailCampaign $record) => $record->audienceLabel()),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        EmailCampaign::STATUS_DRAFT     => 'gray',
                        EmailCampaign::STATUS_SCHEDULED => 'info',
                        EmailCampaign::STATUS_SENDING   => 'warning',
                        EmailCampaign::STATUS_SENT      => 'success',
                        EmailCampaign::STATUS_CANCELLED => 'danger',
                        default                         => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_recipients')
                    ->label('Progress')
                    ->formatStateUsing(function (EmailCampaign $record) {
                        if ($record->isDraft() || $record->isScheduled()) {
                            return '—';
                        }

                        $done = $record->sent_count + $record->failed_count;

                        return number_format($done) . ' / ' . number_format($record->total_recipients)
                            . ' (' . $record->progress() . '%)';
                    }),

                Tables\Columns\TextColumn::make('failed_count')
                    ->label('Failed')
                    ->color(fn (EmailCampaign $record) => $record->failed_count > 0 ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('scheduled_for')
                    ->label('Scheduled')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Finished')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    EmailCampaign::STATUS_DRAFT     => 'Draft',
                    EmailCampaign::STATUS_SCHEDULED => 'Scheduled',
                    EmailCampaign::STATUS_SENDING   => 'Sending',
                    EmailCampaign::STATUS_SENT      => 'Sent',
                    EmailCampaign::STATUS_CANCELLED => 'Cancelled',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (EmailCampaign $record) => $record->isEditable()),

                Tables\Actions\ActionGroup::make(self::tableActions()),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn (EmailCampaign $record) => ! $record->isSending()),
            ]);
    }

    /**
     * What each action is and does, once.
     *
     * The same seven actions appear in the table row and in the header of the
     * campaign's own page, and Filament draws those from two different classes
     * — both of which extend MountableAction, which is the part worth leaning
     * on. Defining the behaviour twice is how the confirmation on "send now"
     * ends up on one of them and not the other.
     */
    protected static function actionDefinitions(): array
    {
        return [
            'sendTest' => [
                'label'  => 'Send me a test',
                'icon'   => 'heroicon-o-beaker',
                'color'  => 'gray',
                'handle' => function (EmailCampaign $record) {
                    app(CampaignSender::class)->test($record, auth()->user());

                    Notification::make()->title('Test sent to ' . auth()->user()->email)->success()->send();
                },
            ],

            'sendNow' => [
                'label'       => 'Send now',
                'icon'        => 'heroicon-o-paper-airplane',
                'color'       => 'success',
                'visible'     => fn (EmailCampaign $record) => $record->isEditable(),
                'confirm'     => true,
                'heading'     => 'Send this campaign?',
                'description' => fn (EmailCampaign $record) => 'This will e-mail '
                    . number_format(app(CampaignAudience::class)->count($record))
                    . ' people. It cannot be undone, though a send in progress can be stopped.',
                'submit'      => 'Send it',
                'handle'      => function (EmailCampaign $record) {
                    $total = app(CampaignSender::class)->send($record);

                    Notification::make()
                        ->title($total > 0 ? "Queued for {$total} recipients" : 'Nobody matched — nothing was sent')
                        ->success()
                        ->send();
                },
            ],

            'schedule' => [
                'label'   => 'Schedule',
                'icon'    => 'heroicon-o-clock',
                'color'   => 'info',
                'visible' => fn (EmailCampaign $record) => $record->isDraft(),
                'form'    => [
                    Forms\Components\DateTimePicker::make('scheduled_for')
                        ->label('Send at')
                        ->seconds(false)
                        ->minDate(now())
                        ->required()
                        ->helperText('The audience is worked out when it sends, not now.'),
                ],
                'handle' => function (EmailCampaign $record, array $data) {
                    app(CampaignSender::class)->schedule($record, Carbon::parse($data['scheduled_for']));

                    Notification::make()->title('Scheduled')->success()->send();
                },
            ],

            'unschedule' => [
                'label'   => 'Back to draft',
                'icon'    => 'heroicon-o-arrow-uturn-left',
                'color'   => 'gray',
                'visible' => fn (EmailCampaign $record) => $record->isScheduled(),
                'handle'  => function (EmailCampaign $record) {
                    app(CampaignSender::class)->unschedule($record);

                    Notification::make()->title('Unscheduled')->success()->send();
                },
            ],

            'cancel' => [
                'label'       => 'Stop sending',
                'icon'        => 'heroicon-o-hand-raised',
                'color'       => 'danger',
                'visible'     => fn (EmailCampaign $record) => $record->isSending(),
                'confirm'     => true,
                'description' => 'Messages already sent cannot be recalled. Everyone still waiting will be skipped.',
                'handle'      => function (EmailCampaign $record) {
                    app(CampaignSender::class)->cancel($record);

                    Notification::make()->title('Stopped')->success()->send();
                },
            ],

            'retryFailed' => [
                'label'   => 'Retry failures',
                'icon'    => 'heroicon-o-arrow-path',
                'color'   => 'warning',
                'visible' => fn (EmailCampaign $record) => $record->failed_count > 0,
                'confirm' => true,
                'handle'  => function (EmailCampaign $record) {
                    $count = app(CampaignSender::class)->retryFailed($record);

                    Notification::make()
                        ->title($count > 0 ? "Requeued {$count} failed recipients" : 'Nothing to retry')
                        ->success()
                        ->send();
                },
            ],

            'duplicate' => [
                'label'  => 'Duplicate as draft',
                'icon'   => 'heroicon-o-document-duplicate',
                'color'  => 'gray',
                'handle' => function (EmailCampaign $record) {
                    $copy = $record->replicate([
                        'status', 'scheduled_for', 'started_at', 'completed_at',
                        'total_recipients', 'sent_count', 'failed_count',
                    ]);

                    $copy->name   = $record->name . ' (copy)';
                    $copy->status = EmailCampaign::STATUS_DRAFT;
                    $copy->save();

                    Notification::make()->title('Copied to a new draft')->success()->send();
                },
            ],
        ];
    }

    /** @return array<Tables\Actions\Action> */
    public static function tableActions(): array
    {
        return collect(self::actionDefinitions())
            ->map(fn (array $definition, string $name) => self::configure(Tables\Actions\Action::make($name), $definition))
            ->values()
            ->all();
    }

    /** @return array<Actions\Action> */
    public static function pageActions(): array
    {
        return collect(self::actionDefinitions())
            ->map(fn (array $definition, string $name) => self::configure(Actions\Action::make($name), $definition))
            ->values()
            ->all();
    }

    private static function configure(MountableAction $action, array $definition): MountableAction
    {
        $action
            ->label($definition['label'])
            ->icon($definition['icon'])
            ->color($definition['color'])
            ->action($definition['handle']);

        if (isset($definition['visible'])) {
            $action->visible($definition['visible']);
        }

        if ($definition['confirm'] ?? false) {
            $action->requiresConfirmation();
        }

        if (isset($definition['heading'])) {
            $action->modalHeading($definition['heading']);
        }

        if (isset($definition['description'])) {
            $action->modalDescription($definition['description']);
        }

        if (isset($definition['submit'])) {
            $action->modalSubmitActionLabel($definition['submit']);
        }

        if (isset($definition['form'])) {
            $action->form($definition['form']);
        }

        return $action;
    }

    public static function getRelations(): array
    {
        return [RecipientsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEmailCampaigns::route('/'),
            'create' => Pages\CreateEmailCampaign::route('/create'),
            'edit'   => Pages\EditEmailCampaign::route('/{record}/edit'),
            'view'   => Pages\ViewEmailCampaign::route('/{record}'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof EmailCampaign && $record->isEditable();
    }

    public static function getNavigationBadge(): ?string
    {
        $sending = static::getModel()::where('status', EmailCampaign::STATUS_SENDING)->count();

        return $sending > 0 ? (string) $sending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
