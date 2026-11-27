<?php

namespace App\Http\Controllers\Memories;

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
            'summary' => ['required', 'string'],
        ]);
        $memory['user_id'] = $request->user()->getKey();

        Memory::query()->upsert([$memory], ['user_id', 'source', 'session_id'], ['device', 'summary']);

        return response()->noContent();
    }
}
