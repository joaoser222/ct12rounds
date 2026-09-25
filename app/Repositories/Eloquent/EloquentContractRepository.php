<?php

namespace App\Repositories\Eloquent;

use App\Enums\BillableStatus;
use App\Models\Contract;
use App\Repositories\Contracts\ContractRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentContractRepository extends BaseEloquentRepository implements ContractRepositoryInterface
{
    protected function modelClass(): string
    {
        return Contract::class;
    }

    protected array $with = ['client', 'plan', 'coupon'];

    protected array $searchableFields = ['plan_name', 'status'];

    protected array $filterableFields = ['client_id', 'plan_id', 'status', 'visibility'];

    protected string $defaultSort = 'created_at';

    protected string $defaultSortDirection = 'desc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findEligibleForGatewaySync(array $filters = []): Collection
    {
        return $this->newQuery()
            ->where('payment_method', '!=', 'cash')
            ->where('accepted_terms', 'accepted')
            ->whereHas('invoices', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->where('payment_method', 'boleto')
                        ->orWhere(function ($nested) {
                            $nested->whereIn('payment_method', ['pix', 'credit_card'])
                                ->whereDate('due_date', now()->toDateString());
                        });
                });
            })
            ->get();
    }

    public function findByClient(int $clientId): Collection
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findActiveByClient(int $clientId): ?Model
    {
        return $this->newQuery()
            ->where('client_id', $clientId)
            ->where('status', BillableStatus::OPEN->value)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    public function countByStatus(string $status): int
    {
        return $this->newQuery()->where('status', $status)->count();
    }
}
