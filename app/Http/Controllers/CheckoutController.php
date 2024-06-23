<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checkout;

class CheckoutController extends Controller
{
    public function checkout(Request $request){
        try{
            $validate   =   $request->validate([
                'totalProductsUnits' => 'required|string|max:255',
                'totalPriceUnits' => 'required|string|max:255',
                'totalPrice' => 'required|string|max:255',
                'totalUnits' => 'required|string|max:255',
            ]);

            $checkout = new Checkout();
            $checkout->totalProductsUnits =   $request->totalProductsUnits;
            $checkout->totalPriceUnits =   $request->totalPriceUnits;
            $checkout->totalPrice =   $request->totalPrice;
            $checkout->totalUnits =   $request->totalUnits;
            $checkout->status = 'Pending'; // Default status is pending

            $checkout->save();
            //response
            return response()->json([
                'status' => true,
                'message' => "Checkout Details created Successfully!",
                'data'  =>  $checkout,
            ]);
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
    //NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN Admin Action  NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNN
    public function adminAction(Request $request, $id){
        try{
            $checkout = Checkout::find($id);

        if(!$checkout){
            return response()->json([
                'status' => false,
                'message' => "Checkout not found!",
            ]);
        }

        // $action = $request->input('status');
        $action = $request->status;

        if($action === 'accept'){
            // Admin accepts the order
            // Update status and show details
            $checkout->status = 'accept';
            $checkout->save();

            return response()->json([
                'status' => true,
                'message' => "Checkout Accepted!",
                'data'  => [
                    'status' => $checkout->status,
                    'totalPrice' => $checkout->totalPrice,
                    'totalUnits' => $checkout->totalUnits,
                    'updated_at' => $checkout->updated_at,
                ],
            ]);
        } elseif($action === 'reject'){
            // Admin rejects the order
            // Status remains pending
            return response()->json([
                'status' => true,
                'message' => "Checkout Rejected!",
            ]);
        } else {
            // Invalid action
            return response()->json([
                'status' => false,
                'message' => "Invalid action!",
            ]);
        }
        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode());
        }

    }
}
