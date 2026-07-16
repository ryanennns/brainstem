<?php

namespace App\Livewire;

use App\Ai\Agents\ProjectSummarizer;
use App\Models\Project;
use App\Models\ProjectUpdate;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Throwable;

class ProjectSummary extends Component
{
    public Project $record;

    public function render(): View
    {
        return view('livewire.project-summary', [
            'summary' => $this->summary(),
        ]);
    }

    public function placeholder(): string
    {
        return <<<'HTML'
            <div class="fi-in-entry" role="status">
                <div class="fi-in-entry-label">AI summary</div>
                <div class="fi-in-text-item">Generating summary…</div>
            </div>
            HTML;
    }

    private function summary(): string
    {
        $updateCount = ProjectUpdate::query()
            ->where('project_id', $this->record->getKey())
            ->count();

        if ($updateCount === 0) {
            return 'No project updates yet.';
        }

        try {
            return Cache::rememberForever(
                "project-summary:{$this->record->getKey()}:{$updateCount}",
                fn (): string => (string) ProjectSummarizer::make()->prompt($this->summaryPrompt()),
            );
        } catch (Throwable $exception) {
            report($exception);

            return 'AI summary unavailable.';
        }
    }

    private function summaryPrompt(): string
    {
        $updates = ProjectUpdate::query()
            ->where('project_id', $this->record->getKey())
            ->oldest()
            ->get(['type', 'summary', 'created_at'])
            ->map(fn (ProjectUpdate $update): array => [
                'type' => $update->type,
                'summary' => $update->summary,
                'created_at' => $update->created_at->toIso8601String(),
            ]);

        return json_encode([
            'title' => $this->record->name,
            'description' => $this->record->description,
            'updates' => $updates,
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
