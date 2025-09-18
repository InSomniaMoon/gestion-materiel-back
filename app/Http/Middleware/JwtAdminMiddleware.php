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
      $structure = $payload->get('selected_structure');
      $mask = $payload->get('selected_structure.mask');
      $code_structure = $request->query('code_structure');

      // Vérifie si le code_structure commence par le mask
      if (! is_string($mask) || ! is_string($code_structure) || strncmp($code_structure, $mask, strlen($mask)) !== 0) {
        throw new JWTException("Le code_structure $code_structure ne correspond pas au mask $mask");
      }
      if ($structure['code'] !== $code_structure || $structure['role'] !== 'admin') {
        throw new JWTException("tentative d'accès non admin à la structure $code_structure avec le rôle {$structure['role']}");
      }
    } catch (JWTException $e) {
      Log::warning('Token non valide', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Token non valide'], 401);
    } catch (\Exception $e) {
      Log::error('Erreur serveur', ['error' => $e->getMessage()]);

      return response()->json(['error' => 'Erreur serveur'], 500);
    }

    return $next($request);
  }
}
