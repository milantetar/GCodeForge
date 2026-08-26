<?php

namespace App\Services;

use App\Repositories\CoreRepository;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Shared application-service utilities.
 *
 * Services coordinate business rules and transactions. Query composition stays
 * in repositories, so controllers do not communicate with Eloquent directly.
 *
 * @template TModel of Model
 * @template TRepository of CoreRepository<TModel>
 */
abstract class CoreService
{
    /**
     * @var TRepository
     */
    protected CoreRepository $repository;

    /**
     * @param  TRepository  $repository
     */
    public function __construct(CoreRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Execute related writes atomically.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @param  positive-int  $attempts
     * @return TResult
     */
    protected function transaction(Closure $callback, int $attempts = 3): mixed
    {
        return DB::transaction($callback, $attempts);
    }
}
