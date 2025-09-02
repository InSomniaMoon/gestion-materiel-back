<?php

namespace App\Http\Middleware;

use App\Enums\TokenType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
  /**
   * Handle an incoming request.
   *
   * @param \Closure(Request): (Response) $next
   */
  public function handle(Request $request, \Closure $next): Response
  {
    try {
      JWTAuth::parseToken()->authenticate();
      // get claim type
      $payload = JWTAuth::parseToken()->getPayload();
      $claims = $payload->get('type');

      if ($claims != TokenType::ACCESS->value) {
        throw new JWTException('token not access');
      }
      // token
    } catch (JWTException $e) {
      Log::error('Token not valid', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token not valid'], 401);
    }

    $groups = $payload->get('user_groups');
    $group_id = $request->query('group_id');
    if (! in_array($group_id, $groups)) {
      return response()->json(['error' => 'Unauthorized'], 403);
    }

    return $next($request);
  }
}
