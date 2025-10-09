<?php

namespace App\Filament\Resources\GroupReviewReportResource\Pages;

use App\Filament\Resources\GroupReviewReportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroupReviewReport extends EditRecord
{
    protected static string $resource = GroupReviewReportResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
