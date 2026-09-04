<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\PurchaseRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('purchases')]
#[Description('Lista paginada de compras')]
#[MimeType('application/json')]
#[Uri('ct12rounds://purchases')]
class PurchasesListResource extends Resource
{
    public function __construct(
        protected PurchaseRepositoryInterface $repository,
    ) {}

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('purchases.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->repository->paginate()->toArray());
    }
}
