<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOptionIssue extends Model
{
  protected $fillable = [
    'item_option_id',
    'date_resolution',
    'status',
    'value',
  ];

  protected $hidden = [
    'updated_at',
  ];

  protected $casts = [
    'date_resolution' => 'date',
    'created_at' => 'datetime',
  ];

  // créer un attribut date_declaration qui retourne created_at
  public function getDateDeclarationAttribute()
  {
    return $this->created_at;
  }

  public function comments()
  {
    return $this->hasMany(ItemOptionIssueComment::class);
  }

  public function itemOption()
  {
    return $this->belongsTo(ItemOption::class);
  }
}
