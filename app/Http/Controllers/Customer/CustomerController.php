<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StorefrontSetting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function home(): View
    {
        $featuredProducts = Product::with('category')
            ->where('is_available', true)
            ->where('is_featured', true)
            ->take(6)
            ->get();

        $categories = Category::where('is_active', true)
            ->orderBy('display_order')
            ->take(8)
            ->get();

        $settings = StorefrontSetting::all()->pluck('value', 'key');

        return view('customer.home', compact('featuredProducts', 'categories', 'settings'));
    }

    public function menu(Request $request): View
    {
        $query = Product::with('category')->where('is_available', true);

        $selectedCategory = null;
        if ($request->filled('category')) {
            $selectedCategory = Category::where('slug', $request->category)->first();
            if ($selectedCategory) {
                $query->where('category_id', $selectedCategory->id);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $categories = Category::where('is_active', true)->orderBy('display_order')->get();

        return view('customer.menu.index', compact('products', 'categories', 'selectedCategory'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'customizations.options']);

        $relatedProducts = Product::with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_available', true)
            ->take(3)
            ->get();

        return view('customer.menu.show', compact('product', 'relatedProducts'));
    }
}
