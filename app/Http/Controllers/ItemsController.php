<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\UserGroup;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
      'category' => $request->category,
      'group_id' => $group_id,
    ]);
    $item->save();
    if ($request->has('options')) {
      // validation for options
      $validator = Validator::make($request->options, [
        '*.name' => 'required|max:255',
        '*.description' => 'max:255',
      ]);

      // map through the options and add 'usable' to each option

      if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
      }

      $options = array_map(function ($option) {
        $option['usable'] = $option['usable'] ?? true;

        return $option;
      }, $request->options);

      $item->options()->createMany($options);
    }

    return response()->json($item, 201);
  }

  public function index(Request $request)
  {
    // "current_page": 1,
    $size = $request->query('size', 25);
    $page = $request->query('page', 1);
    $orderBy = $request->query('order_by', 'name');
    $search = $request->query('q');
    $group_id = $request->query('group_id');

    $category = $request->query('category_id');

    $validator = Validator::make($request->all(), [
      'group_id' => 'required|exists:groups,id',
      'size' => 'integer|min:1|max:100',
      'page' => 'integer|min:1',
      'order_by' => 'in:name,created_at,updated_at',
      'category_id' => 'nullable|exists:item_categories,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    // Get all items paginated with their itemOptions

    $items = Item::where('group_id', $group_id)
      // ->with('options')
      ->orderBy($orderBy);

    if ($search) {
      $items = $items
        ->where(function ($query) use ($search) {
          $query
            ->where('name', 'like', "%$search%")
            ->orWhere('description', 'like', "%$search%");
        });
    }

    if ($category) {
      $items = $items->where('category_id', $category);
    }

    $items = $items->paginate($perPage = $size, $columns = ['*'], 'page', $page)
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

    return response()->json($item);
  }

  public function update(Request $request, Item $item)
  {
    $validator = Validator::make($request->all(), Item::$validation);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $item->name = $request->name;
    $item->description = $request->description;
    $item->category = $request->category;
    $item->usable = $request->usable ?? $item->usable;
    $item->save();

    return response()->json($item);
  }

  public function destroy(Item $item)
  {
    $item->delete();

    return response()->json(null, 204);
  }

  public function getCategories()
  {
    $group_id = request()->query('group_id');
    // get distinct name of the categories of items for the group
    $categories = ItemCategory::whereHas('items', function ($q) use ($group_id) {
      $q->where('group_id', $group_id);
    })
      ->distinct('name')
      ->get(['id', 'name']);

    return response()->json($categories);
  }
}
