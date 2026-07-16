<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Ai\Agents\ProjectSummarizer;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\ProjectUpdate;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\Facades\Cache;
use Throwable;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextEntry::make('name')
                    ->label('Title')
                    ->size(TextSize::Large)
                    ->weight(FontWeight::Bold),
                TextEntry::make('description')
                    ->placeholder('No description.'),
                TextEntry::make('ai_summary')
                    ->label('AI summary')
                    ->state(fn (Project $record): string => $this->summary($record))
                    ->markdown()
                    ->prose(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    private function summary(Project $project): string
    {
        $updateCount = ProjectUpdate::query()
            ->where('project_id', $project->getKey())
            ->count();

        if ($updateCount === 0) {
            return 'No project updates yet.';
        }

        try {
            return Cache::rememberForever(
                "project-summary:{$project->getKey()}:{$updateCount}",
                fn (): string => (string) ProjectSummarizer::make()->prompt($this->summaryPrompt($project)),
            );
        } catch (Throwable $exception) {
            report($exception);

            return 'AI summary unavailable.';
        }
    }

    private function summaryPrompt(Project $project): string
    {
        $updates = ProjectUpdate::query()
            ->where('project_id', $project->getKey())
            ->oldest()
            ->get(['type', 'summary', 'created_at'])
            ->map(fn (ProjectUpdate $update): array => [
                'type' => $update->type,
                'summary' => $update->summary,
                'created_at' => $update->created_at->toIso8601String(),
            ]);

        return json_encode([
            'title' => $project->name,
            'description' => $project->description,
            'updates' => $updates,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
