<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permission\StorePermissionRequest;
use App\Http\Requests\Permission\UpdatePermissionRequest;
use App\Services\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        return view('permissions.index');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Permission::class);

        if ($request->ajax()) {
            return view('permissions.partials.create-form');
        }

        return view('permissions.create');
    }

    public function store(StorePermissionRequest $request): JsonResponse|RedirectResponse
    {
        $permission = $this->permissions->create($request->validated());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Permiso creado correctamente.', 'data' => ['id' => $permission->id]], 201);
        }

        return redirect()->route('permissions.index')->with('success', 'Permiso creado correctamente.');
    }

    public function show(Request $request, Permission $permission): View
    {
        $this->authorize('view', $permission);

        if ($request->ajax()) {
            return view('permissions.partials.show', compact('permission'));
        }

        return view('permissions.show', compact('permission'));
    }

    public function edit(Request $request, Permission $permission): View
    {
        $this->authorize('update', $permission);

        if ($request->ajax()) {
            return view('permissions.partials.edit-form', compact('permission'));
        }

        return view('permissions.edit', compact('permission'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse|RedirectResponse
    {
        $permission = $this->permissions->update($permission, $request->validated());

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Permiso actualizado correctamente.', 'data' => ['id' => $permission->id]]);
        }

        return redirect()->route('permissions.index')->with('success', 'Permiso actualizado correctamente.');
    }

    public function destroy(Request $request, Permission $permission): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $permission);

        try {
            $this->permissions->delete($permission);
        } catch (ValidationException $exception) {
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => collect($exception->errors())->flatten()->first(), 'data' => $exception->errors()], 422);
            }

            return back()->withErrors($exception->errors());
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Permiso eliminado correctamente.', 'data' => null]);
        }

        return redirect()->route('permissions.index')->with('success', 'Permiso eliminado correctamente.');
    }
}
