<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Storage;
use Str;

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
      'image' => 'nullable|image|max:2048',
    ]);

    $group = new Group();
    $group->name = $request->name;
    $group->description = $request->description;
    $group->image = $request->image;
    $group->save();

    return response()->json([
      'message' => 'Group created successfully',
      'group' => $group,
    ]);
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
        'groups',
        new \Illuminate\Http\File($tempPath),
        ['visibility' => 'public']
      );
      unlink($tempPath);
    }

    return response()->json([
      'path' => $path ?? null,
    ], 201);
  }
}
