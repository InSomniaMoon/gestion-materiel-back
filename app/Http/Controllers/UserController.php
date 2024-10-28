<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    //

    // function createJwtAlwaysAdmin(Request $request)
    // {
    //     // sets expiratoion time to 10 years
    //     $token =  JWTAuth::setTTL(60 * 24 * 365 * 10)->fromUser($request->user());

    //     return response()->json(['token' => $token]);
    // }
}
