<?php

namespace Database\Seeders;

use App\Models\IpqcCheckItem;
use Illuminate\Database\Seeder;

class IpqcCheckItemSeeder extends Seeder
{
    public function run(): void
    {
        $appearanceItems = [
            'Bending', 'Black Dot', 'Bubble', 'Burn Mark', 'Contamination', 'Corrosive', 'Cracking',
            'Dented', 'Dirty Compound', 'Dirty Dust', 'Dirty Ink', 'Dirty Metalic', 'Dirty Oil',
            'Dirty Scrub', 'Dirty Silicon', 'Dirty Spray', 'Discolor', 'Finger Mark', 'Flashing',
            'Flow Mark', 'Gas Mark', 'Glossy/Shining', 'High Gate', 'Incomplete Printing',
            'Missing Printing', 'No W/I', 'Nut NG', 'Over Cut', 'Over Spray', 'Parting Line',
            'Peel Off', 'Pin Mark', 'Slanting', 'Protective Unstd', 'Rough Spray', 'Scratch',
            'Screw Unpost', 'Short Mold', 'Shortage Qty', 'Silver Streak', 'Stress Mark',
            'Sink Mark', 'Smear Printing', 'Wet Spray', 'Shading Trace', 'Under Cut',
            'Under Spray', 'Under/Over Dimension', 'Water Mark', 'Weld Line',
            'White Mark/Dot', 'Wrong Part',
        ];

        $conditionItems = [
            'Rip/Snap/Snap Lock', 'Pin Hole/Vent Hole', 'Screw Hole', 'Assembly',
            'Packing/Date', 'Marking Part', 'Product Color',
        ];

        $sortOrder = 1;

        foreach ($appearanceItems as $item) {
            IpqcCheckItem::updateOrCreate(
                ['name' => $item, 'category' => 'appearance'],
                ['sort_order' => $sortOrder++, 'is_active' => true]
            );
        }

        foreach ($conditionItems as $item) {
            IpqcCheckItem::updateOrCreate(
                ['name' => $item, 'category' => 'condition'],
                ['sort_order' => $sortOrder++, 'is_active' => true]
            );
        }
    }
}
