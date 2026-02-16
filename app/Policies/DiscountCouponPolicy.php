<?php

namespace App\Policies;

use App\Models\DiscountCoupon;
use App\Models\User;

class DiscountCouponPolicy
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
    public function view(User $user, DiscountCoupon $discountCoupon): bool
    {
        return $user->hasRole(['Super Admin', 'Merchant Admin']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Merchant Admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DiscountCoupon $discountCoupon): bool
    {
        return $user->hasRole(['Super Admin', 'Merchant Admin']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DiscountCoupon $discountCoupon): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DiscountCoupon $discountCoupon): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DiscountCoupon $discountCoupon): bool
    {
        return false;
    }
}
