<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\PayableRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('payables-pending')]
#[Description('Contas a pagar pendentes')]
#[MimeType('application/json')]
#[Uri('ct12rounds://payables/pending')]
class PayablesListResource extends Resource
{
    public function __construct(
        protected PayableRepositoryInterface $repository,
    ) {}

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('payables.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->repository->findPending()->toArray());
    }
}
