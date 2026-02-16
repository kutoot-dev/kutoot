<?php

namespace App\Policies;

use App\Models\MerchantLocation;
use App\Models\User;

class MerchantLocationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Merchant Admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MerchantLocation $merchantLocation): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasRole('Merchant Admin') && $user->merchantLocations->contains($merchantLocation);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Super Admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MerchantLocation $merchantLocation): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasRole('Merchant Admin') && $user->merchantLocations->contains($merchantLocation);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MerchantLocation $merchantLocation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MerchantLocation $merchantLocation): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MerchantLocation $merchantLocation): bool
    {
        return false;
    }
}
