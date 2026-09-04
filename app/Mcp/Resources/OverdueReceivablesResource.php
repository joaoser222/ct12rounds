<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\ReceivableRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('receivables-overdue')]
#[Description('Contas a receber vencidas')]
#[MimeType('application/json')]
#[Uri('ct12rounds://receivables/overdue')]
class OverdueReceivablesResource extends Resource
{
    public function __construct(
        protected ReceivableRepositoryInterface $repository,
    ) {}

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('receivables.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->repository->findOverdue()->toArray());
    }
}
