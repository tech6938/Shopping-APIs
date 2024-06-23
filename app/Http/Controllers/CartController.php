<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Cart;

class CartController extends Controller
{
    public function cartCreate(Request $request)
    {
        try {
            $user = Auth::user();
            $retailer_id = $user->id;

            $validate = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            $product = Product::findOrFail($request->product_id);
            if ($request->quantity > $product->units) {
                return response()->json([
                    'success' => false,
                    'message' => 'Requested quantity exceeds available units.',
                ], 422);
            }
            $cartItem = Cart::where('product_id', $request->product_id)
                            ->where('retailer_id', $retailer_id)
                            ->where('place_status', 'false')
                            ->first();

            if ($cartItem) {
                $cartItem->quantity += $request->quantity;
                $cartItem->save();
            } else {
                $cart = new Cart();
                $cart->product_id = $request->product_id;
                $cart->retailer_id = $retailer_id;
                $cart->quantity = $request->quantity;

                if (!$cartItem) {
                    $cart->order_id = Carbon::now()->format('YmdHis');
                } else {
                    $cart->order_id = $cartItem->order_id;
                }

                $cart->place_status = "false";
                $cart->save();
            }

            $product->units -= $request->quantity;
            $product->save();

            return response()->json([
                'status' => true,
                'message' => "Product added to cart successfully",
                'data' => $cart ?? $cartItem,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding product to cart: ' . $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN CART LIST NNNNNNNNNNNN
    public function cartList(Request $request)
    {
        try {
            $user = Auth::user();
            $retailer_id = $user->id;

            $carts = Cart::with('product')
                        ->where('retailer_id', $retailer_id)
                        ->where('place_status', 'false')
                        ->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No products in cart",
                ], 404);
            }

            $cartList = $carts->map(function ($cart) use ($user) {
                $cartArray = $cart->toArray();
                $productArray = $cartArray['product'];
                unset($cartArray['product']);
                return [
                    'quantity' => $cartArray['quantity'],
                    'datetime' => $cartArray['created_at'],
                    'place_status' => $cartArray['place_status'],
                    'title' => $productArray['title'],
                    'price' => $productArray['price'],
                    'remaing-units' => $productArray['units'],
                    'image' => $productArray['image'],
                    'name' => $user->name,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Cart list",
                'data' => $cartList,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching cart list: ' . $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN UPDATE CART NNNNNNNNNN
    public function updateCart(Request $request)
    {
        try {
            $user = Auth::user();
            $retailer_id = $user->id;
            $carts = Cart::where('retailer_id', $retailer_id)->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Carts not found",
                ], 404);
            }

            foreach ($carts as $cart) {
                $status = "Pending";
                $place_status = "True";
                $cart->status = $status;
                $cart->place_status = $place_status;
                $cart->order_date = $request->carbon::now();
                $cart->save();
            }

            return response()->json([
                'success' => true,
                'message' => "Orders Placed Successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating carts: ' . $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN PENDING ORDERS NNNNNNNNN
    public function pendingOrders(Request $request)
    {
        try {
            $user = Auth::user();
            $retailer_id = $user->id;
            $carts = Cart::with('product')
            ->where('retailer_id', $retailer_id)
            ->where('status', 'Pending')
            ->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Cart not found",
                ], 404);
            }

            $cartList = $carts->map(function ($cart) use ($user) {
                $cartArray = $cart->toArray();
                $productArray = $cartArray['product'];
                unset($cartArray['product']);
                $cashier_name = User::findOrFail($cartArray['cashier_id']);
                return [
                    'quantity' => $cartArray['quantity'],
                    'order_id' => $cartArray['order_id'],
                    'status' => $cartArray['status'],
                    'title' => $productArray['title'],
                    'price' => $productArray['price'],
                    'image' => $productArray['image'],
                    'name' => $user->name,
                    'payment_method' => $cartArray['payment_method'],
                    'payment_date' => $cartArray['payment_date'],
                    'cashier' => $cashier_name->name,
                    'order_date' => $cartArray['order_date']
                ];
            });

            return response()->json([
                'success' => true,
                'message' => "Pending Orders",
                'data' => $cartList,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching pending orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN REMOVE FROM CART
    public function removeFromCart(Request $request, $productID)
    {
        try {
            $user = Auth::user();
            $retailer_id = $user->id;

            $product = Product::findOrFail($productID);

            $cartItem = Cart::where('product_id', $productID)
                            ->where('retailer_id', $retailer_id)
                            ->where('place_status', 'false')
                            ->first();

            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found.',
                ], 404);
            }

            $product->units += $cartItem->quantity;
            $product->save();

            $cartItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product removed from cart successfully.',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing product from cart: ' . $e->getMessage(),
            ], 500);
        }
    }
}
