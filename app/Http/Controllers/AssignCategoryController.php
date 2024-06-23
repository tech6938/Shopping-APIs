<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Category;
use App\Models\AssignCategory;

class AssignCategoryController extends Controller
{
    public function assignCategoriesToRetailers(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'user_id.*' => 'required|exists:users,id',
                'category_id.*' => 'required|exists:categories,id',
            ]);

            $userIds = $request->input('user_id');
            $categoryIds = $request->input('category_id');

            // Ensure $userIds and $categoryIds are arrays
            $userIds = is_array($userIds) ? $userIds : [];
            $categoryIds = is_array($categoryIds) ? $categoryIds : [];

            // Initialize arrays to keep track of assignments, already assigned records, and errors
            $assignments = [];
            $alreadyAssigned = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($userIds as $userId) {
                foreach ($categoryIds as $categoryId) {
                    try {
                        // Check if the record already exists
                        $assignCategory = AssignCategory::where('user_id', $userId)
                                                        ->where('category_id', $categoryId)
                                                        ->first();

                        if ($assignCategory) {
                            // Track already assigned records
                            $alreadyAssigned[] = [
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                                'status' => 'already assigned'
                            ];
                        } else {
                            // Create a new assignment
                            $assignCategory = new AssignCategory([
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                            ]);

                            // Save the new assignCategory
                            $assignCategory->save();

                            // Track successful assignments
                            $assignments[] = [
                                'user_id' => $userId,
                                'category_id' => $categoryId,
                                'status' => 'assigned'
                            ];
                        }
                    } catch (\Exception $e) {
                        // Track errors for specific assignments
                        $errors[] = [
                            'user_id' => $userId,
                            'category_id' => $categoryId,
                            'error' => $e->getMessage()
                        ];
                    }
                }
            }

            if (count($errors) > 0) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some assignments failed to save',
                    'errors' => $errors,
                    'data' => array_merge($assignments, $alreadyAssigned),
                ], 207); // Multi-Status HTTP code
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Category Assignments Processed Successfully',
                'data' => array_merge($assignments, $alreadyAssigned),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNN GET ASSIGN CATEGORY NNNNNNNNNNNNNNNNNN
    public function getAssigningCategory($user_id)
{
    // Fetch the assigned categories for the user
    $assignCategories = AssignCategory::with('category')->where('user_id', $user_id)->get();

    // Check if there are no assigned categories
    if ($assignCategories->isEmpty()) {
        return response()->json(['error' => 'Categories not found'], 404);
    }

    // Prepare an array to store category details
    $categories = [];

    // Iterate through the assigned categories and collect the required details
    foreach ($assignCategories as $assignCategory) {
        if ($assignCategory->category) {
            $categories[] = [
                'name' => $assignCategory->category->name,
                'image' => $assignCategory->category->image,
            ];
        }
    }

    return response()->json([
        'success' => true,
        'categories' => $categories,
    ]);
}

}
