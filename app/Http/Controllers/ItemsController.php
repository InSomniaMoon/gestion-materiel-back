<?php

namespace App\Http\Controllers;

use App\Models\EventSubscription;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UserGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Log;
use Storage;

class ItemsController extends Controller
{
  public function createItem(Request $request)
  {
    $validator = Validator::make($request->all(), Item::$validation);

    $group_id = $request->query('group_id');

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    if (
      ! UserGroup::where('user_id', $request->user()->id)
        ->where('group_id', $group_id)
        ->firstOrFail()
    ) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $item = new Item([
      'name' => $request->name,
      'description' => $request->description,
      'category_id' => $request->category_id,
      'group_id' => $group_id,
      'image' => $request->image,
    ]);
    $item->save();

    if ($request->has('options')) {
      // validation for options
      $validator = Validator::make($request->options, [
        '*.name' => 'required|max:255',
        '*.description' => 'max:255',
      ]);

      if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
      }

      // Synchroniser les options (pour la création, toutes les options sont nouvelles)
      $this->syncOptions($item, $request->options);
    }

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
    $group_id = $request->query('group_id');

    $category = $request->query('category_id');

    $validator = Validator::make($request->all(), [
      'group_id' => 'required|exists:groups,id',
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

    $items = Item::where('group_id', $group_id)
      ->with('category')
      ->with('options');

    if ($isAdmin) {
      $items = $items->withCount([
        'options as open_option_issues_count' => function ($query) {
          $query->join('item_option_issues', 'item_options.id', '=', 'item_option_issues.item_option_id')
            ->where('item_option_issues.status', 'open');
        },
      ])
        ->addSelect([
          DB::raw('(CASE
            WHEN items.usable = true
              AND (SELECT COUNT(*) FROM item_options
                   INNER JOIN item_option_issues ON item_options.id = item_option_issues.item_option_id
                   WHERE item_options.item_id = items.id AND item_option_issues.status = \'open\') = 0
            THEN \'OK\'
            WHEN items.usable = true
              AND (SELECT COUNT(*) FROM item_options
                   INNER JOIN item_option_issues ON item_options.id = item_option_issues.item_option_id
                   WHERE item_options.item_id = items.id AND item_option_issues.status = \'open\') > 0
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

    $items = $items->orderBy($orderBy, $orderDir)->paginate($size, ['*'], 'page', $page)
      ->withPath('/items')
      // set the query string for the next page
      ->withQueryString();

    return response()->json($items);
  }

  public function show(Request $request, Item $item)
  {
    if (
      ! UserGroup::where('user_id', $request->user()->id)
        ->where('group_id', $item->group_id)
        ->firstOrFail()
    ) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }

    $item->load('options');

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
    $options = $request->options ?? [];

    // Synchroniser les options
    $this->syncOptions($item, $options);

    $item->save();

    return response()->json($item);
  }

  /**
   * Synchronise les options d'un item
   * - Supprime les options qui ne sont plus dans la liste
   * - Crée ou met à jour les options existantes
   */
  private function syncOptions(Item $item, array $options)
  {
    // Récupérer les IDs des options existantes
    $existingOptionIds = $item->options()->pluck('id')->toArray();

    // Récupérer les IDs des options envoyées (filtrer les null/0)
    $submittedOptionIds = collect($options)
      ->pluck('id')
      ->filter(function ($id) {
        return $id !== null && $id !== 0;
      })
      ->toArray();

    // Supprimer les options qui ne sont plus dans la liste
    $optionsToDelete = array_diff($existingOptionIds, $submittedOptionIds);
    if (! empty($optionsToDelete)) {
      $item->options()->whereIn('id', $optionsToDelete)->delete();
    }

    // Créer ou mettre à jour les options
    foreach ($options as $optionData) {
      // Préparer les données de l'option
      $optionData['usable'] ??= true;
      $optionData['item_id'] = $item->id;

      if (isset($optionData['id']) && $optionData['id'] > 0) {
        // Mettre à jour l'option existante
        $item->options()->where('id', $optionData['id'])->update([
          'name' => $optionData['name'],
          'description' => $optionData['description'] ?? '',
          'usable' => $optionData['usable'],
        ]);
      } else {
        // Créer une nouvelle option
        $item->options()->create([
          'name' => $optionData['name'],
          'description' => $optionData['description'] ?? '',
          'usable' => $optionData['usable'],
        ]);
      }
    }
  }

  public function destroy(Item $item)
  {
    if (
      EventSubscription::with('event')->where('item_id', $item->id)->where('event.start_date', '<=', now())
        ->where('event.end_date', '>=', now())->orWhere('start_date', '>=', now())->count()
    ) {
      return response()->json(
        ['message' => 'Suppression impossible, il y a des événements à venir pour cet objet'],
        429
      );
    }

    $item->delete();

    return response()->json(null, 204);
  }

  public function getCategories()
  {
    $group_id = request()->query('group_id');
    // get distinct name of the categories of items for the group
    $categories = ItemCategory::where(
      'group_id',
      $group_id
    )
      ->distinct('name')
      ->orderBy('name')
      ->get(['id', 'name']);

    return response()->json($categories);
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
      'category_id' => 'nullable|exists:item_categories,id',
    ])->validate();

    $start_date = Carbon::parse($request->start_date);
    $end_date = Carbon::parse($request->end_date);

    $items = Item::with([
      'events',
      'category',
    ]);

    if ($request->has('category_id')) {
      $items->where('category_id', $request->category_id);
    }

    $items = $items
      ->whereDoesntHave('events', function ($query) use ($start_date, $end_date) {
        $query->where(function ($q) use ($start_date, $end_date) {
          $q->where('start_date', '<', $end_date)
            ->where('end_date', '>', $start_date);
        });
      })
      ->where(function ($query) use ($request) {
        $searchTerm = $request->query('q', '');
        if ($searchTerm) {
          $query->where(DB::raw('lower(name)'), 'LIKE', '%'.strtolower($searchTerm).'%')
            ->orWhereHas('category', function ($categoryQuery) use ($searchTerm) {
              $categoryQuery->where(DB::raw('lower(name)'), 'LIKE', '%'.strtolower($searchTerm).'%');
            });
        }
      })

      // get only items that are usable
      ->where('usable', true);

    return $items->paginate(
      $request->query('size', 25),
      ['*'],
      'page',
      $request->query('page', 1)
    );
  }
}
