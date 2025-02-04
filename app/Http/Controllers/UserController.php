<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

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
      'phone' => 'string|nullable',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $password = Hash::make($request->input('password'));

    $user = User::create([
      'name' => $request->input('name'),
      'email' => $request->input('email'),
      'password' => $password,
      'role' => $request->input('role'),
      'phone' => $request->input('phone'),
    ]);

    // get reset password token

    Password::sendResetLink($request->only('email'));

    // return response()->json(['message' => 'User created successfully'], 201);

    return response()->json($user, 201);
  }
}
