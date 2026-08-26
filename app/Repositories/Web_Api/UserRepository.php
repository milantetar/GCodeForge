<?php

namespace App\Repositories\Web_Api;

use App\Models\User;
use App\Repositories\CoreRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends CoreRepository<User>
 */
class UserRepository extends CoreRepository
{
    /**
     * @return Builder<User>
     */
    protected function newModelQuery(): Builder
    {
        return User::query();
    }

    public function findByEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findBy(['email' => $email]);

        return $user;
    }
}
