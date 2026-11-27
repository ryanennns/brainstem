<?php

namespace App\Http\Controllers\Memories;

use App\Ai\Agents\SessionMemorySummarizer;
use App\Http\Controllers\Controller;
use App\Models\Memory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class StoreMemoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $memory = $request->validate([
            'device' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', Rule::in(['claude_code', 'codex', 'opencode', 'ignition'])],
            'session_id' => ['required', 'string', 'max:255'],
            'summary' => ['required_without:transcript', 'prohibits:transcript', 'string'],
            'transcript' => ['required_without:summary', 'string', 'max:24000'],
        ]);
        $memory['user_id'] = $request->user()->getKey();

        if (isset($memory['transcript'])) {
            $memory['summary'] = trim((string) SessionMemorySummarizer::make()->prompt($memory['transcript'], timeout: 30));
            unset($memory['transcript']);
            abort_if($memory['summary'] === '', 502, 'The summarizer returned no summary.');
        }

        Memory::query()->upsert([$memory], ['user_id', 'source', 'session_id'], ['device', 'summary']);

        return response()->noContent();
    }
}
