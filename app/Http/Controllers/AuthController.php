<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }


        if (! $token = JWTAuth::attempt($request->only('email', 'password'))) {
            return response()->json(['error' => __('auth.failed')], 401);
        }

        // Get the authenticated user.
        $user = Auth::user();

        $user_groups = UserGroup::where('user_id', $user->id);
        $admin_groups = $user_groups->where('role', 'admin')->get();

        // (optional) Attach the role to the token.
        $token = JWTAuth::claims([
            'role' => $user->role,
            'type' => TokenType::ACCESS,
            'user_groups' => $user_groups->get()->map(function ($user_group) {
                return $user_group->group_id;
            }),
            'admin_groups' => $admin_groups->map(function ($user_group) {
                return $user_group->group_id;
            }),
        ])->fromUser($user);

        $groups = UserGroup::where('user_id', $user->id)->with('group')->get();


        return response()->json(compact('token', 'user', 'groups'));
    }

    // User registration
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);


        return response()->json($request = 201);
    }

    function whoAmI()
    {

        $user = Auth::user();
        $groups = UserGroup::where('user_id', $user->id)->with('group')->get();
        return response()->json(
            compact('user', 'groups')
        );
    }
}
