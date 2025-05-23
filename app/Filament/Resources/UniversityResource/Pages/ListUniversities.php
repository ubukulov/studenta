<?php

namespace App\Filament\Resources\UniversityResource\Pages;

use App\Filament\Resources\UniversityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUniversities extends ListRecords
{
    protected static string $resource = UniversityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
