import { Plugin } from '@opencode/plugin';
import { saveSessionMemory, sessionMemoryIsConfigured } from '../session-memory.mjs';

export default Plugin.define({
    id: 'brainstem.session-memory',
    setup(ctx) {
        if (!sessionMemoryIsConfigured()) {
            return;
        }

        const controller = new AbortController();
        void (async () => {
            for await (const event of ctx.event.subscribe({ types: ['session.archived'], signal: controller.signal })) {
                try {
                    const { sessionID } = event.data;
                    const session = await ctx.session.get({ sessionID });
                    if (session.parentID || session.location.directory !== ctx.location.directory) {
                        continue;
                    }

                    const messages = await ctx.session.context({ sessionID });
                    const conversation = messages.flatMap((message) => {
                        if (message.type === 'user') {
                            return [{ role: 'user', text: message.text }];
                        }
                        if (message.type === 'assistant') {
                            const text = message.content
                                .filter((part) => part.type === 'text')
                                .map((part) => part.text)
                                .join('\n');

                            return [{ role: 'assistant', text }];
                        }

                        return [];
                    });

                    await saveSessionMemory('opencode', sessionID, conversation, session.outcome);
                } catch (error) {
                    console.error('Brainstem could not record the archived session:', error instanceof Error ? error.message : error);
                }
            }
        })().catch((error) => {
            if (!controller.signal.aborted) {
                console.error('Brainstem session memory stopped listening for archived sessions:', error);
            }
        });

        return () => controller.abort();
    },
});
