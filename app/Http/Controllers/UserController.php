<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use App\Models\User;
use App\Models\UserStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Log;

class UserController extends Controller
{
  public function getPaginatedUsers(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'page' => 'integer|min:1',
      'size' => 'integer|min:1',
      'q' => 'string',
      'order_by' => 'string|in:firstname,lastname,email,role|nullable',
      'sort_by' => 'string|in:asc,desc|nullable',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $page = $request->input('page', 1);
    $size = $request->input('size', 25);
    $filter = $request->input('q', '');
    $sortBy = $request->input('order_by', 'lastname');
    $orderBy = $request->input('sort_by', 'asc');

    $structure = Structure::find($request->input('structure_id', 'code_structure'));

    $users = User::
      with([
        'userStructures:id,name,color,code_structure',
      ])
      ->whereAny([
        DB::raw('lower(firstname)'),
        DB::raw('lower(lastname)'),
        DB::raw('lower(email)'),
      ], 'like', '%'.strtolower($filter).'%')

      ->whereHas('userStructures', function ($query) use ($request, $structure) {
        switch ($structure->type) {
          case Structure::GROUPE:
            $code_base = substr($structure->code, 0, 2);
            $query->where('code_structure', 'like', "$code_base%");

            break;
          default:
            $query->where('id', $request->input('structure_id'));
            break;
        }
      })
      ->orderBy($sortBy, $orderBy)
      ->simplePaginate($size, ['*'], 'page', $page)
      ->withPath('/users')
      ->withQueryString();

    return response()->json($users);
  }

  public function getBackofficePaginatedUsers(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'page' => 'integer|min:1',
      'size' => 'integer|min:1',
      'q' => 'string|nullable',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $page = $request->input('page', 1);
    $size = $request->input('size', 25);
    $filter = $request->input('q', '');

    $users = User::
      whereAny([
        DB::raw('lower(firstname)'),
        DB::raw('lower(lastname)'),
        DB::raw('lower(email)'),
      ], 'like', '%'.strtolower($filter).'%')
      ->paginate($size, ['*'], 'page', $page)
      ->withPath('/items')
      ->withQueryString();

    return response()->json($users);
  }

  public function createUserWithStructure(Request $request)
  {
    $request->merge(['app_role' => 'user']);

    return $this->createUser($request);
  }

  public function createUser(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'firstname' => 'required|string',
      'lastname' => 'required|string',
      'email' => 'required|email',
      'role' => 'required|string',
      'structure_id' => 'integer|required|exists:structures,id',
      'phone' => 'string|nullable',
      'app_role' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $password = Hash::make(Str::random(25));

    $user = User::create([
      'firstname' => $request->input('firstname'),
      'lastname' => $request->input('lastname'),
      'email' => $request->input('email'),
      'password' => $password,
      'role' => $request->input('app_role'),
      'phone' => $request->input('phone'),
    ]);

    UserStructure::create([
      'user_id' => $user->id,
      'structure_id' => $request->input('structure_id'),
      'role' => $request->input('role'),
    ]);

    Password::sendResetLink($request->only('email'));

    return response()->json($user, 201);
  }

  public function getUserStructures(Request $request, User $user)
  {
    $structures = $user->userStructures()->get();

    return response()->json($structures);
  }

  /**
   * {
   *   "structures_to_add": [{
   * id: 1,
   * role: "admin"}],
   *  "structures_to_remove": [1]
   *  "structures_to_update": [{
   * id: 1,
   * role: "admin"
   * }]
   * }
   */
  public function updateUserStructures(Request $request, User $user)
  {
    $structures = $request->input('structures', []);

    // Remove all user structures for this user
    UserStructure::where('user_id', $user->id)->delete();

    // Prepare new user structures for insertion
    $insertData = [];
    foreach ($structures as $structure) {
      if (! empty($structure['structure_id']) && $structure['structure_id'] > 0) {
        $insertData[] = [
          'user_id' => $user->id,
          'structure_id' => $structure['structure_id'],
          'role' => $structure['role'] ?? 'user',
        ];
      }
    }

    Log::info('Updating user structures', [
      'insertData' => $insertData,
    ]);

    if (! empty($insertData)) {
      UserStructure::insert($insertData);
    }

    return response()->json();
  }

  public function checkUserExists(Request $request, User $user)
  {
    Validator::make($request->all(), [
      'email' => 'required|email',
    ])->validate();

    $user = User::where('email', $request->input('email'))->first();

    return response()->json([
      'exists' => $user !== null,
      'already_in_structure' => $user?->userStructures()->where('id', $request->input('structure_id'))->exists() ?? false,
    ]);
  }

  public function sendResetPassword(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'email' => 'required|email',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $status = Password::sendResetLink(
      $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
      ? response()->json(['message' => __($status)], 200)
      : response()->json(['message' => __($status)], 400);
  }
}
