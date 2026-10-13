<?php

namespace Database\Seeders;

use App\Models\Taxonomy;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pendidikan', 'Kesehatan', 'Infrastruktur', 'Ekonomi', 'Lingkungan', 'Pelayanan Publik', 'Kebencanaan'] as $name) {
            Taxonomy::firstOrCreate(['kind' => 'category', 'name' => $name]);
        }
        // Admin awal dibuat secara interaktif dengan php artisan app:create-admin.
    }
}
