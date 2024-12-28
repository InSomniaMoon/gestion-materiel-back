<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureClick extends Model
{
  protected $fillable = [
    'feature_id',
    'user_id',
  ];
}
