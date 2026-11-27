<?php

namespace App\Http\Controllers\Memories;

use App\Ai\Agents\SessionMemorySummarizer;
use App\Http\Controllers\Controller;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StoreMemoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $memory = $request->validate(Memory::sessionMetadataRules() + [
            'summary' => ['required_without:transcript', 'prohibits:transcript', 'string'],
            'transcript' => ['required_without:summary', 'string', 'max:24000'],
        ]);

        $summary = $memory['summary'] ?? '';
        if (isset($memory['transcript'])) {
            $summary = trim((string) SessionMemorySummarizer::make()->prompt($memory['transcript'], timeout: 30));
            abort_if($summary === '', 502, 'The summarizer returned no summary.');
        }

        Memory::saveSessionSummary($request->user()->getKey(), $memory['device'], $memory['source'], $memory['session_id'], $summary);

        return response()->noContent();
    }
}
