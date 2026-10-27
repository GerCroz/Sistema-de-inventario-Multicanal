<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_products(): void
    {
        Producto::factory()->count(3)->create();

        $response = $this->getJson('/api/productos');

        $response->assertOk()
            ->assertJsonCount(3);
    }

    public function test_can_create_product(): void
    {
        $payload = [
            'nombre' => 'Mouse Gamer',
            'precio' => 18500.50,
            'stock' => 20,
        ];

        $response = $this->postJson('/api/productos', $payload);

        $response->assertCreated()
            ->assertJsonFragment([
                'nombre' => 'Mouse Gamer',
                'stock' => 20,
            ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Mouse Gamer',
            'stock' => 20,
        ]);
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/api/productos', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'precio', 'stock']);
    }

    public function test_can_show_single_product(): void
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Teclado Mecanico',
            'precio' => 45000,
            'stock' => 10,
        ]);

        $response = $this->getJson("/api/productos/{$producto->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $producto->id,
                'nombre' => 'Teclado Mecanico',
            ]);
    }

    public function test_can_update_product(): void
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Monitor 24',
            'precio' => 120000,
            'stock' => 5,
        ]);

        $response = $this->putJson("/api/productos/{$producto->id}", [
            'stock' => 8,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $producto->id,
                'stock' => 8,
            ]);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'stock' => 8,
        ]);
    }

    public function test_can_delete_product(): void
    {
        $producto = Producto::factory()->create();

        $response = $this->deleteJson("/api/productos/{$producto->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('productos', [
            'id' => $producto->id,
        ]);
    }
}
