<?php

namespace Database\Seeders;

use App\Models\EmergencyCategory;
use Illuminate\Database\Seeder;

class EmergencyCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Injuries',
                'slug' => 'injuries',
                'icon' => 'bandage',
                'description' => 'Cuts, wounds, broken bones, and physical trauma',
                'sort_order' => 1,
            ],
            [
                'name' => 'Poisoning',
                'slug' => 'poisoning',
                'icon' => 'skull',
                'description' => 'Toxic substances, plants, foods, and chemicals',
                'sort_order' => 2,
            ],
            [
                'name' => 'Breathing Problems',
                'slug' => 'breathing-problems',
                'icon' => 'lungs',
                'description' => 'Choking, difficulty breathing, respiratory distress',
                'sort_order' => 3,
            ],
            [
                'name' => 'Seizures',
                'slug' => 'seizures',
                'icon' => 'activity',
                'description' => 'Convulsions, fits, and neurological emergencies',
                'sort_order' => 4,
            ],
            [
                'name' => 'Heat & Cold',
                'slug' => 'heat-cold',
                'icon' => 'thermometer',
                'description' => 'Heatstroke, hypothermia, and temperature-related issues',
                'sort_order' => 5,
            ],
            [
                'name' => 'Digestive Issues',
                'slug' => 'digestive-issues',
                'icon' => 'stomach',
                'description' => 'Vomiting, diarrhea, bloat, and GI emergencies',
                'sort_order' => 6,
            ],
            [
                'name' => 'Eye & Ear',
                'slug' => 'eye-ear',
                'icon' => 'eye',
                'description' => 'Eye injuries, ear infections, and sensory emergencies',
                'sort_order' => 7,
            ],
            [
                'name' => 'Birth & Pregnancy',
                'slug' => 'birth-pregnancy',
                'icon' => 'heart',
                'description' => 'Labor complications and pregnancy emergencies',
                'sort_order' => 8,
            ],
        ];

        foreach ($categories as $category) {
            EmergencyCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
