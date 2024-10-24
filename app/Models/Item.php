<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    //     -- un item est un objet, une salle, une tente empreintable.
    // CREATE TABLE IF NOT EXISTS `items` (
    //   `id` BIGINT(11) NOT NULL AUTO_INCREMENT,
    //   `name` varchar(255) NOT NULL,
    //   `description` text NOT NULL,
    //   `category` varchar(255) NOT NULL,
    //   `usable` BOOLEAN NOT NULL DEFAULT true,
    //   PRIMARY KEY (`id`)
    // ) ENGINE = InnoDB DEFAULT CHARSET = utf8;

    protected $fillable = [
        'name',
        'description',
        'slug',
        'category',
        'usable'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function itemOptions()
    {
        return $this->hasMany(ItemOption::class);
    }

    public function itemOptionIssues()
    {
        return $this->hasManyThrough(ItemOptionIssue::class, ItemOption::class);
    }
}
