<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use App\Services\PermissionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly PermissionService $permissions
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        return $this->successResponse(PermissionResource::collection($this->permissions->paginate()));
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissions->create($request->validated());

        return $this->successResponse(PermissionResource::make($permission), 'Permiso creado correctamente.', 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        $this->authorize('view', $permission);

        return $this->successResponse(PermissionResource::make($permission));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->permissions->update($permission, $request->validated());

        return $this->successResponse(PermissionResource::make($permission), 'Permiso actualizado correctamente.');
    }

    public function destroy(Permission $permission): JsonResponse
    {
        $this->authorize('delete', $permission);

        $this->permissions->delete($permission);

        return $this->successResponse(null, 'Permiso eliminado correctamente.');
    }
}
