<?php

use App\Http\Controllers\Mcp\OAuthController;
use App\Mcp\Servers\AuthServer;
use App\Mcp\Servers\ProjectServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

Route::get('/.well-known/oauth-protected-resource', [OAuthController::class, 'protectedResource'])
    ->name('mcp.oauth.protected-resource');

Route::get('/.well-known/oauth-protected-resource/{path}', [OAuthController::class, 'protectedResource'])
    ->where('path', '.*')
    ->name('mcp.oauth.protected-resource.nested');

Route::get('/.well-known/oauth-authorization-server', [OAuthController::class, 'authorizationServer'])
    ->name('mcp.oauth.authorization-server');

Route::get('/.well-known/oauth-authorization-server/{path}', [OAuthController::class, 'authorizationServer'])
    ->where('path', '.*')
    ->name('mcp.oauth.authorization-server.nested');

Route::post('/oauth/register', [OAuthController::class, 'register'])
    ->name('mcp.oauth.register');

Route::post('/oauth/token', [OAuthController::class, 'token'])
    ->name('mcp.oauth.token');

Route::middleware('web')->group(function (): void {
    Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])
        ->name('mcp.oauth.authorize');

    Route::post('/oauth/authorize', [OAuthController::class, 'approve'])
        ->name('mcp.oauth.approve');
});

Mcp::web('mcp/auth', AuthServer::class);

Mcp::web('mcp/projects', ProjectServer::class)
    ->middleware('auth:sanctum');
