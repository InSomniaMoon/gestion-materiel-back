<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Validator;

class ItemCategoryController extends Controller
{
  public function index(Request $request)
  {
    $size = $request->query('size', 25);
    $page = $request->query('page', 0);
    $orderBy = $request->query('order_by', 'name');
    $search = $request->query('q');
    $group_id = $request->query('group_id');

    $category = $request->query('category_id');

    $validator = Validator::make($request->all(), [
      'group_id' => 'required|exists:groups,id',
      'size' => 'integer|min:1|max:100',
      'page' => 'integer|min:0',
      'order_by' => 'in:name,created_at,updated_at',

    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    // Get all items paginated with their itemOptions

    $items = ItemCategory::where('group_id', $group_id)

      ->orderBy($orderBy);

    if ($search) {
      $items = $items
        ->where(function ($query) use ($search) {
          $query
            ->where('name', 'like', "%$search%");
        });
    }

    $items = $items->paginate($perPage = $size, $columns = ['*'], 'page', $page)
      ->withPath('/categories')
      // set the query string for the next page
      ->withQueryString();

    return response()->json($items);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $category = ItemCategory::create([
      'name' => $request->name,
      'group_id' => $request->query('group_id'),
    ]);

    return response()->json($category, 201);
  }

  public function update(Request $request, ItemCategory $category)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $category->update([
      'name' => $request->name,
    ]);

    return response()->json($category, 200);
  }

  public function destroy(ItemCategory $category)
  {
    // Check if the category is used by any item
    if ($category->items()->count() > 0) {
      return response()->json(['message' => 'Impossible de supprimer une catégorie ayant des items.'], 400);
    }

    $category->delete();

    return response()->json(['message' => 'Category deleted successfully'], 204);
  }
}
