<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared persistence operations for Eloquent-backed repositories.
 *
 * @template TModel of Model
 */
abstract class CoreRepository
{
    /**
     * Create a query for the Eloquent model managed by this repository.
     *
     * @return Builder<TModel>
     */
    abstract protected function newModelQuery(): Builder;

    /**
     * Start a new query for the repository model.
     *
     * @return Builder<TModel>
     */
    public function query(): Builder
    {
        return $this->newModelQuery();
    }

    /**
     * @param  array<int, string>  $columns
     * @return TModel
     */
    public function findOrFail(int|string $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  array<int, string>  $columns
     * @return TModel|null
     */
    public function findBy(array $criteria, array $columns = ['*']): ?Model
    {
        return $this->query()->where($criteria)->first($columns);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    /**
     * @param  TModel  $model
     * @param  array<string, mixed>  $attributes
     * @return TModel
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model;
    }

    /**
     * @param  TModel  $model
     */
    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    /**
     * @param  array<int, string>  $columns
     * @return LengthAwarePaginator<int, TModel>
     */
    public function paginate(
        int $perPage = 15,
        array $columns = ['*'],
        string $pageName = 'page',
        ?int $page = null,
    ): LengthAwarePaginator {
        return $this->query()->paginate($perPage, $columns, $pageName, $page);
    }
}
