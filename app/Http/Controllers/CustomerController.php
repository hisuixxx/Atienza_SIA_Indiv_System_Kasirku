<?php

namespace App\Http\Controllers;

use App\Models\Category;
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
        $query = Product::with(['category', 'photos', 'customizations.options']);

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

        $categories = Category::all();

        // Return JSON for AJAX requests (infinite scroll) - only for requests with page parameter.
        // Exclude Inertia visits (X-Inertia header) since axios also sets X-Requested-With.
        if (! $request->header('X-Inertia') && ($request->wantsJson() || $request->ajax()) && $request->has('page') && $request->page > 1) {
            return response()->json([
                'products' => $products->items(),
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
            'pagination' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'has_more_pages' => $products->hasMorePages(),
            ],
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'photos', 'customizations.options']);

        return Inertia::render('customer/index', [
            'selectedProduct' => $product,
            'products' => [$product],
            'categories' => Category::all(),
        ]);
    }
}
