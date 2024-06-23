<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Product;
use App\Models\Retailer;
use App\Models\Category;
use App\Models\Cart;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function assignProductsToRetailers(Request $request)
{
    try {
        // Validate input
        $request->validate([
            'product_id.*' => 'required|exists:products,id',
            'user_id.*' => 'required|exists:users,id',
            'category_id.*' => 'required|exists:categories,id', // Assuming multiple category IDs are provided
        ]);

        $productIds = $request->input('product_id');
        $userIds = $request->input('user_id');
        $categoryIds = $request->input('category_id');

        // Ensure $productIds, $userIds, and $categoryIds are arrays
        $productIds = $productIds ?? [];
        $userIds = $userIds ?? [];
        $categoryIds = $categoryIds ?? [];

        // Initialize arrays to keep track of assignments and errors
        $assignments = [];
        $errors = [];

        if (is_array($productIds) && is_array($userIds) && is_array($categoryIds)) {
            foreach ($productIds as $productId) {
                foreach ($userIds as $userId) {
                    foreach ($categoryIds as $categoryId) {
                        try {
                            $retailerProduct = Retailer::firstOrNew([
                                'product_id' => $productId,
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                            ]);

                            // Save the retailerProduct
                            $retailerProduct->save();

                            // Track successful assignments
                            $assignments[] = [
                                'product_id' => $productId,
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                                'status' => 'assigned'
                            ];
                        } catch (\Exception $e) {
                            // Track errors for specific assignments
                            $errors[] = [
                                'product_id' => $productId,
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                                'error' => $e->getMessage()
                            ];
                        }
                    }
                }
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => '$productIds, $userIds, and $categoryIds must be arrays.'
            ], 400);
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some assignments failed to save',
                'errors' => $errors,
                'data' => $assignments,
            ], 207); // Multi-Status HTTP code
        }

        return response()->json([
            'success' => true,
            'message' => 'Product assignments saved successfully',
            'data' => $assignments,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
}

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN ASSIGNING PRODUCT AND USER DETAILS NNNNNNNNNNNNNNNNNNNNN
    public function getAssigningDetails($user_id){
        try {
            // Fetch user data with assigned products
            $user = User::with('products')->find($user_id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Extract products assigned to the user
            $assignedProducts = $user->products;

            // Return user data and assigned products
            return response()->json([
                'user' => $user,
                'assigned_products' => $assignedProducts,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN PENDING ORDERS NNNNNNNNNNNNNNN
    public function AllpendingOrders(Request $request)
    {
        try {
            $user = Auth::user();
            $carts = Cart::with('product')
            ->whereNotNull('status')
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
                $retailer_name = User::findOrFail($cartArray['retailer_id']);
                $cashier_name = User::findOrFail($cartArray['cashier_id']);
                return [
                    'quantity' => $cartArray['quantity'],
                    'order_id' => $cartArray['order_id'],
                    'status' => $cartArray['status'],
                    'title' => $productArray['title'],
                    'price' => $productArray['price'],
                    'image' => $productArray['image'],
                    'name' => $retailer_name->name,
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

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN UPDATE ORDER STATUS NNNNNNNNNNNNNNN
    public function updateOrderStatus(Request $request, $orderId)
    {
        try {
            $request->validate([
                'status' => 'required|in:Accept,Reject,Pending,Return',
            ]);

            $newStatus = $request->input('status');
            $carts = Cart::where('order_id', $orderId)->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            foreach ($carts as $cart) {
                $cart->status = $newStatus;
                $cart->save();
            }

            $responseData = [
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => [],
            ];

            $lastCart = $carts->last();
            $responseData['data']['order_id'] = $lastCart->order_id;
            $responseData['data']['status'] = $lastCart->status;

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status: ' . $e->getMessage(),
            ], 500);
        }
    }
    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN UPDATE PAYMENT METHOD AND DATE NNNNNNNNNNNNNNN
    public function updatePaymentMethod(Request $request, $orderId)
    {
        try {
            $user = Auth::user();
            $cashier_id = $user->id;

            $request->validate([
                'payment_method' => 'required',
            ]);

            $payment_method = $request->input('payment_method');
            $carts = Cart::where('order_id', $orderId)->get();

            if ($carts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found',
                ], 404);
            }

            foreach ($carts as $cart) {
                $cart->payment_method = $payment_method;
                $cart->payment_date = carbon::now();
                $cart->cashier_id = $cashier_id;
                $cart->save();
            }

            $responseData = [
                'success' => true,
                'message' => 'Payment Added successfully',
                'data' => [],
            ];

            $lastCart = $carts->last();
            $responseData['data']['order_id'] = $lastCart->order_id;
            $responseData['data']['payment_method'] = $lastCart->payment_method;

            return response()->json($responseData, 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment method: ' . $e->getMessage(),
            ], 500);
        }
    }
}
