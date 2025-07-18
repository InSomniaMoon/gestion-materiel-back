<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemOption;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemOptionController extends Controller
{
  public function getOptions(Item $item)
  {
    // ItemOption::where('item_id', $item->id)->get();

    return response()->json($item->options()->orderBy('name')->get());
  }

  public function createOption(Item $item, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'usable' => 'boolean',
      'description' => 'string|max:255|nullable',
    ]);

    Log::info($request->all());

    if ($validator->fails()) {
      return response()->json($validator->errors(), Response::HTTP_BAD_REQUEST);
    }

    $option = ItemOption::create([
      'name' => $request->name,
      'usable' => $request->usable ?? true,
      'description' => $request->description,
      'item_id' => $item->id,
    ]);

    return response()->json($option, Response::HTTP_CREATED);
  }

  public function updateOption(Item $item, ItemOption $option, Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'string|max:255',
      'usable' => 'boolean',
      'description' => 'nullable|string|max:255',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $option->update($request->all());

    return response()->json($option);
  }

  public function deleteOption(Item $item, ItemOption $option)
  {
    $option->delete();

    return response()->json(null, Response::HTTP_NO_CONTENT);
  }
}
