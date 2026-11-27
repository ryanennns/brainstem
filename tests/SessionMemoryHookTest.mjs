import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import { hostname, tmpdir } from 'node:os';
import { join } from 'node:path';
import { text } from 'node:stream/consumers';
import { test } from 'node:test';
import { fileURLToPath } from 'node:url';

const hook = fileURLToPath(new URL('../resources/js/session-memory.mjs', import.meta.url));
const goal = 'Fix the failing build';
const outcome = 'The build is passing';

const transcripts = {
    claude_code: [
        { type: 'user', message: { content: goal } },
        { type: 'user', message: { content: [{ type: 'tool_result', content: 'ignored' }] } },
        { type: 'assistant', isSidechain: true, message: { content: 'ignored' } },
        { type: 'assistant', message: { content: [{ type: 'text', text: outcome }] } },
    ],
    codex: [
        { type: 'event_msg', payload: { type: 'user_message', message: goal } },
        { type: 'event_msg', payload: { type: 'agent_message', phase: 'commentary', message: 'ignored' } },
        { type: 'event_msg', payload: { type: 'agent_message', phase: 'final_answer', message: outcome } },
    ],
    'codex paginated': [
        { type: 'event_msg', payload: { type: 'item_completed', item: {
            type: 'UserMessage', content: [{ type: 'text', text: goal }],
        } } },
        { type: 'response_item', payload: { type: 'function_call_output', output: 'ignored' } },
        { type: 'event_msg', payload: { type: 'item_completed', item: {
            type: 'AgentMessage', content: [{ type: 'Text', text: outcome }],
        } } },
    ],
};

for (const fixture of [...Object.keys(transcripts), 'ignition']) {
    test(`${fixture} posts the session goal and outcome on completion`, { timeout: 5000 }, async (t) => {
        const source = fixture === 'codex paginated' ? 'codex' : fixture;
        const sessionId = `session_${source}`;
        const server = createServer();
        server.listen(0, '127.0.0.1');
        t.after(() => server.close());
        await once(server, 'listening');

        let input = { session_id: sessionId, messages: [
            { role: 'user', text: goal },
            { role: 'tool', text: 'ignored' },
            { role: 'assistant', text: outcome },
        ] };
        if (fixture in transcripts) {
            const directory = await mkdtemp(join(tmpdir(), 'brainstem-hook-'));
            t.after(() => rm(directory, { recursive: true }));
            const path = join(directory, 'transcript.jsonl');
            await writeFile(path, transcripts[fixture].map((record) => JSON.stringify(record)).join('\n'));
            input = { session_id: sessionId, transcript_path: path, hook_event_name: 'SessionEnd' };
        }

        const child = spawn(process.execPath, [hook, source], {
            env: {
                ...process.env,
                BRAINSTEM_URL: `http://127.0.0.1:${server.address().port}`,
                BRAINSTEM_API_TOKEN: 'test-token',
                BRAINSTEM_DEVICE: '',
            },
            stdio: ['pipe', 'ignore', 'inherit'],
        });
        const exited = once(child, 'exit');
        const received = once(server, 'request', { signal: AbortSignal.timeout(3000) });
        child.stdin.end(JSON.stringify(input));

        const [request, response] = await received;
        const body = JSON.parse(await text(request));
        response.writeHead(204);
        response.end();

        assert.equal(request.url, '/api/memories');
        assert.equal(request.headers.authorization, 'Bearer test-token');
        assert.deepEqual(body, {
            device: hostname(),
            source,
            session_id: sessionId,
            transcript: `First user message: ${goal}\n\nRecent messages:\nassistant: ${outcome}`,
        });
        assert.deepEqual(await exited, [0, null]);
    });
}
