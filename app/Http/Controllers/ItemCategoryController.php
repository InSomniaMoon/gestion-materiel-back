<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use App\Models\Structure;
use Illuminate\Http\Request;
use Log;
use Validator;

class ItemCategoryController extends Controller
{
  public function index(Request $request)
  {
    $size = $request->query('size', 25);
    $page = $request->query('page', 1);
    $orderBy = $request->query('order_by', 'name');
    $search = $request->query('q');
    $code_structure = $request->query('code_structure');

    $validator = Validator::make($request->all(), [
      'code_structure' => 'required',
      'size' => 'integer|min:1|max:100',
      'page' => 'integer|min:0',
      'order_by' => 'in:name,created_at,updated_at',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    Log::info('request for getPaginatedCategories', $request->all());

    $items = ItemCategory::whereHas('structure', function ($query) use ($code_structure) {
      $query->where('code_structure', $code_structure);
    })
      ->orderBy($orderBy);

    if ($search) {
      $items = $items
        ->where(function ($query) use ($search) {
          $query
            ->where('name', 'like', "%$search%");
        });
    }

    $items = $items->paginate($size, ['*'], 'page', $page)
      ->withPath('/categories')
      // set the query string for the next page
      ->withQueryString();

    return response()->json($items);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'identified' => 'required|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }
    $code_structure = $request->input('code_structure');
    $structure = Structure::where('code_structure', $code_structure)->first();
    $category = ItemCategory::create([
      'name' => $request->name,
      'structure_id' => $structure->id,
      'identified' => $request->input('identified'),
    ]);

    return response()->json($category, 201);
  }

  public function update(Request $request, ItemCategory $category)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'identified' => 'required|boolean',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $category->update([
      'name' => $request->name,
      'identified' => $request->identified,

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
