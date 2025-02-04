<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAppAdminMiddleware
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
      $role = $payload->get('role');

      if ($role != 'admin') {
        throw new JWTException('not admin');
      }
    } catch (JWTException $e) {
      Log::warning('Token not valid', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token not valid'], 401);
    }

    return $next($request);
  }
}
