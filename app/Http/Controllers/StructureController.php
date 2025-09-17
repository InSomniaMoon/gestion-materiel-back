<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Structure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Laravel\Facades\Image;
use Storage;

class StructureController extends Controller
{
  public function getGroups(Request $request)
  {
    if (request()->get('all')) {
      return Structure::orderBy('name', 'ASC')->get();
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

    $structures = Structure::where('name', 'like', "%$filter%")
      ->orWhere('description', 'like', "%$filter%")
      ->paginate($size, ['*'], 'page', $page)
      ->withQueryString();

    return response()->json($structures);
  }

  public function updateStructure(Request $request, Structure $structure)
  {
    $request->validate([
      'name' => 'required|string',
      'image' => 'nullable|string',
      'description' => 'nullable|string',
    ]);

    $structure->name = $request->name;
    $structure->description = $request->description;

    if ($request->input('image')) {
      // delete old image if exists
      if ($structure->image) {
        Storage::disk('public')->delete($structure->image);
      }
      $structure->image = $request->image;
    }

    $structure->save();

    return response()->json([
      'message' => 'Structure modifiée avec succès',
      'structure' => $structure,
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
      $tempPath = tempnam(sys_get_temp_dir(), 'structure_img');
      $resizedImage->save($tempPath);
      $path = Storage::disk('public')->putFile(
        'structures',
        new \Illuminate\Http\File($tempPath),
        ['visibility' => 'public']
      );
      unlink($tempPath);
    }

    return response()->json([
      'path' => $path ?? null,
    ], 201);
  }

  public function addUserToStructure(Request $request)
  {
    $request->validate([
      'email' => 'required|exists:users,email',
      'structure_id' => 'required|exists:structures,id',
      'role' => 'required|string|in:admin,user',
    ]);

    $structure = Structure::find($request->structure_id);
    $user = \App\Models\User::where('email', $request->email)->first();

    // Check if user is already in structure
    if ($user->userStructures()->where('structure_id', $structure->id)->exists()) {
      return response()->json(['message' => 'User déjà dans la structure'], 400);
    }

    $user->userStructures()->attach($structure->id);

    return response()->json(['message' => 'User ajouté à la structure avec succès']);
  }

  public function getStructuresWithMembers(Request $request)
  {
    $structure_id = $request->query('structure_id');

    $structure = Structure::find($structure_id);

    if ($structure->type == Structure::GROUPE) {
      // slice 2 last characters
      $base_code = substr($structure->code_structure, 0, -2);
      $children = Structure::with('members:id,firstname,lastname')
        ->where('code_structure', 'LIKE', "$base_code%")
        ->orderBy('code_structure')
        ->get();

      return compact(['structure', 'children']);
    }

    return [$structure];
  }
}
