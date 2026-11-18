<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function landing()
    {
        return Inertia::render('customer/landing');
    }

    public function index(Request $request)
    {
        $selectedBranch = $this->resolveSelectedBranch($request);
        $branchId = $selectedBranch?->id;
        $query = Product::with(['category', 'photos', 'customizations.options', 'branches:id'])
            ->where('is_active', true);

        // Apply search filter
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('category', function ($q2) use ($searchTerm) {
                        $q2->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        // Apply category filter
        if ($request->has('category') && $request->category && $request->category !== 'all') {
            if (is_numeric($request->category)) {
                $query->where('category_id', $request->category);
            } else {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category)->orWhere('name', $request->category);
                });
            }
        }

        // Paginate results
        $perPage = 12;
        $products = $query->latest()->paginate($perPage);
        $products->getCollection()->each(function (Product $product) use ($branchId): void {
            $product->setAttribute('is_available', $product->isAvailableAt($branchId));
        });

        $categories = Category::all();
        $branches = Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        // Return JSON for AJAX requests (infinite scroll) - only for requests with page parameter.
        // Exclude Inertia visits (X-Inertia header) since axios also sets X-Requested-With.
        if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax()) && $request->has('page') && $request->page > 1) {
            return response()->json([
                'products' => $products->items(),
                'selectedBranch' => $branchId,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'has_more_pages' => $products->hasMorePages(),
                ],
            ]);
        }

        // Return Inertia page for initial load and redirects
        return Inertia::render('customer/index', [
            'products' => $products->items(),
            'categories' => $categories,
            'branches' => $branches,
            'selectedBranch' => $branchId,
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more_pages' => $products->hasMorePages(),
            ],
        ]);
    }

    public function show(Product $product, Request $request)
    {
        abort_unless($product->is_active, 404);
        $selectedBranch = $this->resolveSelectedBranch($request);
        $product->load(['category', 'photos', 'customizations.options', 'branches:id']);
        $product->setAttribute('is_available', $product->isAvailableAt($selectedBranch?->id));

        return Inertia::render('customer/index', [
            'selectedProduct' => $product,
            'products' => [$product],
            'categories' => Category::all(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'selectedBranch' => $selectedBranch?->id,
        ]);
    }

    private function resolveSelectedBranch(Request $request): ?Branch
    {
        if ($request->filled('branch_id')) {
            $branch = Branch::whereKey($request->integer('branch_id'))
                ->where('is_active', true)
                ->firstOrFail();
            $request->session()->put('customer_branch_id', $branch->id);

            return $branch;
        }

        $branchId = $request->session()->get('customer_branch_id');
        $branch = $branchId
            ? Branch::whereKey($branchId)->where('is_active', true)->first()
            : null;

        if (! $branch) {
            $branch = Branch::where('is_active', true)->orderBy('id')->first();
        }

        if ($branch) {
            $request->session()->put('customer_branch_id', $branch->id);
        }

        return $branch;
    }
}
