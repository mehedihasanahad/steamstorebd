<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardCategoryResource\Pages;
use App\Models\GiftCardCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GiftCardCategoryResource extends Resource
{
    protected static ?string $model = GiftCardCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),
            Forms\Components\Textarea::make('description')->rows(3),
            Forms\Components\TextInput::make('icon')->placeholder('heroicon-o-tag'),
            Forms\Components\FileUpload::make('image')
                ->label('Category Image (1057×1488px)')
                ->image()
                ->disk('public')
                ->directory('images/categories')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\TextColumn::make('giftCards.id')->label('Cards')->counts('giftCards'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftCardCategories::route('/'),
            'create' => Pages\CreateGiftCardCategory::route('/create'),
            'edit' => Pages\EditGiftCardCategory::route('/{record}/edit'),
        ];
    }
}
