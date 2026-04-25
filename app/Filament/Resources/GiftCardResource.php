<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardResource\Pages;
use App\Models\GiftCard;
use App\Models\GiftCardCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class GiftCardResource extends Resource
{
    protected static ?string $model = GiftCard::class;
    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(GiftCardCategory::where('is_active', true)->pluck('name', 'id'))
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('badge_text')->placeholder('Best Value'),
                Forms\Components\FileUpload::make('image')
                    ->label('Gift Card Image (1057×1488px)')
                    ->image()
                    ->disk('public')
                    ->directory('images/gift-cards')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
            ])->columns(2),

            Forms\Components\Section::make('Pricing')->schema([
                Forms\Components\TextInput::make('denomination_usd')->numeric()->required()->prefix('$'),
                Forms\Components\TextInput::make('denomination_bdt')->numeric()->required()->prefix('৳'),
                Forms\Components\TextInput::make('price_bdt')->numeric()->required()->prefix('৳'),
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
            ])->columns(2),

            Forms\Components\Section::make('Details')->schema([
                Forms\Components\Textarea::make('description')->rows(4)->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->badge(),
                Tables\Columns\TextColumn::make('denomination_usd')->money('USD')->prefix('$'),
                Tables\Columns\TextColumn::make('price_bdt')->formatStateUsing(fn($state) => format_bdt($state)),
                Tables\Columns\BadgeColumn::make('stock_count')
                    ->label('Stock')
                    ->color(fn($state) => $state > 5 ? 'success' : ($state > 0 ? 'warning' : 'danger')),
                Tables\Columns\ToggleColumn::make('is_active'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->options(GiftCardCategory::pluck('name', 'id'))
                    ->label('Category'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGiftCards::route('/'),
            'create' => Pages\CreateGiftCard::route('/create'),
            'edit' => Pages\EditGiftCard::route('/{record}/edit'),
        ];
    }
}
