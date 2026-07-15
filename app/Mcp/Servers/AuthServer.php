<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\SignIn;
use App\Mcp\Tools\SignUp;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Auth Server')]
#[Version('0.0.1')]
#[Instructions('Sign up or sign in to receive a Sanctum API token for the private project server.')]
class AuthServer extends Server
{
    protected array $tools = [
        SignUp::class,
        SignIn::class,
    ];
}
