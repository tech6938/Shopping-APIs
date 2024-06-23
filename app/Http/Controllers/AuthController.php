<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\User;

class AuthController extends Controller
{
    public function newUser(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users', // Ensure the table name is correct
                'password' => 'required|string|min:6',
                'c_password' => 'required|same:password',
                'role' => 'required|in:admin,retailer,cashier'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $userID = Str::random(8);
            //create new user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'userID' => $userID,
                'password' => bcrypt($request->password),
            ]);
            //token
            $token = $user->createToken('authToken')->plainTextToken;
            return response()->json([
                'message' => 'User created successfully',
                'data'   =>  $user,
                'token'   =>  $token,

            ]);
        }catch (\Exception $e) {
            $statusCode = is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() < 600 ? $e->getCode() : 500;
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN LOGIN NNNNNNNNNNNNNNNNNNNNNNNNNNNNN
    public function loginUser(Request $req)
{
    try{
        $credentials = $req->only('email', 'password');


        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $user->tokens()->delete();
            $token = $user->createToken('authToken')->plainTextToken;


            return response()->json([
                'message' => 'Login Successful!',
                'code' => 200,
                'data' => $user,
                'token' => $token,
            ]);
        } else {
            return response()->json([
                'message' => 'Enter Valid Data!',
                'code' => 400
            ]);
        }
    }catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode());
    }
}

// NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN Get all users with role 'retailer' NNNN
public function showRetailers(Request $request)
{
    try{
        // Check if the authenticated user is an admin
    $user = Auth::user();
    if ($user->role !== 'admin') {
        return response()->json(['message' => 'You Are Not an Admin!'], 403);
    }

    // Get all users with role 'retailer'
    $retailers = User::where('role', 'retailer')->get(['id','name', 'email']);

    return response()->json([
        'status' => true,
        'message' => 'Retailers retrieved successfully',
        'data' => $retailers,
    ]);
    }catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getCode());
    }
}
    //NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN GET USER DETAILS WITH EMAIL
    public function getUserDetails(Request $request)
    {
        try{
            $email = $request->user()->email;
        $user = User::where('email', $email)->first();
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Read User Details!',
            'user' => $user,
            'token' => $token,
        ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    // NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN LOGOUT NNNNNNNNNNNNNNNNNN
    public function Logout(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'You Are Not Authorized!'], 403);
        }
        $user->tokens->each(function($token) {
            $token->delete();
        });
        return response()->json(['success' => true, 'message' => "Logout Successful"], 200);
    }
}
