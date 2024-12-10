<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ItemOption;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $item = Item::create([
            'name' => 'Salle 10 places',
            'description' => 'Salle 10 places',
            'category' => 'salle',
            'usable' => true,
            'group_id' => 1
        ]);

        ItemOption::create([
            'name' => 'Chaise',
            'description' => 'Chaise',
            'usable' => true,
            'item_id' => $item->id
        ]);

        ItemOption::create([
            'name' => 'Table',
            'description' => 'Table',
            'usable' => true,
            'item_id' => $item->id
        ]);

        ItemOption::create([
            'name' => 'Projecteur',
            'description' => 'Projecteur',
            'usable' => true,
            'item_id' => $item->id
        ]);

        Item::create([
            'name' => 'Tente 4 places',
            'description' => 'Tente 4 places',
            'category' => 'tente',
            'usable' => true,
            'group_id' => 1

        ]);
        Item::create([
            'name' => 'Tente 2 places',
            'description' => 'Tente 2 places',
            'category' => 'tente',
            'usable' => true,
            'group_id' => 1
        ]);

        $item = Item::create([
            'name' => 'Tente 6 places',
            'description' => 'Tente 6 places',
            'category' => 'tente',
            'usable' => true,
            'group_id' => 1
        ]);

        ItemOption::create([
            'name' => 'double toit',
            'description' => 'double toit',
            'usable' => true,
            'item_id' => $item->id
        ]);

        ItemOption::create([
            'name' => 'toit',
            'description' => 'toit',
            'usable' => true,
            'item_id' => $item->id
        ]);

        ItemOption::create([
            'name' => 'piquets',
            'description' => '2 piquets et 1 fétiere',
            'usable' => true,
            'item_id' => $item->id
        ]);


        $item = Item::create([
            'name' => 'Tente 8 places',
            'description' => 'Tente 8 places',
            'category' => 'tente',
            'usable' => true,
            'group_id' => 1
        ]);

        ItemOption::create([
            'name' => 'double toit',
            'description' => 'double toit',
            'usable' => true,
            'item_id' => $item->id
        ]);
        ItemOption::create([
            'name' => 'toit',
            'description' => 'toit',
            'usable' => true,
            'item_id' => $item->id
        ]);
        ItemOption::create([
            'name' => 'piquets',
            'description' => '3 piquets et 2 fétieres',
            'usable' => true,
            'item_id' => $item->id
        ]);
        ItemOption::create([
            'name' => 'Matelas',
            'description' => 'Matelas',
            'usable' => true,
            'item_id' => $item->id
        ]);
        ItemOption::create([
            'name' => 'Sac de couchage',
            'description' => 'Sac de couchage',
            'usable' => true,
            'item_id' => $item->id
        ]);

        $item = Item::create([
            'name' => 'Tente 10 places',
            'description' => 'Tente 10 places',
            'category' => 'tente',
            'usable' => true,
            'group_id' => 1
        ]);
    }

    protected $model = Item::class;
}
