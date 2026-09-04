<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\PlanRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('plan')]
#[Description('Detalhes de um plano por ID')]
#[MimeType('application/json')]
class PlanResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected PlanRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://plans/{id}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('plans.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $model = $this->repository->findWithRelations((int) $request->get('id'));

        if ($model === null) {
            return Response::error('Plano não encontrado.');
        }

        return Response::json($model->toArray());
    }
}
