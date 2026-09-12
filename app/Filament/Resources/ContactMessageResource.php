<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContactMessageResource\Pages;
use App\Models\ContactMessage;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactMessageResource extends Resource
{
    protected static ?string $model = ContactMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?string $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Contact Messages';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $unread = static::getModel()::unread()->count();

        return $unread > 0 ? (string) $unread : null;
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
            Section::make('Sender')
                ->schema([
                    TextEntry::make('name')->label('Name'),
                    TextEntry::make('email')->label('Email')->copyable(),
                    TextEntry::make('created_at')->label('Received')->dateTime(),
                    TextEntry::make('ip_address')->label('IP Address')->placeholder('—'),
                ])->columns(2),

            Section::make('Message')
                ->schema([
                    TextEntry::make('message')->label('')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->getStateUsing(fn (ContactMessage $record) => $record->isRead()),
                Tables\Columns\TextColumn::make('name')
                    ->label('From')
                    ->searchable()
                    ->description(fn (ContactMessage $record) => $record->email),
                Tables\Columns\TextColumn::make('message')
                    ->searchable()
                    ->limit(80)
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('read_at')
                    ->label('Read')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('reply')
                    ->label('Reply')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->url(fn (ContactMessage $record) => 'mailto:'.$record->email)
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('markRead')
                    ->label('Mark read')
                    ->icon('heroicon-o-check')
                    ->visible(fn (ContactMessage $record) => ! $record->isRead())
                    ->action(fn (ContactMessage $record) => $record->update(['read_at' => now()])),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactMessages::route('/'),
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
