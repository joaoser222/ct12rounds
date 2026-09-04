<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Name('invoices')]
#[Description('Lista paginada de notas fiscais')]
#[MimeType('application/json')]
#[Uri('ct12rounds://invoices')]
class InvoicesListResource extends Resource
{
    public function __construct(
        protected InvoiceRepositoryInterface $repository,
    ) {}

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('gateway_invoices.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        return Response::json($this->repository->paginate()->toArray());
    }
}
