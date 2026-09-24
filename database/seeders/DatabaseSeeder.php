<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        foreach ($this->genres() as $name) {
            Genre::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name, '-', 'en', ['@' => 'at']), 'status' => 'active'],
            );
        }
    }

    /**
     * @return list<string>
     */
    private function genres(): array
    {
        return ['Fantasi', 'Romansa', 'Aksi', 'Misteri', 'Drama', 'Sci-Fi', 'Horor', 'Komedi'];
    }
}
