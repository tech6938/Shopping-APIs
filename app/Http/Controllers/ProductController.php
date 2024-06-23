<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    public function productCreate(Request $request)
    {
        try {
            $validate = $request->validate([
                'title' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'units' => 'required|string|min:0',
                'category_id' => 'required|integer|exists:categories,id',
                'color' => 'nullable|array',
                'color.*' => 'string|max:255',
                'storage' => 'nullable|array',
                'storage.*' => 'string|max:255',
                'screenSize' => 'nullable|string|max:255',
                'screenResolution' => 'nullable|string|max:255',
                'camera' => 'nullable|string|max:255',
                'cameraLens' => 'nullable|string|max:255',
                'Ram' => 'nullable|string|max:255',
                'processor' => 'nullable|string|max:255',
                'battery' => 'nullable|string|max:255',
                'charging' => 'nullable|string|max:255',
                'image' => 'required|array',
                'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg',
            ]);

            $product = new Product();
            $product->title = $request->title;
            $product->price = $request->price;
            $product->units = $request->units;
            $product->category_id = $request->category_id;
            $product->screenSize = $request->screenSize;
            $product->screenResolution = $request->screenResolution;
            $product->camera = $request->camera;
            $product->cameraLens = $request->cameraLens;
            $product->Ram = $request->Ram;
            $product->processor = $request->processor;
            $product->battery = $request->battery;
            $product->charging = $request->charging;

            if ($request->has('color') && !empty($request->color)) {
                $product->color = implode(',', $request->color);
            } else {
                $product->color = null;
            }

            if ($request->has('storage') && !empty($request->storage)) {
                $product->storage = implode(',', $request->storage);
            } else {
                $product->storage = null;
            }

            $imageNames = [];
            if ($request->hasFile('image')) {
                foreach ($request->file('image') as $img) {
                    $ext = $img->getClientOriginalExtension();
                    $imageName = time() . '_' . uniqid() . '.' . $ext;
                    $img->move(public_path('/uploads/'), $imageName);
                    $imageNames[] = $imageName;
                }
                $product->image = implode(',', $imageNames);
            } else {
                $product->image = null;
            }

            $product->save();

            return response()->json([
                'status' => true,
                'message' => "Product created successfully",
                'data' => $product,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    //NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN for showing product details NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN
    public function ShowProductDetails($id){
        try{
            $product = Product::find($id);
            if(!$product){
                return response()->json([
                    'message' => "No Product Found",
                    'check' => "Enter Valid ID",
                    'status' => 200,
                    // 'data' => $product,
                ]);
            }else{
                return response()->json([
                    'message' => "products details",
                    'status' => 200,
                    'data' => $product,
                ]);
            }
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
        //NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN for all users NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN
        public function AllProducts(){
            try{
                // Fetch all products
                    $products = Product::all();
                    return response()->json([
                    'message' => "All products Details!",
                    'status' => 200,
                    'data' => $products,
                ]);
            }catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getCode());
            }
        }
        //NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN SHOW PRODUCT IN CATEGORY
    public function assignCategoryToProducts(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate request data
            $validatedData = $request->validate([
                'category_id' => 'required|exists:categories,id',
                'category_id.*' => 'required|array',
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id'
            ]);

            $categoryId = $validatedData['category_id'];
            $productIds = $validatedData['product_ids'];

            // Check if the category exists
            $category = Category::find($categoryId);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found',
                ], 404);
            }

            // Assign category to products
            Product::whereIn('id', $productIds)->update(['category_id' => $categoryId]);

            return response()->json([
                'success' => true,
                'message' => 'Category assigned to products successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
