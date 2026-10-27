<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Producto::firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Mouse Gamer RGB',
                'precio' => 18500.00,
                'stock' => 20,
            ]
        );

        Producto::firstOrCreate(
            ['id' => 2],
            [
                'nombre' => 'Teclado Mecánico',
                'precio' => 45000.00,
                'stock' => 15,
            ]
        );

        Producto::firstOrCreate(
            ['id' => 3],
            [
                'nombre' => 'Monitor 24" 144Hz',
                'precio' => 165000.00,
                'stock' => 8,
            ]
        );
    }
}
