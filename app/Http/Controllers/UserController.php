<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
  public function getPaginatedUsers(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'page' => 'integer|min:1',
      'size' => 'integer|min:1',
      'filter' => 'string',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $page = $request->input('page', 1);
    $size = $request->input('size', 25);
    $filter = $request->input('q', '');

    $users = User::where('name', 'like', '%'.$filter.'%')
      ->orWhere('email', 'like', '%'.$filter.'%')
      ->simplePaginate($perPage = $size, $columns = ['*'], $pageName = 'page', $page = $page)
      ->withPath('/items')
      ->withQueryString();

    return response()->json($users);
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
}
