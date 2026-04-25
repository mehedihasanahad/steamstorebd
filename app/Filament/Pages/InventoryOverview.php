<?php

namespace App\Filament\Pages;

use App\Models\GiftCard;
use App\Models\GiftCardCode;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class InventoryOverview extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static string $view = 'filament.pages.inventory-overview';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?string $title = 'Inventory Overview';
    protected static ?int $navigationSort = 4;

    public function getViewData(): array
    {
        $cards = GiftCard::with('codes')->orderBy('sort_order')->get()->map(function ($card) {
            return [
                'id' => $card->id,
                'name' => $card->name,
                'total' => $card->codes->count(),
                'available' => $card->codes->where('status', 'available')->count(),
                'reserved' => $card->codes->where('status', 'reserved')->count(),
                'sold' => $card->codes->where('status', 'sold')->count(),
            ];
        });

        return compact('cards');
    }
}
