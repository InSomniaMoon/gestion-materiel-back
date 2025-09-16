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

    $users = User::
      with([
        'userGroups' => function ($query) use ($request) {
          $query->where('id', $request->input('group_id'));
        },
      ])
      ->whereAny([
        DB::raw('lower(firstname)'),
        DB::raw('lower(lastname)'),
        DB::raw('lower(email)'),
      ], 'like', '%'.strtolower($filter).'%')

      ->whereHas('userGroups', function ($query) use ($request) {
        $query->where('id', $request->input('group_id'));
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

  public function createUserWithGroup(Request $request)
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
      'group_id' => 'integer|required',
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
      'already_in_group' => $user?->userGroups()->where('id', $request->input('group_id'))->exists() ?? false,
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
