<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GiftCardCategoryResource\Pages;
use App\Models\GiftCardCategory;
use App\Models\MainCategory;
use App\Support\Region;
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
            Forms\Components\Select::make('main_category_id')
                ->label('Brand / Main Category')
                ->options(MainCategory::orderBy('sort_order')->pluck('name', 'id'))
                ->searchable()
                ->nullable()
                ->placeholder('— Select brand —'),
            Forms\Components\TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn($state, Forms\Set $set) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Changing the slug is safe: the old /product URL redirects to the new one.'),
            Forms\Components\Select::make('region')
                ->label('Region')
                ->options(Region::options())
                ->searchable()
                ->nullable()
                ->placeholder('— No region —')
                ->helperText('Where this product can be redeemed. Shown as a flag on product cards.'),
            Forms\Components\TextInput::make('region_group')
                ->label('Region Group')
                ->maxLength(255)
                ->placeholder('steam-wallet')
                ->helperText("Products sharing this key appear in each other's region switcher. Leave blank if this product has no regional variants."),
            Forms\Components\Textarea::make('description')->rows(3),
            Forms\Components\RichEditor::make('long_description')
                ->label('Long Description (shown on product page)')
                ->toolbarButtons(['bold','italic','underline','bulletList','orderedList','link','h2','h3'])
                ->columnSpanFull(),
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
            Forms\Components\Toggle::make('is_featured')
                ->label('Featured')
                ->default(false)
                ->helperText('Show this product in the homepage Featured rail.'),
            Forms\Components\TextInput::make('featured_sort')
                ->numeric()
                ->default(0)
                ->label('Featured order')
                ->helperText('Lower numbers appear first in the Featured rail.'),

            Forms\Components\Section::make('Product Content')
                ->description('The Instructions and FAQ tabs on the product page.')
                ->schema([
                    Forms\Components\RichEditor::make('instructions')
                        ->label('Instructions')
                        ->helperText("Leave blank to inherit the brand's How to Redeem steps.")
                        ->toolbarButtons(['bold', 'italic', 'h3', 'bulletList', 'orderedList', 'link'])
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('faq')
                        ->label('FAQ')
                        ->schema([
                            Forms\Components\TextInput::make('question')->required()->maxLength(255),
                            Forms\Components\Textarea::make('answer')->required()->rows(3),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Add a question')
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['question'] ?? null)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull()
                ->collapsed(),

            Forms\Components\Section::make('Buyer Inputs')
                ->description('Details the buyer must supply before this product can be fulfilled — a Player ID for a top-up, an account e-mail for a subscription. Leave empty for gift cards.')
                ->schema([
                    Forms\Components\Repeater::make('buyer_input_fields')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('key')
                                ->required()
                                ->maxLength(40)
                                ->placeholder('player_id')
                                ->helperText('Lowercase, no spaces. Stored with the order.'),
                            Forms\Components\TextInput::make('label')
                                ->required()
                                ->maxLength(80)
                                ->placeholder('Player ID'),
                            Forms\Components\Select::make('type')
                                ->required()
                                ->default('text')
                                ->live()
                                ->options([
                                    'text'   => 'Text',
                                    'number' => 'Number',
                                    'email'  => 'Email',
                                    'select' => 'Dropdown',
                                ]),
                            Forms\Components\Toggle::make('required')
                                ->default(true),
                            Forms\Components\TextInput::make('help')
                                ->maxLength(160)
                                ->placeholder('Find it in-game under Profile, top-left')
                                ->columnSpanFull(),
                            Forms\Components\TagsInput::make('options')
                                ->helperText('The choices for the dropdown.')
                                ->visible(fn (Forms\Get $get) => $get('type') === 'select')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->defaultItems(0)
                        ->addActionLabel('Add a field')
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? null),
                ])
                ->columnSpanFull()
                ->collapsed(),

            Forms\Components\Section::make('SEO')
                ->description('Search engine settings for this product page (/product/{slug}). Leave empty to use the automatic title and description.')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Title')
                        ->placeholder('Buy Steam Wallet Gift Card in Bangladesh')
                        ->helperText('Aim for under 45 characters. " — Steam Store BD" is added automatically.')
                        ->maxLength(70),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('Meta Description')
                        ->helperText('Shown under the title in Google. Aim for 120–160 characters.')
                        ->rows(3)
                        ->maxLength(165),
                ])
                ->columnSpanFull()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('mainCategory.name')
                    ->label('Brand')
                    ->badge()
                    ->color('info')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('slug')->color('gray'),
                Tables\Columns\TextColumn::make('region')
                    ->label('Region')
                    ->formatStateUsing(fn (?string $state) => Region::label($state) ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('giftCards.id')->label('Cards')->counts('giftCards'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_featured')->label('Featured'),
                Tables\Filters\SelectFilter::make('region')->options(Region::options()),
                Tables\Filters\SelectFilter::make('main_category_id')
                    ->label('Brand')
                    ->options(fn () => MainCategory::orderBy('sort_order')->pluck('name', 'id')),
            ])
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
