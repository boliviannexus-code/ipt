<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\AssignRolesRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $users
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        return $this->successResponse(UserResource::collection($this->users->paginate(withTrashed: true)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return $this->successResponse(UserResource::make($user), 'Usuario creado correctamente.', 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->successResponse(UserResource::make($user->load(['personnel.position.area', 'roles'])));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->users->update($user, $request->validated());

        return $this->successResponse(UserResource::make($user), 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user, $request->user());

        return $this->successResponse(null, 'Usuario eliminado correctamente.');
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $isCurrentUser = $request->user()?->is($user) ?? false;

        $user = $this->users->toggleStatus($user);

        if ($isCurrentUser && ! $user->is_active) {
            $this->logoutCurrentUser($request);
        }

        return $this->successResponse(UserResource::make($user), 'Estado actualizado correctamente.');
    }

    public function changePassword(ChangePasswordRequest $request, User $user): JsonResponse
    {
        $this->authorize('changePassword', $user);
        $isCurrentUser = $request->user()?->is($user) ?? false;

        $user = $this->users->changePassword($user, $request->validated('password'));

        if ($isCurrentUser) {
            $this->logoutCurrentUser($request);
        }

        return $this->successResponse(UserResource::make($user), 'Contraseña actualizada correctamente.');
    }

    public function assignRoles(AssignRolesRequest $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);

        $user = $this->users->syncRoles($user, $request->validated('roles') ?? []);

        return $this->successResponse(UserResource::make($user), 'Roles actualizados correctamente.');
    }

    private function logoutCurrentUser(Request $request): void
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
