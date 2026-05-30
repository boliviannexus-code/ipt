<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccommodationCatalogRequest;
use App\Http\Requests\Admin\UpdateAccommodationCatalogRequest;
use App\Support\AccommodationCatalogRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccommodationCatalogController extends Controller
{
    public function index(string $catalog): View
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $modelClass = $metadata['model'];
        $usageRelation = $metadata['usage_relation'];

        $records = $modelClass::withTrashed()
            ->withCount([$usageRelation.' as usage_count'])
            ->orderByRaw('sort_order is null')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20);

        return view('admin.accommodation-catalogs.index', [
            'catalog' => $catalog,
            'catalogs' => AccommodationCatalogRegistry::all(),
            'metadata' => $metadata,
            'records' => $records,
        ]);
    }

    public function create(Request $request, string $catalog): View
    {
        $data = $this->viewData($catalog);

        if ($request->ajax()) {
            return view('admin.accommodation-catalogs.partials.create-form', $data);
        }

        return view('admin.accommodation-catalogs.create', $data);
    }

    public function store(StoreAccommodationCatalogRequest $request, string $catalog): JsonResponse|RedirectResponse
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $modelClass = $metadata['model'];

        $record = $modelClass::create($this->catalogData($request->validated(), (bool) $metadata['has_capacity']));

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $metadata['singular'].' creado correctamente.',
                'data' => ['id' => $record->id],
            ], 201);
        }

        return redirect()
            ->route('admin.accommodation-catalogs.index', $catalog)
            ->with('success', $metadata['singular'].' creado correctamente.');
    }

    public function edit(Request $request, string $catalog, int $record): View
    {
        $record = AccommodationCatalogRegistry::resolveRecord($catalog, $record);
        abort_if($record->trashed(), 404);

        $data = $this->viewData($catalog, $record);

        if ($request->ajax()) {
            return view('admin.accommodation-catalogs.partials.edit-form', $data);
        }

        return view('admin.accommodation-catalogs.edit', $data);
    }

    public function update(UpdateAccommodationCatalogRequest $request, string $catalog, int $record): JsonResponse|RedirectResponse
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $record = AccommodationCatalogRegistry::resolveRecord($catalog, $record);
        abort_if($record->trashed(), 404);

        $data = $this->catalogData($request->validated(), (bool) $metadata['has_capacity']);
        $this->ensureProtectedRecordStaysActive($catalog, $record, (bool) $data['is_active']);

        $record->update($data);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $metadata['singular'].' actualizado correctamente.',
                'data' => ['id' => $record->id],
            ]);
        }

        return redirect()
            ->route('admin.accommodation-catalogs.index', $catalog)
            ->with('success', $metadata['singular'].' actualizado correctamente.');
    }

    public function toggle(string $catalog, int $record): RedirectResponse
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $record = AccommodationCatalogRegistry::resolveRecord($catalog, $record);
        abort_if($record->trashed(), 404);

        $nextState = ! (bool) $record->is_active;
        $this->ensureProtectedRecordStaysActive($catalog, $record, $nextState);

        $record->update(['is_active' => $nextState]);

        return redirect()
            ->route('admin.accommodation-catalogs.index', $catalog)
            ->with('success', $metadata['singular'].' '.($nextState ? 'activado' : 'deshabilitado').' correctamente.');
    }

    public function destroy(string $catalog, int $record): RedirectResponse
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $record = AccommodationCatalogRegistry::resolveRecord($catalog, $record);
        abort_if($record->trashed(), 404);

        if (AccommodationCatalogRegistry::isProtected($catalog, $record)) {
            return back()->with('error', 'Este registro base no se puede eliminar.');
        }

        if ($this->usageCount($catalog, $record) > 0) {
            return back()->with('error', 'No se puede eliminar porque ya esta siendo usado.');
        }

        $record->delete();

        return redirect()
            ->route('admin.accommodation-catalogs.index', $catalog)
            ->with('success', $metadata['singular'].' eliminado correctamente.');
    }

    public function restore(string $catalog, int $record): RedirectResponse
    {
        $metadata = AccommodationCatalogRegistry::get($catalog);
        $record = AccommodationCatalogRegistry::resolveRecord($catalog, $record);
        abort_unless($record->trashed(), 404);

        $record->restore();

        return redirect()
            ->route('admin.accommodation-catalogs.index', $catalog)
            ->with('success', $metadata['singular'].' restaurado correctamente.');
    }

    private function viewData(string $catalog, ?Model $record = null): array
    {
        return [
            'catalog' => $catalog,
            'catalogs' => AccommodationCatalogRegistry::all(),
            'metadata' => AccommodationCatalogRegistry::get($catalog),
            'record' => $record,
        ];
    }

    private function catalogData(array $data, bool $hasCapacity): array
    {
        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => $data['sort_order'] ?? null,
        ];

        if ($hasCapacity) {
            $payload['capacity'] = $data['capacity'];
        }

        return $payload;
    }

    private function ensureProtectedRecordStaysActive(string $catalog, Model $record, bool $nextState): void
    {
        if (! $nextState && AccommodationCatalogRegistry::isProtected($catalog, $record)) {
            throw ValidationException::withMessages([
                'is_active' => 'Este registro base no se puede deshabilitar.',
            ]);
        }
    }

    private function usageCount(string $catalog, Model $record): int
    {
        $relation = AccommodationCatalogRegistry::get($catalog)['usage_relation'];

        return $record->{$relation}()->count();
    }
}
