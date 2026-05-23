<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseRequest;
use App\Http\Requests\UpdateWarehouseRequest;
use App\Models\Warehouse;
use App\Services\BranchService;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouses,
        private readonly BranchService $branches
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Warehouse::class);

        return view('warehouses.index', [
            'warehouses' => $this->warehouses->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Warehouse::class);

        $data = ['branches' => $this->branches->active()];

        if ($request->ajax()) {
            return view('warehouses.partials.create-form', $data);
        }

        return view('warehouses.create', $data);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse|RedirectResponse
    {
        $warehouse = $this->warehouses->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Almacen creado correctamente.',
                'data' => [
                    'id' => $warehouse->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Almacen creado correctamente.');
    }

    public function show(Request $request, Warehouse $warehouse): View
    {
        $this->authorize('view', $warehouse);

        $warehouse->load(['branch', 'company']);

        if ($request->ajax()) {
            return view('warehouses.partials.show', compact('warehouse'));
        }

        return view('warehouses.show', compact('warehouse'));
    }

    public function edit(Request $request, Warehouse $warehouse): View
    {
        $this->authorize('update', $warehouse);

        $data = [
            'warehouse' => $warehouse,
            'branches' => $this->branches->active(),
        ];

        if ($request->ajax()) {
            return view('warehouses.partials.edit-form', $data);
        }

        return view('warehouses.edit', $data);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $warehouse);

        $warehouse = $this->warehouses->update($warehouse, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Almacen actualizado correctamente.',
                'data' => [
                    'id' => $warehouse->id,
                ],
            ]);
        }

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Almacen actualizado correctamente.');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $this->authorize('delete', $warehouse);

        $this->warehouses->delete($warehouse);

        return redirect()
            ->route('warehouses.index')
            ->with('success', 'Almacen eliminado correctamente.');
    }
}
