<?php

namespace App\Filament\Resources\ProjectUpdates\Pages;

use App\Filament\Resources\ProjectUpdates\ProjectUpdateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectUpdate extends ViewRecord
{
    protected static string $resource = ProjectUpdateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
