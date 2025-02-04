<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Models\RefreshToken;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
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

    $tokens = $this->generate_tokens($user);
    $token = $tokens['token'];
    $refresh_token = $tokens['refresh_token'];

    $groups = UserGroup::where('user_id', $user->id)->with('group')->get();

    return response()->json(compact('token', 'refresh_token', 'user', 'groups'));
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

  public function resetPassword(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'token' => 'required',
      'email' => 'required|email',
      'password' => 'required|min:8|confirmed',
    ]);

    Log::info($request->all());

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $status = Password::reset(
      $request->only('email', 'password', 'password_confirmation', 'token'),
      function (User $user, string $password) {
        $user->forceFill([
          'password' => Hash::make($password),
        ]);

        $user->save();
      }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => __($status)], 200)
        : response()->json(['message' => __($status)], 400);
  }

  public function whoAmI(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'refresh_token' => 'required|string',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $refresh_token = $request->get('refresh_token');

    $refresh_token = RefreshToken::where('token', $refresh_token)->first();

    if (! $refresh_token) {
      return response()->json(['error' => 'Token not found'], 404);
    }
    $refresh_token->delete();
    if ($refresh_token->expires_at < now()) {
      return response()->json(['error' => 'Token expired'], 401);
    }

    $user = User::find($refresh_token->user_id);

    $tokens = $this->generate_tokens($user);
    $token = $tokens['token'];
    $refresh_token = $tokens['refresh_token'];

    $groups = UserGroup::where('user_id', $user->id)->with('group')->get();

    return response()->json(
      compact('user', 'groups', 'token', 'refresh_token')
    );
  }

  private function generate_tokens($user)
  {
    $user_groups = $user->userGroups()->get();
    $admin_groups =
        $user_groups->filter(function ($group) {
          return $group->role === 'admin';
        });
    // generate a uuidV7
    $refresh_token = uuid7();
    $token = JWTAuth::claims([
      'role' => $user->role,
      'type' => TokenType::ACCESS,
      //   'user_groups' => UserGroup::where('user_id', $user->id)->get(),
      'user_groups' => $user_groups->map(function ($group) {
        return $group->group_id;
      }),
      'admin_groups' => $admin_groups->map(function ($group) {
        return $group->group_id;
      }),
    ])->fromUser($user);

    //save refresh token
    $user->refresh_token = $refresh_token;

    // delete all refresh tokens that are expired
    RefreshToken::where('expires_at', '<', now())->delete();

    RefreshToken::create([
      'token' => $refresh_token,
      'user_id' => $user->id,
      'expires_at' => now()->addDays(7),
    ]);

    return compact('token', 'refresh_token');
  }
}

function uuid7()
{
  static $last_timestamp = 0;
  $unixts_ms = intval(microtime(true) * 1000);
  if ($last_timestamp >= $unixts_ms) {
    $unixts_ms = $last_timestamp + 1;
  }
  $last_timestamp = $unixts_ms;
  $data = random_bytes(10);
  $data[0] = chr((ord($data[0]) & 0x0f) | 0x70); // set version
  $data[2] = chr((ord($data[2]) & 0x3f) | 0x80); // set variant

  return vsprintf(
    '%s%s-%s-%s-%s-%s%s%s',
    str_split(
      str_pad(dechex($unixts_ms), 12, '0', \STR_PAD_LEFT).
          bin2hex($data),
      4
    )
  );
}
