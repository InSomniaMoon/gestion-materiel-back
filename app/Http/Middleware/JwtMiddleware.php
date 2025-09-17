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
    if ($request->query('code_structure') === null) {
      return response()->json(['error' => 'le paramètre code_structure est requis'], 422);
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

    $loaded_structure_code = $payload->get('selected_structure.code');
    $structure_code = $request->query('code_structure');
    if ($loaded_structure_code !== $structure_code) {
      return response()->json(['error' => 'Non autorisé'], 403);
    }

    return $next($request);
  }
}
