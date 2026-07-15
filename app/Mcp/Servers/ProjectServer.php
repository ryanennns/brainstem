<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\CreateProjectUpdate;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\GetProjectUpdates;
use App\Mcp\Tools\HelloWorld;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\SearchProjects;
use App\Mcp\Tools\UpdateProject;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Project Server')]
#[Version('0.0.1')]
#[Instructions('Create projects and append updates to projects owned by the authenticated user.')]
class ProjectServer extends Server
{
    protected array $tools = [
        CreateProject::class,
        CreateProjectUpdate::class,
        GetProject::class,
        GetProjectUpdates::class,
        HelloWorld::class,
        ListProjects::class,
        SearchProjects::class,
        UpdateProject::class,
    ];
}
