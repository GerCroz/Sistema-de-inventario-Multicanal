<?php

namespace Tests\Feature;

use App\Events\ProductoActualizado;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ProductoBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_product_dispatches_broadcast_event(): void
    {
        Event::fake([ProductoActualizado::class]);

        $response = $this->postJson('/api/productos', [
            'nombre' => 'Teclado RGB',
            'precio' => 35000,
            'stock' => 15,
        ]);

        $response->assertCreated();

        Event::assertDispatched(ProductoActualizado::class, function (ProductoActualizado $event) {
            return $event->producto->nombre === 'Teclado RGB'
                && $event->broadcastAs() === 'producto.actualizado'
                && $event->broadcastOn()[0]->name === 'inventario';
        });
    }

    public function test_updating_product_stock_dispatches_broadcast_event(): void
    {
        Event::fake([ProductoActualizado::class]);

        $producto = Producto::factory()->create([
            'nombre' => 'Pad Mouse',
            'stock' => 10,
        ]);

        $response = $this->putJson("/api/productos/{$producto->id}", [
            'stock' => 25,
        ]);

        $response->assertOk();

        Event::assertDispatched(ProductoActualizado::class, function (ProductoActualizado $event) use ($producto) {
            return $event->producto->id === $producto->id
                && $event->producto->stock === 25;
        });
    }

    public function test_event_payload_structure(): void
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Webcam HD',
            'precio' => 25000,
            'stock' => 7,
        ]);

        $event = new ProductoActualizado($producto);
        $payload = $event->broadcastWith();

        $this->assertEquals($producto->id, $payload['id']);
        $this->assertEquals('Webcam HD', $payload['nombre']);
        $this->assertEquals(25000.0, $payload['precio']);
        $this->assertEquals(7, $payload['stock']);
    }
}
