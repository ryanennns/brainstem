import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { createReadStream } from 'node:fs';
import { hostname } from 'node:os';
import { createInterface } from 'node:readline';
import { text } from 'node:stream/consumers';
import { fileURLToPath } from 'node:url';

const scriptPath = fileURLToPath(import.meta.url);

export function sessionMemoryIsConfigured() {
    return Boolean(process.env.BRAINSTEM_URL && process.env.BRAINSTEM_API_TOKEN);
}

export async function saveSessionMemory(source, sessionId, messages, executionStatus) {
    if (typeof sessionId !== 'string' || !sessionId.trim()) {
        throw new Error('The session ID is missing.');
    }

    let firstPrompt = '';
    let recentMessages = '';
    for await (const message of messages) {
        if (!['user', 'assistant'].includes(message?.role) || typeof message.text !== 'string' || !message.text.trim()) {
            continue;
        }

        if (!firstPrompt && message.role === 'user') {
            firstPrompt = message.text.trim().slice(0, 3000);
            continue;
        }

        recentMessages = `${recentMessages}\n${message.role}: ${message.text.trim()}`.slice(-20000);
    }

    if (!firstPrompt) {
        return;
    }

    const status = executionStatus ? `\n\nAgent execution status: ${executionStatus}` : '';
    const response = await fetch(new URL('/api/memories', process.env.BRAINSTEM_URL), {
        method: 'POST',
        redirect: 'error',
        signal: AbortSignal.timeout(45000),
        headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${process.env.BRAINSTEM_API_TOKEN}`,
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            device: process.env.BRAINSTEM_DEVICE || hostname(),
            source,
            session_id: sessionId,
            transcript: `First user message: ${firstPrompt}\n\nRecent messages:${recentMessages}${status}`,
        }),
    });

    if (!response.ok) {
        throw new Error(`Brainstem returned HTTP ${response.status}.`);
    }
}

function messageFromCodex(record) {
    if (record?.type !== 'event_msg') {
        return;
    }

    const event = record.payload;
    if (event?.type === 'user_message') {
        return { role: 'user', text: event.message };
    }
    if (event?.type === 'agent_message' && event.phase !== 'commentary') {
        return { role: 'assistant', text: event.message };
    }
    if (event?.type !== 'item_completed') {
        return;
    }

    const item = event.item;
    if (item?.type === 'UserMessage' || (item?.type === 'AgentMessage' && item.phase !== 'commentary')) {
        return {
            role: item.type === 'UserMessage' ? 'user' : 'assistant',
            text: textBlocks(item.content, ['text', 'Text']),
        };
    }
}

function textBlocks(content, types) {
    if (typeof content === 'string') {
        return content;
    }

    if (!Array.isArray(content)) {
        return '';
    }

    return content.filter((part) => types.includes(part?.type)).map((part) => part.text).join('\n');
}

function messageFromClaude(record) {
    if (!['user', 'assistant'].includes(record?.type) || record.isSidechain || record.isMeta) {
        return;
    }

    return { role: record.type, text: textBlocks(record.message?.content, ['text']) };
}

async function* readTranscript(path, readMessage) {
    const lines = createInterface({ input: createReadStream(path), crlfDelay: Infinity });
    for await (const line of lines) {
        let record;
        try {
            record = JSON.parse(line);
        } catch {
            continue;
        }

        yield readMessage(record);
    }
}

async function runHook(source, background) {
    if (!sessionMemoryIsConfigured()) {
        return;
    }

    const input = JSON.parse(await text(process.stdin));
    if (source === 'ignition') {
        await saveSessionMemory(source, input.session_id, input.messages);

        return;
    }

    if (!['codex', 'claude_code'].includes(source) || input.hook_event_name !== 'SessionEnd') {
        throw new Error('Expected a Codex or Claude Code SessionEnd event, or an Ignition completion.');
    }
    if (!input.transcript_path) {
        return;
    }

    // Codex only allows three seconds for SessionEnd; the HTTP request can outlive the hook.
    if (source === 'codex' && !background) {
        const child = spawn(process.execPath, [scriptPath, source, '--background'], {
            detached: true,
            stdio: ['pipe', 'ignore', 'ignore'],
        });
        await once(child, 'spawn');
        child.stdin.end(JSON.stringify(input));
        child.unref();

        return;
    }

    const readMessage = source === 'codex' ? messageFromCodex : messageFromClaude;
    await saveSessionMemory(source, input.session_id, readTranscript(input.transcript_path, readMessage));
}

if (process.argv[1] === scriptPath) {
    try {
        await runHook(process.argv[2], process.argv[3] === '--background');
    } catch (error) {
        console.error(`Session memory hook failed: ${error.message}`);
        process.exitCode = 1;
    }
}
