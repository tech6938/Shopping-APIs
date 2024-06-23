<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;
use App\Models\Category;

class CategoryController extends Controller
{
    public function newCategory(Request $request)
{
    try{
    // Validate the request data
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|unique:categories,name',
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2000',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors' => $validator->errors(),
        ], 400);
    }

    // Get the authenticated user
    $user = Auth::user();

    // Check if the user is authenticated
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not authenticated!',
        ], 401);
    }

    // Check if the image is actually uploaded
    if (!$request->hasFile('image')) {
        return response()->json([
            'success' => false,
            'message' => 'No image file found in the request!',
        ], 400);
    }

    // Handle the image upload
    $img = $request->file('image');
    $ext = $img->getClientOriginalExtension();
    $imageName = time().'.'.$ext;
    $img->move(public_path('/uploads/'), $imageName);

    // Create a new category
    $category = new Category();
    $category->name = $request->name;
    $category->image = $imageName;
    $category->save();

    return response()->json([
        'success' => true,
        'message' => 'Category created successfully!',
        'data' => $category,
        'image_path' => asset('uploads/'.$imageName),
    ], 200);
}catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'message' => $e->getMessage(),
    ], $e->getCode());
}
}
// NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN  FOR ALL CATEGORY
    public function allCategory(){
        try{
        $user = Auth::user();

        $allCat =   Category::all();
        return response()->json([
            'status'    =>  true,
            'message'   => 'Retrieved All Categories Successfully!',
            'Categories'  =>    $allCat,
            // 'image_path' => asset('uploads/'.$allCat->image),
        ]);
    }catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode());
    }
    }
}
