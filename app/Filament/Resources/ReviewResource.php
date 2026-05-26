<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Reviews';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Reviewer')->schema([
                Forms\Components\TextInput::make('reviewer_name')
                    ->label('Display Name')
                    ->required()
                    ->maxLength(100),
                Forms\Components\Select::make('platform')
                    ->options([
                        'website'  => '🌐 Website',
                        'whatsapp' => '📱 WhatsApp',
                        'messenger'=> '💬 Messenger',
                        'steam'    => '🎮 Steam',
                    ])
                    ->default('website')
                    ->required(),
                Forms\Components\Select::make('rating')
                    ->options([
                        5 => '⭐⭐⭐⭐⭐ — 5 stars',
                        4 => '⭐⭐⭐⭐ — 4 stars',
                        3 => '⭐⭐⭐ — 3 stars',
                        2 => '⭐⭐ — 2 stars',
                        1 => '⭐ — 1 star',
                    ])
                    ->default(5)
                    ->required(),
            ])->columns(3),

            Forms\Components\Section::make('Review Content')->schema([
                Forms\Components\Textarea::make('comment')
                    ->label('Comment')
                    ->required()
                    ->rows(4)
                    ->maxLength(1000),
                Forms\Components\FileUpload::make('screenshot_path')
                    ->label('Screenshot (WhatsApp / Messenger / Steam proof)')
                    ->image()
                    ->disk('public')
                    ->directory('reviews')
                    ->maxSize(5120)
                    ->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Settings')->schema([
                Forms\Components\Select::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->default('approved')
                    ->required(),
                Forms\Components\Toggle::make('is_verified_purchase')
                    ->label('Verified Purchase')
                    ->default(false),
                Forms\Components\TextInput::make('sort_order')
                    ->label('Sort Order')
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower = shown first'),
                Forms\Components\Hidden::make('source')->default('admin'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reviewer_name')
                    ->label('Reviewer')
                    ->searchable()
                    ->formatStateUsing(fn($state, Review $record) => $state ?? $record->displayName()),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn($state) => str_repeat('⭐', (int) $state)),
                Tables\Columns\TextColumn::make('comment')
                    ->limit(55)
                    ->searchable(),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'whatsapp'  => 'success',
                        'messenger' => 'info',
                        'steam'     => 'warning',
                        default     => 'gray',
                    })
                    ->formatStateUsing(fn(Review $record) => $record->platformLabel()),
                Tables\Columns\ImageColumn::make('screenshot_path')
                    ->label('Screenshot')
                    ->disk('public')
                    ->height(40),
                Tables\Columns\IconColumn::make('is_verified_purchase')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->color(fn($state) => $state === 'admin' ? 'primary' : 'gray'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'  => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'website'  => 'Website',
                        'whatsapp' => 'WhatsApp',
                        'messenger'=> 'Messenger',
                        'steam'    => 'Steam',
                    ]),
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'customer' => 'Customer Submitted',
                        'admin'    => 'Admin Added',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Review $record) => $record->status !== 'approved')
                    ->action(function (Review $record) {
                        $record->update(['status' => 'approved']);
                        Notification::make()->title('Review approved and now visible on site')->success()->send();
                    }),

                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Review $record) => $record->status !== 'rejected')
                    ->requiresConfirmation()
                    ->action(function (Review $record) {
                        $record->update(['status' => 'rejected']);
                        Notification::make()->title('Review rejected')->warning()->send();
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('approve_selected')
                    ->label('Approve Selected')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn($records) => $records->each->update(['status' => 'approved']))
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit'   => Pages\EditReview::route('/{record}/edit'),
        ];
    }
}
