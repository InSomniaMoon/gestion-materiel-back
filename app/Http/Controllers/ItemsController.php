<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemsController extends Controller
{

    function createItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "description" => "required|max:255",
            "category" => "required",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $item = new Item([
            "name" => $request->name,
            "description" => $request->description,
            "category" => $request->category,
        ]);
        $item->save();
        return response()->json($item, 201);
    }

    function index(Request $request)
    {
        // "current_page": 1,
        $size = $request->query('per_page', 20);
        $page = $request->query('page', 1);
        $orderBy = $request->query('order_by', 'name');

        // Get all items paginated with their itemOptions
        $items = Item::orderBy($orderBy)->simplePaginate($size, ['*'], null, $page)
            ->withPath('/items')
            // set the query string for the next page
            ->withQueryString();
        return response()->json($items);
    }

    function show(Item $item)
    {
        return response()->json($item);
    }

    function update(Request $request, Item $item)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required",
            "description" => "required|max:255",
            "category" => "required|max:255",
            "usable" => "boolean|required"
        ]);

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
}
