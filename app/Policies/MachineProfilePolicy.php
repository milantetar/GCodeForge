<?php

namespace App\Policies;

use App\Models\MachineProfile;
use App\Models\User;

class MachineProfilePolicy
{
    public function view(User $user, MachineProfile $profile): bool
    {
        return $profile->user_id === $user->id || $user->can('projects.manage');
    }

    public function update(User $user, MachineProfile $profile): bool
    {
        return $this->view($user, $profile);
    }

    public function delete(User $user, MachineProfile $profile): bool
    {
        return $this->view($user, $profile);
    }
}
