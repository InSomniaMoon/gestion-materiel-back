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
    // if the featureClick already exists, update the count
    $featureClick = FeatureClick::where('feature_id', $feature->id)
      ->where('user_id', Auth::id())
      ->first();

    if ($featureClick) {
      $featureClick->increment('clicks');
    } else {
      $featureClick = new FeatureClick();
      $featureClick->feature_id = $feature->id;
      $featureClick->user_id = Auth::id();
      $featureClick->clicks = 1;
      $featureClick->save();
    }

    return response()->json($feature);
  }
}
