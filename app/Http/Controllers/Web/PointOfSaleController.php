<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePointOfSaleRequest;
use App\Http\Requests\UpdatePointOfSaleRequest;
use App\Models\PointOfSale;
use App\Models\User;
use App\Services\BranchService;
use App\Services\PointOfSaleService;
use App\Services\WarehouseService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PointOfSaleController extends Controller
{
    public function __construct(
        private readonly PointOfSaleService $pointOfSales,
        private readonly BranchService $branches,
        private readonly WarehouseService $warehouses
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PointOfSale::class);

        return view('point-of-sales.index', [
            'pointOfSales' => $this->pointOfSales->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', PointOfSale::class);

        $data = $this->formData();

        if ($request->ajax()) {
            return view('point-of-sales.partials.create-form', $data);
        }

        return view('point-of-sales.create', $data);
    }

    public function store(StorePointOfSaleRequest $request): JsonResponse|RedirectResponse
    {
        $pointOfSale = $this->pointOfSales->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto de venta creado correctamente.',
                'data' => ['id' => $pointOfSale->id],
            ], 201);
        }

        return redirect()
            ->route('point-of-sales.index')
            ->with('success', 'Punto de venta creado correctamente.');
    }

    public function show(Request $request, PointOfSale $pointOfSale): View
    {
        $this->authorize('view', $pointOfSale);

        $pointOfSale->load(['branch', 'warehouse', 'company', 'users']);

        if ($request->ajax()) {
            return view('point-of-sales.partials.show', compact('pointOfSale'));
        }

        return view('point-of-sales.show', compact('pointOfSale'));
    }

    public function edit(Request $request, PointOfSale $pointOfSale): View
    {
        $this->authorize('update', $pointOfSale);

        $data = $this->formData() + ['pointOfSale' => $pointOfSale->load('users')];

        if ($request->ajax()) {
            return view('point-of-sales.partials.edit-form', $data);
        }

        return view('point-of-sales.edit', $data);
    }

    public function update(UpdatePointOfSaleRequest $request, PointOfSale $pointOfSale): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $pointOfSale);

        $pointOfSale = $this->pointOfSales->update($pointOfSale, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Punto de venta actualizado correctamente.',
                'data' => ['id' => $pointOfSale->id],
            ]);
        }

        return redirect()
            ->route('point-of-sales.index')
            ->with('success', 'Punto de venta actualizado correctamente.');
    }

    public function destroy(PointOfSale $pointOfSale): RedirectResponse
    {
        $this->authorize('delete', $pointOfSale);

        $this->pointOfSales->delete($pointOfSale);

        return redirect()
            ->route('point-of-sales.index')
            ->with('success', 'Punto de venta eliminado correctamente.');
    }

    private function formData(): array
    {
        return [
            'branches' => $this->branches->active(),
            'users' => User::query()
                ->where('is_active', true)
                ->when(CompanyContext::id(), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'warehouses' => $this->warehouses->active()
                ->groupBy('branch_id')
                ->map(fn ($items) => $items->values()),
        ];
    }
}
