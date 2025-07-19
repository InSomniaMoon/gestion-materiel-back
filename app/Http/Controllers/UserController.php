<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
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
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $page = $request->input('page', 1);
    $size = $request->input('size', 25);
    $filter = $request->input('q', '');

    $users = User::
      with('userGroups')
      ->whereAny([

        DB::raw('lower(name)'),
        DB::raw('lower(email)'),
      ], 'like', '%'.strtolower($filter).'%')
      // ->where(DB::raw('lower(name)'), 'like', '%' . strtolower($filter) . '%')
      // ->orWhere(DB::raw('lower(email)'), 'like', '%' . strtolower($filter) . '%')
      ->whereHas('userGroups', function ($query) use ($request) {
        $query->where('id', $request->input('group_id'));
      })
      ->paginate($size, ['*'], 'page', $page)
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

    $users = User::where('name', 'like', "%$filter%")
      ->orWhere('email', 'like', "%$filter%")
      ->simplePaginate($size, ['*'], 'page', $page)
      ->withPath('/items')
      ->withQueryString();

    return response()->json($users);
  }

  public function createUserWithGroup(Request $request)
  {
    $request->merge(['app_role' => 'user']);

    return $this->createUser($request);
  }

  public function createUser(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string',
      'email' => 'required|email',
      'role' => 'required|string',
      'group_id' => 'integer|required',
      'phone' => 'string|nullable',
      'app_role' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $password = Hash::make(Str::random(25));

    $user = User::create([
      'name' => $request->input('name'),
      'email' => $request->input('email'),
      'password' => $password,
      'role' => $request->input('app_role'),
      'phone' => $request->input('phone'),
    ]);

    UserGroup::create([
      'user_id' => $user->id,
      'group_id' => $request->input('group_id'),
      'role' => $request->input('role'),
    ]);

    Password::sendResetLink($request->only('email'));

    return response()->json($user, 201);
  }

  public function getUserGroups(Request $request, User $user)
  {
    $groups = $user->userGroups()->get();

    return response()->json($groups);
  }

  /**
   * {
   *   "groups_to_add": [{
   * id: 1,
   * role: "admin"}],
   *  "groups_to_remove": [1]
   *  "groups_to_update": [{
   * id: 1,
   * role: "admin"
   * }]
   * }
   */
  public function updateUserGroups(Request $request, User $user)
  {
    $groups = $request->input('groups', []);

    // Remove all user groups for this user
    UserGroup::where('user_id', $user->id)->delete();

    // Prepare new user groups for insertion
    $insertData = [];
    foreach ($groups as $group) {
      if (! empty($group['group_id']) && $group['group_id'] > 0) {
        $insertData[] = [
          'user_id' => $user->id,
          'group_id' => $group['group_id'],
          'role' => $group['role'] ?? 'user',
        ];
      }
    }

    Log::info('Updating user groups', [
      'insertData' => $insertData,
    ]);

    if (! empty($insertData)) {
      UserGroup::insert($insertData);
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
      'already_in_group' => $user ? $user->userGroups()->find($request->input('group_id'))->exists() : false,
    ]);
  }
}
