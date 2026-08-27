<?php

namespace App\Mcp\Tools;

use App\Notifications\AgentMessage;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Notification;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Description('Send a message from the agent to the configured recipient via SMS.')]
class SendSms extends Tool
{
    public function handle(Request $request): Response
    {
        $message = $request->validate([
            'message' => ['required', 'string', 'max:1600'],
        ])['message'];
        $to = config('services.vonage.to');

        if (! is_string($to) || $to === '') {
            return Response::error('SMS is not configured.');
        }

        try {
            Notification::route('vonage', $to)->notify(new AgentMessage($message));
        } catch (Throwable) {
            return Response::error('SMS could not be sent.');
        }

        return Response::text('SMS sent.');
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()
                ->description('The message to send via SMS.')
                ->required(),
        ];
    }
}
