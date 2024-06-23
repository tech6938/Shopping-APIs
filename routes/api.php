<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AssignCategoryController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware('auth:sanctum')->group(function () {

// PRODUCT CONTROLLER
Route::post('/create-product', [ProductController::class, 'productCreate']);
Route::post('/assign-category', [ProductController::class, 'assignCategoryToProducts']);
Route::get('/show-product/{id}', [ProductController::class, 'ShowProductDetails']);
Route::get('/all-products', [ProductController::class, 'AllProducts']);

// Checkout CONTROLLER
Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::post('/checkout-status/{id}', [CheckoutController::class, 'adminAction']);

// AUTH CONTROLLER (Show all details of retailer to admin)
Route::get('/retailers', [AuthController::class, 'showRetailers']);
Route::get('/user-detail', [AuthController::class, 'getUserDetails']);

//CATEGORY CONTROLLER
Route::post('/create-category', [CategoryController::class, 'newCategory']);
Route::get('/all-category', [CategoryController::class, 'allCategory']); // show all categories

// ADMIN CONTROLLER
Route::post('/assign-products', [AdminController::class, 'assignProductsToRetailers']);
Route::get('/get/product/{user_id}', [AdminController::class, 'getAssigningDetails']);
Route::get('/all-pending-orders', [AdminController::class, 'allPendingOrders']);
Route::post('/update-order-status/{orderId}', [AdminController::class, 'updateOrderStatus']);
Route::post('/update-payment/{orderId}', [AdminController::class, 'updatePaymentMethod']);

// CART CONTROLLER
Route::post('/add-to-cart', [CartController::class, 'cartCreate']);
Route::get('/cart-list', [CartController::class, 'cartList']);
Route::post('/checkout-action', [CartController::class, 'updateCart']);
Route::get('/pending-orders', [CartController::class, 'pendingOrders']);
Route::post('/remove-from-cart/{productID}', [CartController::class, 'removeFromCart']);

// ASSIGN CATEGORY CONTROLLER
Route::post('/assign-category', [AssignCategoryController::class, 'assignCategoriesToRetailers']);
Route::get('/get/category/{user_id}', [AssignCategoryController::class, 'getAssigningCategory']);

// for logout
Route::post('/logout', [AuthController::class, 'Logout']);
});
// AUTH CONTROLLER
Route::post('signup',[AuthController::class, 'newUser']);
Route::post('login',[AuthController::class, 'loginUser']);


