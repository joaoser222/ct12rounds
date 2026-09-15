<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface ClientRepositoryInterface extends RepositoryInterface
{
    public function findWithRelations(int $id): ?Model;

    public function findByDocument(string $document): ?Model;

    public function findByEmail(string $email): ?Model;

    public function findByEmailAndPhone(string $email, ?string $phone): ?Model;

    public function findActive(): Collection;
}
