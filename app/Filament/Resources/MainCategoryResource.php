<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MainCategoryResource\Pages;
use App\Models\MainCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class MainCategoryResource extends Resource
{
    protected static ?string $model = MainCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Brands / Main Categories';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Brand Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->rows(3),
                    Forms\Components\TextInput::make('icon')
                        ->placeholder('🎮  or  heroicon-o-tag'),
                    Forms\Components\FileUpload::make('image')
                        ->label('Brand Cover Image (1057×1488px portrait)')
                        ->image()
                        ->disk('public')
                        ->directory('images/main-categories')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true),
                ])->columns(2),

            Forms\Components\Section::make('How to Redeem')
                ->description('Step-by-step redemption instructions shown on the brand page and product pages')
                ->schema([
                    Forms\Components\RichEditor::make('how_to_redeem')
                        ->label('')
                        ->toolbarButtons(['bold', 'italic', 'h3', 'bulletList', 'orderedList', 'link'])
                        ->placeholder("Example:\n1. Open the app/website and sign in\n2. Go to Settings → Gift Card or Wallet\n3. Enter your code and tap Redeem\n4. Balance added instantly")
                        ->columnSpanFull(),
                ])->collapsible(),

            Forms\Components\Section::make('SEO')
                ->description('Search engine optimization for this brand page (/brand/{slug})')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Title')
                        ->placeholder('Buy Steam Gift Cards in Bangladesh | bKash — Steam Store BD')
                        ->maxLength(70),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('Meta Description')
                        ->rows(3)
                        ->maxLength(165),
                    Forms\Components\Textarea::make('seo_keywords')
                        ->label('Meta Keywords')
                        ->rows(3)
                        ->placeholder('steam gift card bd, buy steam gift card bangladesh, ...'),
                    Forms\Components\RichEditor::make('seo_content')
                        ->label('SEO Content')
                        ->toolbarButtons(['bold', 'italic', 'h2', 'h3', 'bulletList', 'orderedList', 'link'])
                        ->columnSpanFull(),
                ])->columns(2)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->height(48)
                    ->width(34),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\TextColumn::make('giftCardCategories_count')
                    ->label('Sub-categories')
                    ->counts('giftCardCategories'),
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
            'index'  => Pages\ListMainCategories::route('/'),
            'create' => Pages\CreateMainCategory::route('/create'),
            'edit'   => Pages\EditMainCategory::route('/{record}/edit'),
        ];
    }
}
