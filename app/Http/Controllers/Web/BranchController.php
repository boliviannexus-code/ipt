<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use App\Services\BranchService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branches
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        return view('branches.index', [
            'branches' => $this->branches->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Branch::class);

        $data = ['companies' => $this->companiesForForm($request)];

        if ($request->ajax()) {
            return view('branches.partials.create-form', $data);
        }

        return view('branches.create', $data);
    }

    public function store(StoreBranchRequest $request): JsonResponse|RedirectResponse
    {
        $branch = $this->branches->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada correctamente.',
                'data' => [
                    'id' => $branch->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    public function show(Request $request, Branch $branch): View
    {
        $this->authorize('view', $branch);

        $branch->load('company')->loadCount('warehouses');

        if ($request->ajax()) {
            return view('branches.partials.show', compact('branch'));
        }

        return view('branches.show', compact('branch'));
    }

    public function edit(Request $request, Branch $branch): View
    {
        $this->authorize('update', $branch);

        if ($request->ajax()) {
            return view('branches.partials.edit-form', [
                'branch' => $branch,
                'companies' => $this->companiesForForm($request),
            ]);
        }

        return view('branches.edit', [
            'branch' => $branch,
            'companies' => $this->companiesForForm($request),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $branch);

        $branch = $this->branches->update($branch, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sucursal actualizada correctamente.',
                'data' => [
                    'id' => $branch->id,
                ],
            ]);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $this->branches->delete($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal eliminada correctamente.');
    }

    private function companiesForForm(Request $request)
    {
        $companyId = CompanyContext::id($request->user());

        if ($companyId !== null) {
            return Company::query()->whereKey($companyId)->get(['id', 'name']);
        }

        if (! CompanyContext::isGlobalAdmin($request->user())) {
            return Company::query()->whereRaw('1 = 0')->get(['id', 'name']);
        }

        return Company::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
    }
}
