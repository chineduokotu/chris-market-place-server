<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Home Cleaning', 'slug' => 'home-cleaning'],
            ['name' => 'Plumbing', 'slug' => 'plumbing'],
            ['name' => 'Electrical', 'slug' => 'electrical'],
            ['name' => 'Tutoring', 'slug' => 'tutoring'],
            ['name' => 'Fitness Training', 'slug' => 'fitness-training'],
            ['name' => 'Photography', 'slug' => 'photography'],
            ['name' => 'Web Development', 'slug' => 'web-development'],
            ['name' => 'Graphic Design', 'slug' => 'graphic-design'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
