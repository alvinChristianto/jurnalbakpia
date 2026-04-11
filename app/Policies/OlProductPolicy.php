<?php

namespace App\Policies;

use App\Models\OlProduct;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OlProductPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_ol::product');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OlProduct $olProduct): bool
    {
        return $user->can('view_ol::product');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_ol::product');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OlProduct $olProduct): bool
    {
        return $user->can('update_ol::product');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OlProduct $olProduct): bool
    {
        return $user->can('delete_ol::product');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OlProduct $olProduct): bool
    {
        return $user->can('restore_ol::product');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OlProduct $olProduct): bool
    {
        return $user->can('force_delete_ol::product');
    }
}
