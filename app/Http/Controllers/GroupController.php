<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupController extends Controller
{
  public function getGroups(Request $request)
  {
    if (request()->get('all')) {
      return Group::orderBy('name', 'ASC')->get();
    }

    $validator = Validator::make($request->all(), [
      'page' => 'integer|min:1',
      'size' => 'integer|min:1',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $page = $request->input('page', 1);
    $size = $request->input('size', 25);
    $filter = $request->input('q', '');

    $groups = Group::where('name', 'ilike', "%$filter%")
      ->orWhere('description', 'ilike', "%$filter%")
      ->simplePaginate($size, ['*'], 'page', $page)
      ->withQueryString();

    return response()->json($groups);
  }

  public function createGroup(Request $request)
  {
    $request->validate([
      'name' => 'required|string',
    ]);

    $group = new Group();
    $group->name = $request->name;
    $group->description = $request->description;
    $group->save();

    return response()->json([
      'message' => 'Group created successfully',
      'group' => $group,
    ]);
  }
}
