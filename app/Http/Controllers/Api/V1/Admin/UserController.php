<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreUserRequest;
use App\Http\Requests\Api\V1\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;

/**
 * @tags Admin / Users
 */
class UserController extends Controller
{
    /**
     * List all users.
     *
     * @queryParam search string Search by name, email, or mobile.
     * @queryParam filter[role] string Filter by role name.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['roles', 'activeSubscription.plan', 'primaryCampaign'])
            ->when($request->input('search'), fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%");
            }))
            ->when($request->input('filter.role'), fn ($q, $role) => $q->role($role))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    /**
     * Show a user.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'activeSubscription.plan', 'primaryCampaign']);

        return new UserResource($user);
    }

    /**
     * Create a new user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        // remove upload key before mass assignment; file handled separately
        $createData = Arr::except($data, ['profile_picture']);

        $user = User::create($createData);

        if ($request->hasFile('profile_picture')) {
            $user->addMediaFromRequest('profile_picture')->toMediaCollection('avatar');
        }

        if (isset($data['roles'])) {
            $user->syncRoles(
                \Spatie\Permission\Models\Role::whereIn('id', $data['roles'])->pluck('name')
            );
        }

        $user->load(['roles', 'activeSubscription.plan', 'primaryCampaign']);

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a user.
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if ($request->hasFile('profile_picture')) {
            $user->clearMediaCollection('avatar');
            $user->addMediaFromRequest('profile_picture')->toMediaCollection('avatar');
        }

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        if (isset($data['roles'])) {
            $user->syncRoles(
                \Spatie\Permission\Models\Role::whereIn('id', $data['roles'])->pluck('name')
            );
        }

        $user->load(['roles', 'activeSubscription.plan', 'primaryCampaign']);

        return new UserResource($user);
    }

    /**
     * Delete a user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted.'], 200);
    }
}
