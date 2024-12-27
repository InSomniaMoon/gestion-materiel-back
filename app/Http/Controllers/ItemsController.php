<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemsController extends Controller
{

  function createItem(Request $request)
  {
    $validator = Validator::make($request->all(), Item::$validation);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $item = new Item([
      "name" => $request->name,
      "description" => $request->description,
      "category" => $request->category,
      'group_id' => $request->user()->group_id,
    ]);
    $item->save();
    if ($request->has('options')) {
      // validation for options
      $validator = Validator::make($request->options, [
        "*.name" => "required|max:255",
        "*.description" => "max:255",
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

  function index(Request $request)
  {
    // "current_page": 1,
    $size = $request->query('size', 25);
    $page = $request->query('page', 1);
    $orderBy = $request->query('order_by', 'name');
    $search = $request->query("q");
    $group_id = $request->query("group_id");

    $category = $request->query("category");

    $validator = Validator::make($request->all(), [
      'group_id' => 'required|exists:groups,id',
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
      $items = $items->where('category', 'like', "%$category%");
    }

    $items = $items->paginate($perPage = $size, $columns = ['*'], $pageName = 'page', $page = $page)
      ->withPath('/items')
      // set the query string for the next page
      ->withQueryString();

    return response()->json($items);
  }

  function show(Request $request, Item $item)
  {
    if (!UserGroup::where('user_id', $request->user()->id)
      ->where('group_id', $item->group_id)
      ->firstOrFail()) {
      return response()->json(['message' => 'Unauthorized'], 401);
    }
    return response()->json($item);
  }

  function update(Request $request, Item $item)
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

  function destroy(Item $item)
  {
    $item->delete();
    return response()->json(null, 204);
  }


  public function getCategories()
  {
    // get all distinct 'category' values from the 'items' table
    $categories = Item::distinct()->pluck('category');
    return response()->json($categories);
  }
}
