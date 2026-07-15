<?php

namespace Tests\Feature;

use Tests\TestCase;

class McpAuthenticationTest extends TestCase
{
    public function test_unauthenticated_project_mcp_requests_return_401(): void
    {
        $this->postJson('/mcp/projects')
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate', 'Bearer realm="mcp", error="invalid_token"');
    }
}
