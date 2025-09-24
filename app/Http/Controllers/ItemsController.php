<?php

namespace App\Http\Controllers;

use App\Models\EventSubscription;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UserStructure;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Log;
use Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

class ItemsController extends Controller
{
  public function createItem(Request $request)
  {
    $validator = Validator::make($request->all(), Item::$validation);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $code_structure = $request->query('code_structure');

    $structure = $request->user()->userStructures()
      ->where('code_structure', $code_structure)
      ->firstOrFail();
    if (! $structure) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $item = Item::create([
      'name' => $request->name,
      'description' => $request->description,
      'category_id' => $request->category_id,
      'structure_id' => $structure->id,
      'image' => $request->image,
    ]);

    return response()->json($item, 201);
  }

  public function index(Request $request)
  {
    $isAdmin = in_array('jwt:admin', $request->route()->middleware());

    // "current_page": 1,
    $size = $request->query('size', 25);
    $page = $request->query('page', 1);
    $orderBy = $request->query('order_by', 'name');
    $orderDir = $request->query('sort_by', 'asc');
    $search = $request->query('q');
    $code_structure = $request->query('code_structure');

    $category = $request->query('category_id');

    $validator = Validator::make($request->all(), [
      'code_structure' => 'required|exists:structures,code_structure',
      'size' => 'integer|min:1|max:100',
      'page' => 'integer|min:1',
      'order_by' => 'in:name,created_at,updated_at,category_id,open_option_issues_count,state',
      'sort_by' => 'in:asc,desc',
      'category_id' => 'nullable|exists:item_categories,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    // Get all items paginated with their itemOptions

    $items = Item::whereHas('structure', function ($query) use ($code_structure) {
      $query->where('code_structure', $code_structure);
    })
      ->with(['category']);

    if ($isAdmin) {
      $items = $items->withCount([
        'issues as open_issues_count' => function ($query) {
          $query->where('item_issues.status', 'open');
        },
      ])
        ->addSelect([
          DB::raw('(CASE
            WHEN items.usable = true
              AND (SELECT COUNT(*) FROM item_issues
                   WHERE item_issues.item_id = items.id AND item_issues.status = \'open\') = 0
            THEN \'OK\'
            WHEN items.usable = true
              AND (SELECT COUNT(*) FROM item_issues
                   WHERE item_issues.item_id = items.id AND item_issues.status = \'open\') > 0
            THEN \'NOK\'
            ELSE \'KO\'
          END) as state'),
        ]);
    }

    if ($category) {
      $items = $items->where('category_id', $category);
    }

    if ($search) {
      $items = $items
        ->where(function ($query) use ($search) {
          $query
            ->where(DB::raw('lower(name)'), 'like', '%'.strtolower($search).'%')
            ->orWhere(DB::raw('lower(description)'), 'like', '%'.strtolower($search).'%');
        })
        ->orWhereHas('category', function ($query) use ($search) {
          $query->where(DB::raw('lower(name)'), 'like', '%'.strtolower($search).'%');
        });
    }

    $items = $items->orderBy($orderBy, $orderDir)
      ->simplePaginate($size, ['*'], 'page', $page)
      ->withPath('/items')
      // set the query string for the next page
      ->withQueryString();

    return response()->json($items);
  }

  public function show(Request $request, Item $item)
  {
    if (
      ! UserStructure::where('user_id', $request->user()->id)
        ->where('structure_id', $item->structure_id)
        ->firstOrFail()
    ) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    return response()->json($item, 200);
  }

  public function update(Request $request, Item $item)
  {
    $validator = Validator::make($request->all(), Item::$validation);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $item->name = $request->name;
    $item->description = $request->description;
    $item->category_id = $request->category_id;
    $item->usable = $request->usable ?? $item->usable;
    $item->date_of_buy = $request->date_of_buy
      ? date('Y-m-d', strtotime($request->date_of_buy))
      : null;
    $item->image = $request->image;

    $item->save();

    return response()->json($item);
  }

  public function destroy(Item $item)
  {
    $coming_events = EventSubscription::with([
      'event' => function ($query) {
        $query->where('id', '!=', null)->select('id', 'start_date', 'end_date');
        $query->where('end_date', '>=', now())->orWhere('start_date', '>=', now());
        $query->select('id', 'start_date', 'end_date', 'name');
      },

    ])
      ->where('item_id', $item->id)
      ->get();
    if ($coming_events->count() > 0) {
      return response()->json(
        [
          'message' => 'Suppression impossible, il y a des événements à venir pour cet objet',
          'events' => $coming_events,
        ],
        429
      );
    }
    $item->delete();

    return response()->json(null, 204);
  }

  public function getCategories()
  {
    $structure = request()->query('code_structure');

    // get distinct name of the categories of items for the structure
    return ItemCategory::whereHas('structure', function ($query) use ($structure) {
      $query->where('code_structure', $structure);
    })
      ->distinct('name')
      ->orderBy('name')
      ->select(['id', 'name', 'identified'])->get();
  }

  public function uploadFile(Request $request)
  {
    $validation = Validator::make($request->all(), [
      'image' => 'required|image|max:2048',

    ]);

    if ($validation->fails()) {
      return response()->json($validation->errors(), 400);
    }

    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $resizedImage = Image::read($image)->resize(128, 128);
      $tempPath = tempnam(sys_get_temp_dir(), 'group_img');
      $resizedImage->save($tempPath);
      $path = Storage::disk('public')->putFile(
        'items',
        new \Illuminate\Http\File($tempPath),
        ['visibility' => 'public']
      );
      unlink($tempPath);
    }

    return response()->json([
      'path' => $path ?? null,
    ], 201);
  }

  public function getAvailableItems(Request $request)
  {
    Log::info(request('start_date'));
    Log::info(request('end_date'));
    Validator::make($request->all(), [
      // start date is format 2025-07-17T18:00:00.000Z
      'start_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"',
      'end_date' => 'required|date_format:"Y-m-d\TH:i:s.000\Z"|after_or_equal:start_date',
      'page' => 'integer|min:1',
      'size' => 'integer|min:1|max:100',
      'q' => 'nullable|string|max:255',
      'order_by' => 'in:items.name,category_id',
      'sort_by' => 'in:asc,desc',
      'category_id' => 'nullable|exists:item_categories,id',
      'for_event' => 'nullable|exists:events,id',
    ])->validate();

    $orderBy = $request->query('order_by', 'items.name');
    $orderDir = $request->query('sort_by', 'asc');
    $start_date = Carbon::parse($request->start_date);
    $end_date = Carbon::parse($request->end_date);
    $forEventId = $request->query('for_event');
    $code_structure_mask = JWTAuth::parseToken()->getPayload()->get('selected_structure.mask');

    // 1. Quantité utilisée pour chaque item non identifié
    $usedQuantities = EventSubscription::query()
      ->leftJoin('events as e', 'e.id', '=', 'event_subscriptions.event_id')
      ->leftJoin('items as i', 'i.id', '=', 'event_subscriptions.item_id')
      ->leftJoin('item_categories as c', 'c.id', '=', 'i.category_id')
      ->where('c.identified', false)
      ->groupBy('i.id')
      ->select('i.id', 'i.name', DB::raw('SUM(event_subscriptions.quantity) as used_quantity'));

    if ($forEventId) {
      $usedQuantities->where('e.id', '!=', $forEventId);
    }

    // 2. Sélection des items disponibles
    $items = Item::query();

    if ($request->has('q')) {
      $searchTerm = $request->query('q', '');
      Log::info("Searching for items with term: $searchTerm");
      $items->where(function ($query) use ($searchTerm) {
        $query->where(DB::raw('lower(items.name)'), 'LIKE', '%'.strtolower($searchTerm).'%')
          ->orWhere(DB::raw('lower(categorie.name)'), 'like', '%'.strtolower($searchTerm).'%');
      });
    }

    if ($request->has('category_id')) {
      $items->where('items.category_id', $request->category_id);
    }

    $items
      ->leftJoin('item_categories as categorie', 'categorie.id', '=', 'items.category_id')
      ->leftJoin('event_subscriptions as es', 'es.item_id', '=', 'items.id')
      ->leftJoin('events as event', 'event.id', '=', 'es.event_id')
      ->leftJoinSub($usedQuantities, 'oqu', function ($join) {
        $join->on('oqu.id', '=', 'items.id');
      })
      ->whereHas('structure', function ($query) use ($request, $code_structure_mask) {
        $query->where('code_structure', 'like', "$code_structure_mask%");
      })
      ->where(function ($query) use ($start_date, $end_date) {
        $query->where(function ($sub) use ($start_date, $end_date) {
          $sub->whereRaw('not exists (
                    select 1 from events
                    inner join event_subscriptions on events.id = event_subscriptions.event_id
                    where items.id = event_subscriptions.item_id
                    and (start_date < ? and end_date > ?)
                )', [$start_date, $end_date])
            ->where('categorie.identified', true);
        })
          ->orWhere('categorie.identified', false);
      });
    if ($forEventId) {
      $items->orWhere('event.id', '!=', $forEventId);
    }
    $items
      ->whereRaw('items.stock - COALESCE(oqu.used_quantity, 0) > 0')
      ->select(
        'categorie.*',
        'items.*',
        DB::raw('items.stock - COALESCE(oqu.used_quantity, 0) as rest')
      );

    return $items
      ->with(['category:identified,id,name'])
      ->orderBy($orderBy, $orderDir)
      ->distinct()
      ->paginate(
        $request->query('size', 25),
        ['*'],
        'page',
        $request->query('page', 1)
      );
    // $items = Item::with([
    //   'events',
    //   'category',
    // ]);

    // if ($request->has('category_id')) {
    //   $items->where('category_id', $request->category_id);
    // }

    // $items = $items
    //   ->with(['category:identified,id'])
    //   ->orWhereHas('category', function ($query) {
    //     $query->where('identified', true);
    //   })->whereDoesntHave('events', function ($query) use ($start_date, $end_date, $forEventId) {
    //     // Exclude items that have an overlapping event other than the one specified in for_event
    //     $query->where(function ($q) use ($start_date, $end_date) {
    //       $q->where('start_date', '<', $end_date)
    //         ->where('end_date', '>', $start_date);
    //     });
    //     // if item.category.identified is false, we take it anyway and stock is real stock minus sum of event quantities
    //     if ($forEventId) {
    //       $query->where('events.id', '!=', $forEventId);
    //     }
    //   })
    //   ->where(function ($query) use ($request) {
    //     $searchTerm = $request->query('q', '');
    //     if ($searchTerm) {
    //       $query->where(DB::raw('lower(name)'), 'LIKE', '%' . strtolower($searchTerm) . '%')
    //         ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
    //           $categoryQuery->where(DB::raw('lower(name)'), 'LIKE', '%' . strtolower($searchTerm) . '%');
    //         });
    //     }
    //   })
    //   // get only items that are usable
    //   ->where('usable', true)->select('items.*', );

    // return $items->paginate(
    //   $request->query('size', 25),
    //   ['*'],
    //   'page',
    //   $request->query('page', 1)
    // );
  }
}
