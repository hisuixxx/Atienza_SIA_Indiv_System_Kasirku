<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CustomizationOption;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please log in to view your shopping cart.');
        }

        $cart = Cart::with(['items.product.category'])
            ->firstOrCreate(['user_id' => Auth::id()]);

        return view('customer.cart.index', compact('cart'));
    }

    /**
     * Add a customized product to the shopping cart.
     */
    public function add(Request $request, Product $product): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Please sign in or create an account to customize and order drinks.');
        }

        if (!$product->is_available) {
            return back()->with('error', 'This item is currently sold out and cannot be added to your cart.');
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
            'special_instructions' => ['nullable', 'string', 'max:500'],
            'customizations' => ['nullable', 'array'],
        ]);

        $quantity = (int) $request->input('quantity', 1);
        $rawCustomizations = $request->input('customizations', []);

        // Compute actual additional charges from database to ensure price integrity
        $customizationsSummary = [];
        $totalAddOns = 0.00;

        if (!empty($rawCustomizations)) {
            foreach ($rawCustomizations as $groupName => $optionName) {
                // Find matching option in the product's customization hierarchy
                $option = CustomizationOption::whereHas('customization', function ($q) use ($product, $groupName) {
                    $q->where('product_id', $product->id)
                      ->where('name', $groupName);
                })->where('name', $optionName)->first();

                $addPrice = $option ? (float) $option->additional_price : 0.00;
                $totalAddOns += $addPrice;

                $customizationsSummary[$groupName] = [
                    'option' => $optionName,
                    'price' => $addPrice,
                ];
            }
        }

        $unitPrice = (float) $product->base_price + $totalAddOns;
        $itemSubtotal = $unitPrice * $quantity;

        $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'customizations' => $customizationsSummary,
            'special_instructions' => $request->input('special_instructions'),
            'item_subtotal' => $itemSubtotal,
        ]);

        return redirect()->route('cart.index')->with('success', "{$product->name} added to your order!");
    }

    /**
     * Update quantity of a cart item.
     */
    public function update(Request $request, CartItem $cartItem): RedirectResponse
    {
        if (!Auth::check() || $cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $action = $request->input('action');

        if ($action === 'increase') {
            if ($cartItem->quantity < 20) {
                $cartItem->quantity += 1;
                $cartItem->item_subtotal = $cartItem->unit_price * $cartItem->quantity;
                $cartItem->save();
            }
        } elseif ($action === 'decrease') {
            if ($cartItem->quantity > 1) {
                $cartItem->quantity -= 1;
                $cartItem->item_subtotal = $cartItem->unit_price * $cartItem->quantity;
                $cartItem->save();
            } else {
                $cartItem->delete();
                return back()->with('success', 'Item removed from your cart.');
            }
        }

        return back()->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(CartItem $cartItem): RedirectResponse
    {
        if (!Auth::check() || $cartItem->cart->user_id !== Auth::id()) {
            abort(403);
        }

        $productName = $cartItem->product->name ?? 'Item';
        $cartItem->delete();

        return back()->with('success', "{$productName} removed from your cart.");
    }
}
