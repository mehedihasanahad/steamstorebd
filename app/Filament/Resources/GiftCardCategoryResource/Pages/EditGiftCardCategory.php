<?php

namespace App\Filament\Resources\GiftCardCategoryResource\Pages;

use App\Filament\Resources\GiftCardCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGiftCardCategory extends EditRecord
{
    protected static string $resource = GiftCardCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
