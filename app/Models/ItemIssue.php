<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemIssue extends Model
{
  protected $fillable = [
    'item_id',
    'resolution_date',
    'status',
    'value',
    'reported_by',
  ];

  protected $hidden = [
    'updated_at',
  ];

  protected $casts = [
    'resolution_date' => 'date',
    'created_at' => 'datetime',
  ];

  // créer un attribut date_declaration qui retourne created_at
  public function getDateDeclarationAttribute()
  {
    return $this->created_at;
  }

  public function comments()
  {
    return $this->hasMany(ItemIssueComment::class);
  }

  public function item()
  {
    return $this->belongsTo(Item::class);
  }

  public function reporter()
  {
    return $this->belongsTo(User::class, 'reported_by');
  }
}
