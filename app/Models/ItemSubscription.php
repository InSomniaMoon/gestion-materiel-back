<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemSubscription extends Model
{
    //
    protected  $fillable = [
        'item_id',
        'user_id',
        'status',
        'name',
        'start_date',
        'end_date',
    ];

    // casts
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
