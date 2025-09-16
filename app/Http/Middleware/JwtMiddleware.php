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
    if ($request->query('structure_id') === null) {
      return response()->json(['error' => 'le paramètre structure_id est requis'], 422);
    }
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
      Log::error('Token non valide', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token non valide'], 401);
    }

    $structures = $payload->get('user_structures');
    $structure_id = $request->query('structure_id');
    if (! in_array($structure_id, $structures)) {
      return response()->json(['error' => 'Non autorisé'], 403);
    }

    return $next($request);
  }
}
