<?php

namespace App\Policies;

use App\Models\Conversion;
use App\Models\User;

class ConversionPolicy
{
    public function view(User $user, Conversion $conversion): bool
    {
        return $conversion->user_id === $user->id || $user->can('projects.manage');
    }

    public function download(User $user, Conversion $conversion): bool
    {
        return $this->view($user, $conversion);
    }
}
