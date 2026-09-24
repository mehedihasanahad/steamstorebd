<?php

namespace App\Filament\Resources\EmailCampaignResource\RelationManagers;

use App\Jobs\SendCampaignEmail;
use App\Models\EmailCampaign;
use App\Models\EmailCampaignRecipient;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Who the campaign was actually written to, and what happened to each of them.
 *
 * This is the reason recipients are rows rather than a number: when somebody
 * asks whether a particular customer got the message, the answer is here, with
 * the provider's own error against the ones that did not.
 */
class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Recipients';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        // Nothing to show until the audience has been frozen.
        return $ownerRecord instanceof EmailCampaign && ! $ownerRecord->isEditable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Address')
                    ->searchable()
                    ->copyable()
                    ->description(fn (EmailCampaignRecipient $record) => $record->name),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        EmailCampaignRecipient::STATUS_SENT    => 'success',
                        EmailCampaignRecipient::STATUS_FAILED  => 'danger',
                        EmailCampaignRecipient::STATUS_SKIPPED => 'gray',
                        default                                => 'warning',
                    }),

                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Sent')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('error')
                    ->label('Error')
                    ->placeholder('—')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn (EmailCampaignRecipient $record) => $record->error),
            ])
            ->defaultSort('id')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    EmailCampaignRecipient::STATUS_PENDING => 'Pending',
                    EmailCampaignRecipient::STATUS_SENT    => 'Sent',
                    EmailCampaignRecipient::STATUS_FAILED  => 'Failed',
                    EmailCampaignRecipient::STATUS_SKIPPED => 'Skipped',
                ]),
            ])
            ->actions([
                Tables\Actions\Action::make('resend')
                    ->label('Send again')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (EmailCampaignRecipient $record) => $record->status === EmailCampaignRecipient::STATUS_FAILED)
                    ->requiresConfirmation()
                    ->action(function (EmailCampaignRecipient $record) {
                        $record->update([
                            'status' => EmailCampaignRecipient::STATUS_PENDING,
                            'error'  => null,
                        ]);

                        // The campaign has to be open again, or the job will
                        // find it finished and quietly do nothing.
                        $record->campaign?->forceFill([
                            'status'       => EmailCampaign::STATUS_SENDING,
                            'completed_at' => null,
                            'failed_count' => max(0, ($record->campaign->failed_count ?? 1) - 1),
                        ])->save();

                        SendCampaignEmail::dispatch($record);

                        Notification::make()->title('Requeued')->success()->send();
                    }),
            ])
            ->paginated([25, 50, 100]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
