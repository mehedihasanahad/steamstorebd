<?php

namespace App\Filament\Resources\EmailCampaignResource\Pages;

use App\Filament\Resources\EmailCampaignResource;
use App\Models\EmailCampaign;
use App\Services\CampaignAudience;
use App\Support\Campaigns\MergeTags;
use App\Support\EmailRichText;
use App\Support\EmailTheme;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;

class ViewEmailCampaign extends ViewRecord
{
    protected static string $resource = EmailCampaignResource::class;

    /**
     * While a campaign is going out the numbers on this page change without
     * anybody touching it, so it refreshes itself. A finished campaign is
     * static and is left alone.
     */
    protected function getPollingInterval(): ?string
    {
        return $this->getRecord()->isSending() ? '5s' : null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->visible(fn (EmailCampaign $record) => $record->isEditable()),
            ...EmailCampaignResource::pageActions(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Status')
                ->schema([
                    TextEntry::make('status')
                        ->badge()
                        ->color(fn (string $state) => match ($state) {
                            EmailCampaign::STATUS_SENT      => 'success',
                            EmailCampaign::STATUS_SENDING   => 'warning',
                            EmailCampaign::STATUS_CANCELLED => 'danger',
                            EmailCampaign::STATUS_SCHEDULED => 'info',
                            default                         => 'gray',
                        }),

                    TextEntry::make('audience')
                        ->label('Audience')
                        ->formatStateUsing(fn (EmailCampaign $record) => $record->audienceLabel()),

                    TextEntry::make('reach')
                        ->label(fn (EmailCampaign $record) => $record->isEditable() ? 'Would reach' : 'Recipients')
                        ->state(function (EmailCampaign $record) {
                            $count = $record->isEditable()
                                ? app(CampaignAudience::class)->count($record)
                                : $record->total_recipients;

                            return number_format($count);
                        }),

                    TextEntry::make('sent_count')->label('Sent')->numeric(),

                    TextEntry::make('failed_count')
                        ->label('Failed')
                        ->numeric()
                        ->color(fn (EmailCampaign $record) => $record->failed_count > 0 ? 'danger' : 'gray'),

                    TextEntry::make('scheduled_for')->label('Scheduled for')->dateTime()->placeholder('—'),
                    TextEntry::make('started_at')->label('Started')->dateTime()->placeholder('—'),
                    TextEntry::make('completed_at')->label('Finished')->dateTime()->placeholder('—'),
                ])
                ->columns(4),

            Section::make('Preview')
                ->description('The message as a recipient sees it, with the placeholders filled in as an example.')
                ->schema([
                    TextEntry::make('subject')->label('Subject'),
                    TextEntry::make('preheader')->label('Preview text')->placeholder('—'),

                    // Shown on the campaign's own dark canvas: the body's
                    // colours are the e-mail's, and grey-on-white in the admin
                    // panel would not be what anybody receives.
                    TextEntry::make('body')
                        ->label('')
                        ->state(function (EmailCampaign $record) {
                            $c = EmailTheme::palette();

                            $rendered = EmailRichText::inline(
                                MergeTags::apply($record->body, 'Rahim Uddin', 'rahim@example.com'),
                            );

                            return new HtmlString(
                                '<div style="background-color:' . $c['card'] . '; border:1px solid ' . $c['border']
                                . '; border-radius:10px; padding:24px; max-width:600px;">' . $rendered . '</div>',
                            );
                        })
                        ->columnSpanFull(),

                    TextEntry::make('cta_label')
                        ->label('Button')
                        ->placeholder('—')
                        ->formatStateUsing(fn (EmailCampaign $record) => $record->cta_label
                            ? $record->cta_label . ' → ' . $record->cta_url
                            : '—'),
                ])
                ->columns(2),
        ]);
    }
}
