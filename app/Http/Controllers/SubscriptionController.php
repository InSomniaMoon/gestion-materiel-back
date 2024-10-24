<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubscriptionController extends Controller
{
    //

    function createSubscription(Request $request, Item $item)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $start_date = $request->start_date;
        $end_date = $request->end_date;

        // check if the item is already subscribed on the given date
        // retrieve all the subscriptions of the item where start date is less than or equal to the given end date and end date is greater than or equal to the given start date

        $subscriptions = $item->subscriptions()
            ->where('status', '<>', 'canceled')
            ->where('start_date', '<=', $end_date)
            ->where('end_date', '>=', $start_date)
            ->get();


        if ($subscriptions->count() > 0) {
            return response()->json(['message' => 'Item is already subscribed on the given date'], 403);
        }

        $subscription =  $item->subscriptions()->create([
            'name' => $request->name,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'user_id' => Auth::user()->id,
            'status' => 'active',
        ]);

        return response()->json($subscription, 201);
    }

    function getSubscriptions(Request $request, Item $item)
    {
        return response()->json($item->subscriptions()->get(), 200);
    }
}
