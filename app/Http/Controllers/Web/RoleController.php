<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\AssignRolePermissionsRequest;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
        private readonly PermissionRepository $permissions
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        return view('roles.index', [
            'roles' => $this->roles->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Role::class);

        $data = ['permissionGroups' => $this->permissions->allGroupedByModule()];

        if ($request->ajax()) {
            return view('roles.partials.create-form', $data);
        }

        return view('roles.create', $data);
    }

    public function store(StoreRoleRequest $request): JsonResponse|RedirectResponse
    {
        $role = $this->roles->create($request->validated());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rol creado correctamente.', 'data' => ['id' => $role->id]], 201);
        }

        return redirect()->route('roles.index')->with('success', 'Rol creado correctamente.');
    }

    public function show(Request $request, Role $role): View
    {
        $this->authorize('view', $role);

        $role = app(RoleRepository::class)
            ->withUsersCount($role->load('permissions')->loadCount('permissions'));

        if ($request->ajax()) {
            return view('roles.partials.show', compact('role'));
        }

        return view('roles.show', compact('role'));
    }

    public function edit(Request $request, Role $role): View
    {
        $this->authorize('update', $role);

        $data = [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissions->allGroupedByModule(),
        ];

        if ($request->ajax()) {
            return view('roles.partials.edit-form', $data);
        }

        return view('roles.edit', $data);
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        try {
            $role = $this->roles->update($role, $request->validated());
        } catch (ValidationException $exception) {
            return $this->validationFailure($request, $exception);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rol actualizado correctamente.', 'data' => ['id' => $role->id]]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $role);

        try {
            $this->roles->delete($role);
        } catch (ValidationException $exception) {
            return $this->validationFailure($request, $exception);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Rol eliminado correctamente.', 'data' => null]);
        }

        return redirect()->route('roles.index')->with('success', 'Rol eliminado correctamente.');
    }

    public function permissionsForm(Request $request, Role $role): View
    {
        $this->authorize('assignPermissions', $role);

        $data = [
            'role' => $role->load('permissions'),
            'permissionGroups' => $this->permissions->allGroupedByModule(),
        ];

        return view('roles.partials.permissions-form', $data);
    }

    public function assignPermissions(AssignRolePermissionsRequest $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->authorize('assignPermissions', $role);

        try {
            $role = $this->roles->syncPermissions($role, $request->validated('permissions') ?? []);
        } catch (ValidationException $exception) {
            return $this->validationFailure($request, $exception);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Permisos del rol actualizados correctamente.', 'data' => ['id' => $role->id]]);
        }

        return redirect()->route('roles.index')->with('success', 'Permisos del rol actualizados correctamente.');
    }

    private function validationFailure(Request $request, ValidationException $exception): JsonResponse|RedirectResponse
    {
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => collect($exception->errors())->flatten()->first(),
                'data' => $exception->errors(),
            ], 422);
        }

        return back()->withErrors($exception->errors());
    }
}
