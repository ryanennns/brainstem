<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Filament\Resources\ProjectUpdates\ProjectUpdateResource;
use Filament\Resources\RelationManagers\RelationManager;

class UpdatesRelationManager extends RelationManager
{
    protected static string $relationship = 'updates';

    protected static ?string $relatedResource = ProjectUpdateResource::class;
}
