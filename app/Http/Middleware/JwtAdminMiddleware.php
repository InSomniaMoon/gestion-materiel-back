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
      $structures = $payload->get('admin_structures');

      $structures_id = $request->query('structure_id');
      if (! in_array($structures_id, haystack: $structures)) {
        throw new JWTException('not admin');
      }
    } catch (JWTException $e) {
      Log::warning('Token non valide', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token non valide'], 401);
    }

    return $next($request);
  }
}
