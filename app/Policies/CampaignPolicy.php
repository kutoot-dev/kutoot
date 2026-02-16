<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Filtered in controller
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Campaign $campaign): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($user->hasRole('Merchant Admin')) {
            // Can view if they created it or it belongs to their merchant location?
            // Campaigns are created by users with 'Merchant Admin' role.
            return $campaign->creator_id === $user->id;
        }

        // Standard User: Can view if plan allows
        return $user->activeSubscription &&
            $campaign->plans()->where('plan_id', $user->activeSubscription->plan_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage campaigns');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Campaign $campaign): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        return $user->hasPermissionTo('manage campaigns') && $campaign->creator_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Campaign $campaign): bool
    {
        return $this->update($user, $campaign);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Campaign $campaign): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Campaign $campaign): bool
    {
        return false;
    }
}
