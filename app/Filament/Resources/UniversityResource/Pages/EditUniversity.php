<?php

namespace App\Filament\Resources\UniversityResource\Pages;

use App\Filament\Resources\UniversityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUniversity extends EditRecord
{
    protected static string $resource = UniversityResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
