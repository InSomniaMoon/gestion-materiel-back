<?php

namespace App\Http\Controllers;

use App\Enums\TokenType;
use App\Models\RefreshToken;
use App\Models\Structure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use function Laravel\Prompts\select;
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

    $tokens = $this->generate_tokens($user, $user->userStructures()->first(), null);
    $token = $tokens['token'];
    $refresh_token = $tokens['refresh_token'];

    $structures = $user->userStructures()->get();

    return response()->json(
      compact('user', 'structures', 'token', 'refresh_token')
    );
  }

  // User registration
  public function register(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'firstname' => 'required|string|max:255',
      'lastname' => 'required|string|max:255',
      'email' => 'required|string|email|max:255|unique:users',
      'password' => 'required|string|min:6',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    User::create([
      'first_name' => $request->get('firstname'),
      'lastname' => $request->get('lastname'),
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

    if ($refresh_token->expires_at < now()) {
      return response()->json(['error' => 'Token expired'], 401);
    }

    $user = $refresh_token->user()->first();

    $tokens = $this->generate_tokens($user, $user->userStructures()->first(), $refresh_token);
    $token = $tokens['token'];
    $refresh_token = $tokens['refresh_token'];

    $structures = $user->userStructures()->get();

    return response()->json(
      compact('user', 'structures', 'token', 'refresh_token')
    );
  }

  private function generate_tokens(User $user, Structure $structure, $existing_refresh_token)
  {
    $refresh_token = $existing_refresh_token->token ?? uuid7();
    $code_mask = match ($structure?->type) {
      Structure::NATIONAL, Structure::UNITE => substr($structure?->code_structure, 0, -2),
      Structure::GROUPE, Structure::TERRITOIRE => $structure?->code_structure,
      default => $structure?->code_structure,
    };
    $token = JWTAuth::claims([
      'role' => $user->role,
      'type' => TokenType::ACCESS,
      'selected_structure' => [
        'code_mask' => $code_mask,
        'id' => $structure->id,
        'code' => $structure->code_structure,
        'role' => $structure->pivot->role,
      ],
    ])->fromUser($user);

    //save refresh token
    $user->refresh_token = $refresh_token;

    // delete all refresh tokens that are expired
    RefreshToken::where('expires_at', '<', now())->delete();

    if ($existing_refresh_token) {
      $existing_refresh_token->expires_at = now()->addDays(7);
      $existing_refresh_token->save();
    } else {
      RefreshToken::create([
        'token' => $refresh_token,
        'user_id' => $user->id,
        'expires_at' => now()->addDays(7),
      ]);
    }

    return compact('token', 'refresh_token');
  }

  public function generateTokenForSelectedStructure(Request $request, Structure $structure)
  {
    $user = $request->user();

    $refresh_token = RefreshToken::where('token', $request->get('refresh_token'))->first();

    if (! $refresh_token) {
      return response()->json(['error' => 'Token not found'], 404);
    }
    if ($refresh_token->expires_at < now()) {
      return response()->json(['error' => 'Token expired'], 401);
    }

    $structure = $user->userStructures()->where('id', $structure->id)->first();
    if (! $structure) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    return $this->generate_tokens($user, $structure, $refresh_token);
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
