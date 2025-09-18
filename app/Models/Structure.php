<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Structure extends Model
{
  public const NATIONAL = 'NATIONAL';

  public const TERRITOIRE = 'TERRITOIRE';

  public const GROUPE = 'GROUPE';

  public const UNITE = 'UNITE';

  protected $table = 'structures';

  protected $fillable = [
    'id',
    'name',
    'description',
    'image',
    'code_structure',
    'nom_structure',
    'color',
    'type',
    'parent_code',
  ];

  public $timestamps = false;

  public function members()
  {
    return $this->belongsToMany(User::class, 'user_structures', 'structure_id', 'user_id')
      ->using(UserStructure::class)
      ->withPivot('role');
  }
}
