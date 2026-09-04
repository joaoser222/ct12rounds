<?php

declare(strict_types=1);

namespace App\Mcp\Resources;

use App\Repositories\Contracts\MovementRepositoryInterface;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;

#[Name('movements-range')]
#[Description('Movimentações de caixa por intervalo de datas (start/end no formato Y-m-d)')]
#[MimeType('application/json')]
class MovementsByDateResource extends Resource implements HasUriTemplate
{
    public function __construct(
        protected MovementRepositoryInterface $repository,
    ) {}

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('ct12rounds://movements/range/{start}/{end}');
    }

    public function shouldRegister(): bool
    {
        return auth()->user()?->can('movements.view') ?? false;
    }

    public function handle(Request $request): Response
    {
        $start = (string) $request->get('start');
        $end = (string) $request->get('end');

        return Response::json($this->repository->findByDateRange($start, $end)->toArray());
    }
}
