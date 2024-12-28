<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use App\Models\FeatureClick;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeatureClickController extends Controller
{
  public function click(Feature $feature)
  {
    FeatureClick::updateOrCreate([
      'feature_id' => $feature->id,
      'user_id' => Auth::user()->id,
    ], [
      'clicks' => DB::raw('clicks + 1'),
    ]);

    return response()->json($feature);
  }
}
