<?php

namespace App\Policies;

use App\Models\OlEcommerceTransactionDetail;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class OlEcommerceTransactionDetailPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OlEcommerceTransactionDetail $olEcommerceTransactionDetail): bool
    {
        return $user->can('view_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OlEcommerceTransactionDetail $olEcommerceTransactionDetail): bool
    {
        return $user->can('update_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OlEcommerceTransactionDetail $olEcommerceTransactionDetail): bool
    {
        return $user->can('delete_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OlEcommerceTransactionDetail $olEcommerceTransactionDetail): bool
    {
        return $user->can('restore_ol::ecommerce::transaction::detail');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OlEcommerceTransactionDetail $olEcommerceTransactionDetail): bool
    {
        return $user->can('force_delete_ol::ecommerce::transaction::detail');
    }
}
