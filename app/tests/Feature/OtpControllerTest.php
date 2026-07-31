<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Services\WhatsAppEngineClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class OtpControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_a_request_without_an_api_key(): void
    {
        $this->postJson('/api/otp/send', [
            'phone' => '33612345678',
            'message' => 'Code: 1234',
        ])->assertStatus(401);
    }

    public function test_it_rejects_a_request_with_an_invalid_api_key(): void
    {
        $this->postJson('/api/otp/send', [
            'phone' => '33612345678',
            'message' => 'Code: 1234',
        ], ['X-Api-Key' => 'not-a-real-key'])
            ->assertStatus(401);
    }

    public function test_it_rejects_an_invalid_phone_number(): void
    {
        [, $key] = ApiKey::generate('test');

        $this->postJson('/api/otp/send', [
            'phone' => 'not-a-phone',
            'message' => 'Code: 1234',
        ], ['X-Api-Key' => $key])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_it_returns_503_when_the_whatsapp_session_is_not_ready(): void
    {
        [, $key] = ApiKey::generate('test');

        $this->mock(WhatsAppEngineClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('status')->once()->andReturn(['status' => 'QR_READY', 'phone' => null]);
            $mock->shouldNotReceive('sendText');
        });

        $this->postJson('/api/otp/send', [
            'phone' => '33612345678',
            'message' => 'Code: 1234',
        ], ['X-Api-Key' => $key])
            ->assertStatus(503)
            ->assertJson(['success' => false]);
    }

    public function test_it_sends_the_message_when_the_session_is_ready(): void
    {
        [, $key] = ApiKey::generate('test');

        $this->mock(WhatsAppEngineClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('status')->once()->andReturn(['status' => 'READY', 'phone' => '33600000000']);
            $mock->shouldReceive('sendText')
                ->once()
                ->with('33612345678', 'Code: 1234')
                ->andReturn(['success' => true, 'id' => 'wa-message-id', 'httpStatus' => 200]);
        });

        $this->postJson('/api/otp/send', [
            'phone' => '33612345678',
            'message' => 'Code: 1234',
        ], ['X-Api-Key' => $key])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'id' => 'wa-message-id']);
    }

    public function test_it_updates_the_api_keys_last_used_timestamp(): void
    {
        [$apiKey, $key] = ApiKey::generate('test');
        $this->assertNull($apiKey->last_used_at);

        $this->mock(WhatsAppEngineClient::class, function (MockInterface $mock) {
            $mock->shouldReceive('status')->andReturn(['status' => 'READY']);
            $mock->shouldReceive('sendText')->andReturn(['success' => true, 'id' => 'x', 'httpStatus' => 200]);
        });

        $this->postJson('/api/otp/send', [
            'phone' => '33612345678',
            'message' => 'Code: 1234',
        ], ['X-Api-Key' => $key]);

        $this->assertNotNull($apiKey->fresh()->last_used_at);
    }
}
