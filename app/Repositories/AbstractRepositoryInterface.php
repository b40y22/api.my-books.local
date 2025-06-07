<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

interface AbstractRepositoryInterface
{
    public function findOrFail(int $id): Model;
}
