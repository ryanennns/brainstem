<?php

use App\Mcp\Servers\ProjectServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('mcp/hello-world', ProjectServer::class)
    ->middleware('auth:sanctum');
