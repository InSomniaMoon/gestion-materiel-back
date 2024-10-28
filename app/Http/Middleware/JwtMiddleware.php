<?php

namespace App\Http\Middleware;

use App\Enums\TokenType;
use Closure;
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
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            JWTAuth::parseToken()->authenticate();
            // get claim type
            $claims = JWTAuth::getPayload()->get('type');

            if ($claims != TokenType::ACCESS->value) {
                throw new JWTException('token not access');
            }
            // token 
        } catch (JWTException $e) {
            Log::error('Token not valid', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token not valid'], 401);
        }
        return $next($request);
    }
}
