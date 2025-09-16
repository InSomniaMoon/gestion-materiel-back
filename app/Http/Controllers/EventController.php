<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Validator;

class EventController extends Controller
{
  public function getEventsForUserForStructure(Request $request)
  {
    $structure_id = $request->input('structure_id');

    $events = Event::
      with(['structure:id,color'])
      ->where(function ($query) use ($structure_id) {
        $query
          ->whereHas('structure', function ($q) use ($structure_id) {
            $q->where('structure_id', $structure_id);
          })
          ->where('start_date', '>=', now())
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

    // Attach materials to the event (array of item IDs)
    $itemIdsAndQuantities = collect($request->input('materials', []))
      ->map(function ($m) {
        if (is_array($m) && isset($m['id'])) {
          return [$m['id'] => ['quantity' => $m['quantity'] ?? 1]];
        }

        return null;
      })
      ->filter()
      ->reduce(
        fn ($carry, $item) => $carry + $item,
        []
      );
    if (! empty($itemIdsAndQuantities)) {
      $event->eventSubscriptions()->sync($itemIdsAndQuantities);
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
      ->whereHas('structure', function ($query) use ($request) {
        $query->where('user_id', $request->user()->id);
      })
      ->where('start_date', '<=', now())
      ->where('end_date', '>=', now())
      ->get();

    return response()->json($events);
  }

  public function getEventById(Request $request, Event $event)
  {
    if (! $this->has_user_access_toEvent($request, $event)) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $event
      ->load([
        'unit',
        'eventSubscriptions' => function ($query) {
          $query
            ->select('items.id', 'items.name', 'items.category_id', 'quantity')
            ->with(['options', 'category'])
            ->orderBy('name')
            ->orderBy('category_id');
        },
      ]);

    return response()->json($event);
  }

  public function delete(Request $request, Event $event)
  {
    if (! $this->has_user_access_toEvent($request, $event)) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    $event->delete();

    return response()->json(null, 204);
  }

  public function update(Request $request, Event $event)
  {
    $request->validate([
      'unit_id' => 'required|integer|exists:units,id',
      'name' => 'required|string|max:255',
      'start_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"',
      'end_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"|after_or_equal:start_date',
      'materials' => 'array',
      'materials.*.id' => 'integer|exists:items,id',
      'comment' => 'nullable|string|max:500',
    ]);

    $event->name = $request->input('name');
    $event->start_date = $request->input('start_date');
    $event->end_date = $request->input('end_date');
    $event->comment = $request->input('comment');

    // Sync materials as item IDs
    $itemIds = collect($request->input('materials', []))
      ->map(function ($m) {
        if (is_array($m) && isset($m['id'])) {
          return [$m['id'] => ['quantity' => $m['quantity'] ?? 1]];
        }

        return null;
      })
      ->filter()
      ->reduce(fn ($carry, $item) => $carry + $item, []);
    $event->eventSubscriptions()->sync($itemIds);
    $event->save();

    return response()->json($event);
  }

  private function has_user_access_toEvent(Request $request, Event $event)
  {
    $user = $request->user();
    // le user fait partie de l'unité mais n'est pas forcément le créateur. ou le user est un admin dans le groupe de l'unité
    $structure = $event->structure()->first();
    //TODO
    $is_group_admin = $user->userStructures()->where('id', $structure->id)->where('role', 'admin')->exists();

    return $user->userStructures()->where('id', $event->structure_id)->exists() || $is_group_admin;
  }
}
