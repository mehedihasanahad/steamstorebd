<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellerApplicationResource\Pages;
use App\Jobs\SendResellerApplicationApprovedEmail;
use App\Jobs\SendResellerApplicationDeclinedEmail;
use App\Models\ResellerApplication;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ResellerApplicationResource extends Resource
{
    protected static ?string $model = ResellerApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Resellers';

    protected static ?string $navigationLabel = 'Applications';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Applicant')
                ->schema([
                    TextEntry::make('application_number')->label('Application ID')->copyable(),
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'declined' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('phone')->label('Phone')->copyable(),
                    TextEntry::make('whatsapp_number')->label('WhatsApp')->copyable(),
                ])->columns(2),

            Section::make('Business')
                ->schema([
                    TextEntry::make('selling_platform')
                        ->label('Selling Platform')
                        ->formatStateUsing(fn ($state, ResellerApplication $record) => $record->platformLabel()),
                    TextEntry::make('gift_card_types')
                        ->label('Wants To Sell')
                        ->formatStateUsing(fn ($state, ResellerApplication $record) => $record->giftCardTypesLabel()),
                    TextEntry::make('created_at')->label('Submitted')->dateTime(),
                    TextEntry::make('ip_address')->label('IP Address')->placeholder('—'),
                ])->columns(2),

            Section::make('Review')
                ->schema([
                    TextEntry::make('reviewer.name')->label('Reviewed By')->placeholder('—'),
                    TextEntry::make('reviewed_at')->label('Reviewed At')->dateTime()->placeholder('—'),
                    TextEntry::make('decline_reason')
                        ->label('Decline Reason')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->visible(fn (ResellerApplication $record) => ! $record->isPending()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_number')
                    ->label('Application ID')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono')
                    ->size('sm'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Applicant')
                    ->searchable()
                    ->description(fn (ResellerApplication $r) => $r->email),
                Tables\Columns\TextColumn::make('whatsapp_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->description(fn (ResellerApplication $r) => $r->phone !== $r->whatsapp_number ? $r->phone : null),
                Tables\Columns\TextColumn::make('selling_platform')
                    ->label('Platform')
                    ->formatStateUsing(fn ($state, ResellerApplication $record) => $record->platformLabel())
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('gift_card_types')
                    ->label('Wants To Sell')
                    ->formatStateUsing(fn ($state, ResellerApplication $record) => $record->giftCardTypesLabel())
                    ->wrap()
                    ->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'declined' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'declined' => 'Declined',
                    ]),
                Tables\Filters\SelectFilter::make('selling_platform')
                    ->label('Platform')
                    ->options(ResellerApplication::PLATFORMS),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (ResellerApplication $record) => $record->whatsappLink())
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ResellerApplication $r) => $r->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Approve Reseller Application')
                    ->modalDescription(fn (ResellerApplication $r) => "Approve {$r->name} as a reseller? They will receive a congratulations email straight away.")
                    ->action(function (ResellerApplication $record) {
                        $record->update([
                            'status' => 'approved',
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        dispatch(new SendResellerApplicationApprovedEmail($record));

                        Notification::make()
                            ->title('Application approved — congratulations email sent')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('decline')
                    ->label('Decline')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ResellerApplication $r) => $r->isPending())
                    ->form([
                        Textarea::make('decline_reason')
                            ->label('Reason for declining')
                            ->helperText('This is sent to the applicant, so keep it clear and respectful.')
                            ->required()
                            ->minLength(10)
                            ->maxLength(1000)
                            ->rows(4),
                    ])
                    ->modalHeading('Decline Reseller Application')
                    ->modalDescription('The applicant will be emailed the reason you give below.')
                    ->modalSubmitActionLabel('Decline & Send Email')
                    ->action(function (ResellerApplication $record, array $data) {
                        $record->update([
                            'status' => 'declined',
                            'decline_reason' => $data['decline_reason'],
                            'reviewed_by' => Auth::id(),
                            'reviewed_at' => now(),
                        ]);

                        dispatch(new SendResellerApplicationDeclinedEmail($record));

                        Notification::make()
                            ->title('Application declined — email sent with your reason')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListResellerApplications::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
