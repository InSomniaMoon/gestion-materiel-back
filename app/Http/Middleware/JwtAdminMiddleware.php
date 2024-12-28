<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAdminMiddleware
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
      // get role claim from token
      $payload = JWTAuth::parseToken()->getPayload();
      $groups = $payload->get('admin_groups');

      $group_id = $request->query('group_id');
      if (! in_array($group_id, $groups)) {
        throw new JWTException('not admin');
      }
    } catch (JWTException $e) {
      Log::warning('Token not valid', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token not valid'], 401);
    }

    return $next($request);
  }
}
