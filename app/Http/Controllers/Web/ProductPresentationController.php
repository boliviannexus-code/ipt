<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductPresentationRequest;
use App\Http\Requests\UpdateProductPresentationRequest;
use App\Models\Presentation;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductPresentationController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('product-presentations.view'), 403);

        return view('product-presentations.index');
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('product-presentations.create'), 403);

        return view($request->ajax() ? 'product-presentations.partials.create-form' : 'product-presentations.create');
    }

    public function store(StoreProductPresentationRequest $request): JsonResponse|RedirectResponse
    {
        $presentation = Presentation::query()->create(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presentacion creada correctamente.',
                'data' => ['id' => $presentation->id],
            ], 201);
        }

        return redirect()->route('product-presentations.index')->with('success', 'Presentacion creada correctamente.');
    }

    public function show(Request $request, Presentation $productPresentation): View
    {
        abort_unless(auth()->user()?->can('product-presentations.view'), 403);
        abort_unless(CompanyContext::belongsToUser($productPresentation->company_id, $request->user()), 403);

        return view($request->ajax() ? 'product-presentations.partials.show' : 'product-presentations.show', compact('productPresentation'));
    }

    public function edit(Request $request, Presentation $productPresentation): View
    {
        abort_unless(auth()->user()?->can('product-presentations.update'), 403);
        abort_unless(CompanyContext::belongsToUser($productPresentation->company_id, $request->user()), 403);

        return view($request->ajax() ? 'product-presentations.partials.edit-form' : 'product-presentations.edit', compact('productPresentation'));
    }

    public function update(UpdateProductPresentationRequest $request, Presentation $productPresentation): JsonResponse|RedirectResponse
    {
        abort_unless(CompanyContext::belongsToUser($productPresentation->company_id, $request->user()), 403);

        $productPresentation->update(CompanyContext::applyToData($request->validated(), $request->user()));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Presentacion actualizada correctamente.',
                'data' => ['id' => $productPresentation->id],
            ]);
        }

        return redirect()->route('product-presentations.index')->with('success', 'Presentacion actualizada correctamente.');
    }

    public function destroy(Presentation $productPresentation): RedirectResponse
    {
        abort_unless(auth()->user()?->can('product-presentations.delete'), 403);
        abort_unless(CompanyContext::belongsToUser($productPresentation->company_id, auth()->user()), 403);

        abort_if($productPresentation->inventoryMovements()->exists(), 422, 'No se puede eliminar una presentacion con movimientos asociados.');

        $productPresentation->delete();

        return redirect()->route('product-presentations.index')->with('success', 'Presentacion eliminada correctamente.');
    }
}
