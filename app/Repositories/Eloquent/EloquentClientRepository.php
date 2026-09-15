<?php

namespace App\Repositories\Eloquent;

use App\Models\Client;
use App\Repositories\Contracts\ClientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentClientRepository extends BaseEloquentRepository implements ClientRepositoryInterface
{
    protected function modelClass(): string
    {
        return Client::class;
    }

    protected array $with = [];

    protected array $searchableFields = ['name', 'document', 'email', 'phone'];

    protected array $filterableFields = ['status', 'visibility', 'gender'];

    protected string $defaultSort = 'name';

    protected string $defaultSortDirection = 'asc';

    public function findWithRelations(int $id): ?Model
    {
        return $this->newQuery()->find($id);
    }

    public function findByDocument(string $document): ?Model
    {
        $cleanDocument = preg_replace('/\D+/', '', $document);

        return $this->newQuery()->where('document', $cleanDocument)->first();
    }

    public function findByEmail(string $email): ?Model
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    public function findByEmailAndPhone(string $email, ?string $phone): ?Model
    {
        $query = $this->newQuery()->where('email', $email);

        if ($phone !== null && $phone !== '') {
            $query->where('phone', $phone);
        }

        return $query->first();
    }

    public function findActive(): Collection
    {
        return $this->newQuery()->where('status', 'active')->where('visibility', 'visible')->get();
    }
}
