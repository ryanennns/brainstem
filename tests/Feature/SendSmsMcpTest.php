<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProjectServer;
use App\Mcp\Tools\SendSms;
use App\Models\User;
use App\Notifications\AgentMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendSmsMcpTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_messages_are_sent_as_vonage_notifications(): void
    {
        config([
            'vonage.api_key' => 'key',
            'vonage.api_secret' => 'secret',
            'vonage.sms_from' => '15550000001',
            'services.vonage.to' => '15550000002',
        ]);
        Notification::fake();

        ProjectServer::actingAs(User::factory()->create())->tool(SendSms::class, [
            'message' => 'The deployment finished.',
        ])->assertOk()
            ->assertSee('SMS sent.');

        Notification::assertSentOnDemand(AgentMessage::class, function (
            AgentMessage $notification,
            array $channels,
            AnonymousNotifiable $notifiable,
        ): bool {
            return $channels === ['vonage']
                && $notifiable->routes === ['vonage' => '15550000002']
                && $notification->toVonage($notifiable)->content === 'The deployment finished.';
        });
    }

    public function test_agent_messages_require_sms_configuration(): void
    {
        config(['services.vonage.to' => null]);
        Notification::fake();

        ProjectServer::actingAs(User::factory()->create())->tool(SendSms::class, [
            'message' => 'This should not be sent.',
        ])->assertHasErrors(['SMS is not configured.']);

        Notification::assertNothingSent();
    }
}
