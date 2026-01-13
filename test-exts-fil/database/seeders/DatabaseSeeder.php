<?php

namespace Database\Seeders;

use App\Models\Source;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Source Initial Data
        $sources = [
            ['id' => 1, 'name' => 'facebook'],
            ['id' => 2, 'name' => 'whatsapp'],
            ['id' => 3, 'name' => 'saas'],
        ];

        foreach ($sources as $source) {
            Source::updateOrCreate(
                ['id' => $source['id']],
                ['name' => $source['name']]
            );
        }

        $this->call(StatusSeeder::class);
        $this->call((PermissionSeeder::class));
        $this->call((EmailTemplatesSeeder::class));
        $this->call((LanguageSeeder::class));
    }
}
