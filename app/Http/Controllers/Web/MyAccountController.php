<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyAccountController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function edit(Request $request): View
    {
        return view('account.edit', ['user' => $request->user()->load('personnel.position.area')]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ]);

        $this->users->changeOwnPassword($request->user(), $data['password'], $request->session()->getId());

        return back()->with('success', 'Contraseña actualizada correctamente.');
    }
}
