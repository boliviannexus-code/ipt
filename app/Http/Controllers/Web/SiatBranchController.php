<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSinBranchRequest;
use App\Http\Requests\StoreSinPointOfSaleRequest;
use App\Models\SinBranch;
use App\Services\Siat\SinBranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiatBranchController extends Controller
{
    public function __construct(
        private readonly SinBranchService $branches,
    ) {}

    public function index(): View
    {
        return view('siat.branches.index', [
            'branches' => $this->branches->branches(),
        ]);
    }

    public function store(StoreSinBranchRequest $request): RedirectResponse
    {
        $this->branches->createBranch($request->user(), $request->validated());

        return redirect()
            ->route('siat.branches.index')
            ->with('success', 'Sucursal registrada correctamente.');
    }

    public function storePoint(StoreSinPointOfSaleRequest $request, SinBranch $branch): RedirectResponse
    {
        $this->branches->createPointOfSale($branch, $request->validated());

        return redirect()
            ->route('siat.branches.index')
            ->with('success', 'Punto de venta registrado correctamente.');
    }
}
