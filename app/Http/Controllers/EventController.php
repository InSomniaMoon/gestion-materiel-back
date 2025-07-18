<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Validator;

class EventController extends Controller
{
  //
  public function getEventsForUserForUnit(Request $request)
  {
    Validator::make($request->all(), [
      'unit_id' => 'required|integer|exists:units,id',
    ])->validate();

    // on récupère tous les événements futurs ou en cours pour l'utilisateur connecté dans l'unité spécifiée
    $user = $request->user();
    $unitId = $request->input('unit_id');
    if (! $user->units()->where('id', $unitId)->exists()) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $events = Event::where('unit_id', $unitId)
      ->where(function ($query) {
        $query->where('start_date', '>=', now())
          ->orWhere('end_date', '>=', now());
      })
      ->get();

    return response()->json($events);
  }
}
