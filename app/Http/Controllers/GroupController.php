<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Log;
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
      ->paginate($size, ['*'], 'page', $page)
      ->withQueryString();

    return response()->json($groups);
  }

  public function createGroup(Request $request)
  {
    $request->validate([
      'name' => 'required|string',
      'image' => 'nullable|string',
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

  public function updateGroup(Request $request, Group $group)
  {
    $request->validate([
      'name' => 'required|string',
      'image' => 'nullable|string',
      'description' => 'nullable|string',
    ]);

    $group->name = $request->name;
    $group->description = $request->description;

    if ($request->input('image')) {
      // delete old image if exists
      if ($group->image) {
        Storage::disk('public')->delete($group->image);
      }
      $group->image = $request->image;
    }

    $group->save();

    return response()->json([
      'message' => 'Group updated successfully',
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

  public function addUserToGroup(Request $request)
  {
    $request->validate([
      'email' => 'required|exists:users,email',
      'group_id' => 'required|exists:groups,id',
      'role' => 'required|string|in:admin,user',
    ]);

    $group = Group::find($request->group_id);
    $user = \App\Models\User::where('email', $request->email)->first();

    // Check if user is already in group
    if ($user->userGroups()->where('group_id', $group->id)->exists()) {
      return response()->json(['message' => 'User already in group'], 400);
    }

    $user->userGroups()->attach($group->id);

    return response()->json(['message' => 'User added to group successfully']);
  }
}
