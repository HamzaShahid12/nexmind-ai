<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Users may only touch rows they own. Registered for every user-owned model in AppServiceProvider. */
class OwnedPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $model->getAttribute('user_id') === $user->id;
    }

    public function update(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->view($user, $model);
    }
}
