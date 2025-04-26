<?php

namespace App\Filament\Resources\InterestResource\Pages;

use App\Filament\Resources\InterestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInterests extends ListRecords
{
    protected static string $resource = InterestResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
