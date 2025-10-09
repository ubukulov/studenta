<?php

namespace App\Filament\Resources\GroupReviewReportResource\Pages;

use App\Filament\Resources\GroupReviewReportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupReviewReports extends ListRecords
{
    protected static string $resource = GroupReviewReportResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
