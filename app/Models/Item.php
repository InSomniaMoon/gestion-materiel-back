<?php

namespace App\Models;

use Database\Factories\ItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;
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
        'category',
        'usable',
        'group_id'
    ];

    protected $hidden = [
        'created_at',
        'updated_at'
    ];

    public function options()
    {
        return $this->hasMany(ItemOption::class, 'item_id');
    }

    public function optionIssues()
    {
        return $this->hasManyThrough(ItemOptionIssue::class, ItemOption::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(ItemSubscription::class, 'item_id');
    }

    protected static function newFactory()
    {
        return ItemFactory::new();
    }
}
