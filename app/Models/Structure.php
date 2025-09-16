<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
  protected $table = 'structures';

  protected $fillable = [
    'code_structure',
    'nom_structure',
    'name',
    'image',
  ];

  public $timestamps = false;

  public function userStructures()
  {
    return $this->hasMany(UserStructure::class, 'structure_id');
  }
}
