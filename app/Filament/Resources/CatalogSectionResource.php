<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogSectionResource\Pages;
use App\Models\CatalogSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * The top level of the catalog. Sits above Brands in the navigation because
 * that is where it sits in the hierarchy.
 */
class CatalogSectionResource extends Resource
{
    protected static ?string $model = CatalogSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = 'Sections';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Section Info')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Changing the slug is safe: the old /category URL redirects to the new one.'),
                    Forms\Components\TextInput::make('tagline')
                        ->maxLength(255)
                        ->placeholder('Steam, Google Play, App Store and more — delivered instantly')
                        ->helperText('One line shown under the section name on the storefront.')
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('icon')
                        ->placeholder('🎁  or  heroicon-o-gift')
                        ->helperText('An emoji or a heroicon name.'),
                    Forms\Components\ColorPicker::make('accent_color')
                        ->helperText('Used for the section tile and menu highlight.'),
                    Forms\Components\FileUpload::make('image')
                        ->label('Section Image')
                        ->image()
                        ->disk('public')
                        ->directory('images/catalog-sections')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                    Forms\Components\Radio::make('display_mode')
                        ->label('Homepage layout')
                        ->options(CatalogSection::DISPLAY_MODES)
                        ->default(CatalogSection::DISPLAY_SLIDER)
                        ->required()
                        ->in(array_keys(CatalogSection::DISPLAY_MODES))
                        ->helperText('How this section draws its brands on the homepage. A slider keeps the section one row tall however many brands it holds; a grid shows them all at once and grows the page instead. Cards are the same size either way, and the section page is unaffected.')
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->helperText('A hidden section disappears from the menu, homepage and sitemap.'),
                ])->columns(2),

            Forms\Components\Section::make('SEO')
                ->description('Search engine settings for this section page (/category/{slug}).')
                ->schema([
                    Forms\Components\TextInput::make('seo_title')
                        ->label('SEO Title')
                        ->placeholder('Buy Gift Cards in Bangladesh')
                        ->helperText('Aim for under 45 characters. " — Steam Store BD" is added automatically.')
                        ->maxLength(70),
                    Forms\Components\Textarea::make('seo_description')
                        ->label('Meta Description')
                        ->helperText('Shown under the title in Google. Aim for 120–160 characters.')
                        ->rows(3)
                        ->maxLength(165),
                    Forms\Components\RichEditor::make('seo_content')
                        ->label('SEO Content')
                        ->helperText('Rendered below the brand grid on the section page.')
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
                    ->height(40)
                    ->width(40)
                    ->circular(),
                Tables\Columns\TextColumn::make('icon')
                    ->label('')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Large),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('main_categories_count')
                    ->label('Brands')
                    ->counts('mainCategories')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable(),
                Tables\Columns\TextColumn::make('display_mode')
                    ->label('Homepage')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => ucfirst($state ?? CatalogSection::DISPLAY_SLIDER))
                    ->color(fn (?string $state) => $state === CatalogSection::DISPLAY_GRID ? 'warning' : 'gray'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCatalogSections::route('/'),
            'create' => Pages\CreateCatalogSection::route('/create'),
            'edit'   => Pages\EditCatalogSection::route('/{record}/edit'),
        ];
    }
}
