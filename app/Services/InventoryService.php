<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    /** Deduct all configured recipe ingredients exactly once for an order. */
    public function deductForOrder(Order $order): void
    {
        if (! $order->branch_id || DB::table('inventory_transactions')->where('order_id', $order->id)->where('type', 'deduction')->exists()) {
            return;
        }

        $requirements = DB::table('order_items')
            ->join('product_recipes', 'order_items.product_id', '=', 'product_recipes.product_id')
            ->where('order_items.order_id', $order->id)
            ->select('product_recipes.ingredient_id', DB::raw('SUM(product_recipes.quantity * order_items.quantity) as required'))
            ->groupBy('product_recipes.ingredient_id')
            ->orderBy('product_recipes.ingredient_id')
            ->get();

        foreach ($requirements as $requirement) {
            $stock = DB::table('inventory')
                ->where('branch_id', $order->branch_id)
                ->where('ingredient_id', $requirement->ingredient_id)
                ->lockForUpdate()
                ->first();

            if (! $stock || (float) $stock->quantity < (float) $requirement->required) {
                $ingredient = DB::table('ingredients')->where('id', $requirement->ingredient_id)->value('name') ?? 'Ingredient';
                throw ValidationException::withMessages(['items' => "Insufficient stock for {$ingredient}."]);
            }
        }

        foreach ($requirements as $requirement) {
            DB::table('inventory')->where('branch_id', $order->branch_id)
                ->where('ingredient_id', $requirement->ingredient_id)
                ->decrement('quantity', $requirement->required);

            DB::table('inventory_transactions')->insert([
                'branch_id' => $order->branch_id,
                'ingredient_id' => $requirement->ingredient_id,
                'order_id' => $order->id,
                'user_id' => $order->created_by,
                'type' => 'deduction',
                'quantity' => -$requirement->required,
                'reference' => $order->order_number,
                'remarks' => 'Recipe deduction at order entry',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
