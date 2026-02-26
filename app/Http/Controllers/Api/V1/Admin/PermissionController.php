<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StorePermissionRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\Permission\Models\Permission;

/**
 * @tags Admin / Permissions
 */
class PermissionController extends Controller
{
    /**
     * List all permissions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('manage-permissions');

        $permissions = Permission::query()
            ->when($request->input('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->input('filter.guard_name'), fn ($q, $g) => $q->where('guard_name', $g))
            ->latest()
            ->paginate($request->integer('per_page', 50));

        return PermissionResource::collection($permissions);
    }

    /**
     * Show a permission.
     */
    public function show(Permission $permission): PermissionResource
    {
        $this->authorize('manage-permissions');

        return new PermissionResource($permission);
    }

    /**
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create($request->validated());

        return (new PermissionResource($permission))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): PermissionResource
    {
        $permission->update($request->validated());

        return new PermissionResource($permission);
    }

    /**
     * Delete a permission.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('manage-permissions');

        $permission->delete();

        return response()->json(['message' => 'Permission deleted.'], 200);
    }
}
