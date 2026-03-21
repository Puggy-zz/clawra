#!/usr/bin/env node
import { query } from '@anthropic-ai/claude-agent-sdk';
import { parseArgs } from 'node:util';

const { values } = parseArgs({
    options: {
        model: { type: 'string' },
        cwd: { type: 'string' },
    },
});

const prompt = process.env.CLAWRA_PROMPT ?? '';
const cwd = values.cwd;
const model = values.model;

let sessionId = null, result = '', stopReason = 'end_turn';

try {
    for await (const message of query({
        prompt,
        options: {
            cwd,
            allowedTools: ['Read', 'Write', 'Edit', 'Bash', 'Glob', 'Grep'],
            permissionMode: 'bypassPermissions',
            allowDangerouslySkipPermissions: true,
            maxTurns: 50,
            ...(model ? { model } : {}),
        },
    })) {
        if (message.type === 'system' && message.subtype === 'init') {
            sessionId = message.session_id;
        }
        if ('result' in message) {
            result = message.result ?? '';
            stopReason = message.stop_reason ?? 'end_turn';
        }
    }
    process.stdout.write(JSON.stringify({ success: true, result, session_id: sessionId, stop_reason: stopReason }) + '\n');
} catch (err) {
    process.stdout.write(JSON.stringify({ success: false, error: String(err?.message ?? err), session_id: sessionId }) + '\n');
    process.exit(1);
}
