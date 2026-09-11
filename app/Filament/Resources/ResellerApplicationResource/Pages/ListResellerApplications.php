<?php

namespace App\Filament\Resources\ResellerApplicationResource\Pages;

use App\Filament\Resources\ResellerApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListResellerApplications extends ListRecords
{
    protected static string $resource = ResellerApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
