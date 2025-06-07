<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository implements AbstractRepositoryInterface
{
    public function __construct(
        protected $model
    ) {}

    public function findOrFail(int $id): Model
    {
        return $this->model->where('id', $id)->first();
    }
}
