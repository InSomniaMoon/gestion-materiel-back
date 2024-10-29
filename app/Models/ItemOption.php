<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemOption extends Model
{
    //-- un item option est un accessoire lié à un item
    // CREATE TABLE IF NOT EXISTS `item_options` (
    //     `id` BIGINT NOT NULL AUTO_INCREMENT,
    //     `item_id` BIGINT NOT NULL,
    //     `name` varchar(255) NOT NULL,
    //     `usable` BOOLEAN NOT NULL DEFAULT true,
    //     PRIMARY KEY (`id`),
    //     FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
    //   ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

    protected $fillable = [
        'item_id',
        'name',
        'usable',
        'description'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function itemOptionValues()
    {
        return $this->hasMany(ItemOptionIssue::class);
    }
}
