<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
  public function createSubscription(Request $request, Item $item)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required',
      'start_date' => 'required|date',
      'end_date' => 'required|date',
      // $request->user()->group_id == $item->group_id
      'group_id' => 'required|exists:groups,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    // check that the user is in the same group as the item
    if (0 == $request->user()->userGroups()->where('group_id', $item->group_id)->count()) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $start_date = $request->start_date;
    $end_date = $request->end_date;

    $subscriptions = $item->subscriptions()
        ->where('status', '<>', 'canceled')
        ->where('start_date', '<', $end_date)
        ->where('end_date', '>', $start_date)
        ->get();

    if ($subscriptions->count() > 0) {
      return response()->json(['message' => "L'Item est déjà utilisé sur les dates demandées."], Response::HTTP_CONFLICT);
    }

    $subscription = $item->subscriptions()->create([
      'name' => $request->name,
      'start_date' => $request->start_date,
      'end_date' => $request->end_date,
      'user_id' => Auth::user()->id,
      'status' => 'active',
    ]);

    return response()->json($subscription, 201);
  }

  public function getSubscriptions(Request $request, Item $item)
  {
    $subscriptions = $item->subscriptions()->get();

    return response()->json($subscriptions, 200);
  }

  public function getSubscription(Request $request, Item $item, ItemSubscription $subscription)
  {
    if ('admin' != Auth::user()->role && Auth::user()->id != $subscription->user_id) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    if ($subscription->item_id != $item->id) {
      return response()->json(['message' => 'Subscription not found'], 404);
    }

    return response()->json($subscription);
  }

  // function getICS(Request $request)
  // {
    //     // get all the subscriptions of the user
    //     $subscriptions = ItemSubscription::where("start_date", ">=", "NOW()")->get();
    //     Log::info($subscriptions);

    //     $ics_file =
    //         $subscriptions->map(function ($subscription) {
    //             return new ICS([
    //                 'start_date' => $subscription->start_date,
    //                 'end_date' => $subscription->end_date,
    //                 'summary' => $subscription->name,

    //                 'uid' => $subscription->id,
    //                 'sequence' => 0,
    //                 'dtstart' => $subscription->start_date,
    //                 'dtend' => $subscription->end_date,
    //             ]);
    //         })->map(function ($ics) {
    //             return $ics->toString();
    //         })->implode("\n");

    //     return response($ics_file, 200, [
    //         'Content-Type' => 'text/calendar',
    //         'Content-Disposition' => 'attachment; filename="calendar.ics"'
    //     ]);

    //     return response()->json([], 200);
  // }
}
