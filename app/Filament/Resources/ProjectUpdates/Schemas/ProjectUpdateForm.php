<?php

namespace App\Filament\Resources\ProjectUpdates\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProjectUpdateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options([
                        'code_change' => 'Code change',
                        'plan_updated' => 'Plan updated',
                        'miscellaneous' => 'Miscellaneous',
                    ])
                    ->required(),
                Textarea::make('summary')
                    ->required()
                    ->columnSpanFull(),
                Select::make('project_id')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]);
    }
}
use Filament\Forms\Components\Select;
