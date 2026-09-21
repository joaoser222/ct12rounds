<?php

namespace App\Repositories\Eloquent;

use App\Models\LoyaltyLevel;
use App\Repositories\Contracts\LoyaltyLevelRepositoryInterface;

class EloquentLoyaltyLevelRepository extends BaseEloquentRepository implements LoyaltyLevelRepositoryInterface
{
    protected function modelClass(): string
    {
        return LoyaltyLevel::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name'];

    protected array $filterableFields = ['visibility'];

    protected string $defaultSort = 'min_months';

    protected string $defaultSortDirection = 'asc';
}
