<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use App\Rules\ImageAspectRatio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Slides for the homepage hero carousel.
 */
class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Hero Slider';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Slide')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->helperText('For your own reference in this list. Not shown on the slide.'),
                    Forms\Components\TextInput::make('link_url')
                        ->label('Links to')
                        ->url()
                        ->maxLength(255)
                        ->placeholder('https://steamstorebd.com/category/gift-cards')
                        ->helperText('Leave blank to make the slide unclickable.'),
                    Forms\Components\FileUpload::make('image')
                        ->label('Desktop image')
                        ->image()
                        ->required()
                        ->disk('public')
                        ->directory('images/banners')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->imageEditor()
                        ->imageEditorAspectRatios(['16:5'])
                        ->rules([new ImageAspectRatio(16, 5, 1600)])
                        ->helperText('16:5 — 1600×500 or larger. Wrong shape? Upload it anyway and crop with the pencil.'),
                    Forms\Components\FileUpload::make('mobile_image')
                        ->label('Mobile image')
                        ->image()
                        ->disk('public')
                        ->directory('images/banners')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->imageEditor()
                        ->imageEditorAspectRatios(['4:3'])
                        ->rules([new ImageAspectRatio(4, 3, 800)])
                        ->helperText('4:3 — 800×600 or larger. Optional; without it a phone shows the desktop slide uncropped.'),
                    Forms\Components\TextInput::make('alt_text')
                        ->label('Alt text')
                        ->maxLength(255)
                        ->helperText('Describes the slide for screen readers and when the image fails to load.')
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('Scheduling')
                ->description('Leave both dates empty to run the slide until you switch it off.')
                ->schema([
                    Forms\Components\Toggle::make('is_active')->default(true),
                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Lower numbers appear first.'),
                    Forms\Components\DateTimePicker::make('starts_at')
                        ->label('Starts')
                        ->seconds(false),
                    Forms\Components\DateTimePicker::make('ends_at')
                        ->label('Ends')
                        ->seconds(false)
                        ->after('starts_at'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->disk('public')
                    ->height(48)
                    ->width(120),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('link_url')
                    ->label('Links to')
                    ->limit(40)
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Live')
                    ->boolean()
                    ->getStateUsing(fn (Banner $record) => $record->isVisible()),
                Tables\Columns\TextColumn::make('starts_at')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('ends_at')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
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
            'index'  => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit'   => Pages\EditBanner::route('/{record}/edit'),
        ];
    }
}
