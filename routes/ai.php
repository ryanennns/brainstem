<?php

use App\Mcp\Servers\AuthServer;
use App\Mcp\Servers\ProjectServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('mcp/auth', AuthServer::class);

Mcp::web('mcp/projects', ProjectServer::class)
    ->middleware('auth:sanctum');
