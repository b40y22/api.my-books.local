<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRepository implements AbstractRepositoryInterface
{
    public function __construct(
        protected $model
    ) {}

    /**
     * @throws NotFoundException
     */
    public function findOrFail(int $id): Model
    {
        $model = $this->model->where('id', $id)->first();

        if (! $model) {
            throw new NotFoundException(__('errors.model_not_found', ['id' => $id]));
        }

        return $model;
    }

    public function find(int $id): ?Model
    {
        return $this->model->find($id);
    }
}
