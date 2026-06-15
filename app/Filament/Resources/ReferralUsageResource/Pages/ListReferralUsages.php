<?php

namespace App\Filament\Resources\ReferralUsageResource\Pages;

use App\Filament\Resources\ReferralUsageResource;
use Filament\Resources\Pages\ListRecords;

class ListReferralUsages extends ListRecords
{
    protected static string $resource = ReferralUsageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
