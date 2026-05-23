<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categories
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Category::class);

        return view('categories.index', [
            'categories' => $this->categories->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Category::class);

        if ($request->ajax()) {
            return view('categories.partials.create-form');
        }

        return view('categories.create');
    }

    public function store(StoreCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = $this->categories->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria creada correctamente.',
                'data' => [
                    'id' => $category->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria creada correctamente.');
    }

    public function show(Request $request, Category $category): View
    {
        $this->authorize('view', $category);

        if ($request->ajax()) {
            return view('categories.partials.show', compact('category'));
        }

        return view('categories.show', compact('category'));
    }

    public function edit(Request $request, Category $category): View
    {
        $this->authorize('update', $category);

        if ($request->ajax()) {
            return view('categories.partials.edit-form', compact('category'));
        }

        return view('categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $category);

        $category = $this->categories->update($category, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Categoria actualizada correctamente.',
                'data' => [
                    'id' => $category->id,
                ],
            ]);
        }

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria actualizada correctamente.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $this->categories->delete($category);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoria eliminada correctamente.');
    }
}
