<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class Memory extends Model
{
    use HasFactory;
    use HasUuids;

    public const array SOURCES = ['claude_code', 'codex', 'opencode', 'ignition'];

    protected $guarded = [];

    public static function sessionMetadataRules(): array
    {
        return [
            'device' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', Rule::in(self::SOURCES)],
            'session_id' => ['required', 'string', 'max:255'],
        ];
    }

    public static function saveSessionSummary(int $userId, string $device, string $source, string $sessionId, string $summary): void
    {
        self::query()->upsert([[
            'user_id' => $userId,
            'device' => $device,
            'source' => $source,
            'session_id' => $sessionId,
            'summary' => $summary,
        ]], ['user_id', 'source', 'session_id'], ['device', 'summary']);
    }
}
