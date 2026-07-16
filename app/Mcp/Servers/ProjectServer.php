<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProject;
use App\Mcp\Tools\CreateProjectUpdate;
use App\Mcp\Tools\CreateRepository;
use App\Mcp\Tools\GetProject;
use App\Mcp\Tools\GetProjectUpdates;
use App\Mcp\Tools\GetRepository;
use App\Mcp\Tools\HelloWorld;
use App\Mcp\Tools\ListProjects;
use App\Mcp\Tools\ListRepositories;
use App\Mcp\Tools\SearchProjects;
use App\Mcp\Tools\SearchRepositories;
use App\Mcp\Tools\UpdateProject;
use App\Mcp\Tools\UpdateRepository;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Project Server')]
#[Version('0.0.1')]
#[Instructions('Repositories are durable codebases; projects are units of work. Store the repository default branch on its repository. Keep project working_branches limited to feature branches used for that work, and leave them empty when a project represents the whole repository. Every project update must include the current agent client name and its stable session ID.')]
class ProjectServer extends Server
{
    protected array $tools = [
        CreateProject::class,
        CreateProjectUpdate::class,
        CreateRepository::class,
        GetProject::class,
        GetProjectUpdates::class,
        GetRepository::class,
        HelloWorld::class,
        ListProjects::class,
        ListRepositories::class,
        SearchProjects::class,
        SearchRepositories::class,
        UpdateProject::class,
        UpdateRepository::class,
    ];
}
