<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Mockery\Matcher\MatcherInterface;
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

  public function create(Request $request)
  {
    //{"name":"test","unit_id":1,"start_date":"2025-07-19 15:00","end_date":"2025-07-20T13:00:00.000Z","materials":[{"id":101}],"comment":""}
    Validator::make($request->all(), [
      'unit_id' => 'required|integer|exists:units,id',
      'name' => 'required|string|max:255',
      'start_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"',
      'end_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"|after_or_equal:start_date',
      'materials' => 'array',
      'materials.*.id' => 'integer|exists:items,id',
      'comment' => 'nullable|string|max:500',

    ])->validate();

    $event = Event::create($request->only('unit_id', 'name', 'start_date', 'end_date') + ['user_id' => $request->user()->id]);

    // Attach materials to the event
    foreach ($request->input('materials', []) as $material) {
      if (isset($material['id'])) {
        $event->eventSubscriptions()->attach($material['id']);
      }
    }

    return response()->json($event, 201);
  }

  public function getActualEvents(Request $request)
  {
    $events = Event::
      with(relations: [
        'eventSubscriptions.options',
        'eventSubscriptions' => function ($query) {
          $query->select('id', 'name');
        },
      ])
      // with(['unit', 'eventSubscriptions', 'eventSubscriptions.options'])
      // récupère les événements en cours où au moins une unité de laquelle l'utilisateur est membre
      ->whereHas('unit', function ($query) use ($request) {
        $query->where('user_id', $request->user()->id);
      })
      ->where('start_date', '<=', now())
      ->where('end_date', '>=', now())
      ->get();

    return response()->json($events);
  }
}
