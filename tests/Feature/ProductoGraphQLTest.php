<?php

namespace Tests\Feature;

use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoGraphQLTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_query_products_list_with_selected_fields(): void
    {
        Producto::factory()->create([
            'nombre' => 'Teclado Mecanico',
            'precio' => 45000,
            'stock' => 15,
        ]);

        $query = '
            query {
                productos {
                    nombre
                    precio
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $query]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'productos' => [
                        [
                            'nombre' => 'Teclado Mecanico',
                            'precio' => 45000.0,
                        ],
                    ],
                ],
            ]);

        // Verify that unrequested fields like stock and id are not in the response item
        $data = $response->json('data.productos.0');
        $this->assertArrayNotHasKey('stock', $data);
        $this->assertArrayNotHasKey('id', $data);
    }

    public function test_can_query_single_product_by_id(): void
    {
        $producto = Producto::factory()->create([
            'nombre' => 'Mouse Pad XL',
            'precio' => 8000,
            'stock' => 50,
        ]);

        $query = '
            query($id: ID!) {
                producto(id: $id) {
                    id
                    nombre
                    stock
                }
            }
        ';

        $response = $this->postJson('/graphql', [
            'query' => $query,
            'variables' => ['id' => (string) $producto->id],
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'producto' => [
                        'id' => (string) $producto->id,
                        'nombre' => 'Mouse Pad XL',
                        'stock' => 50,
                    ],
                ],
            ]);
    }

    public function test_can_create_product_via_mutation(): void
    {
        $mutation = '
            mutation {
                crearProducto(nombre: "Auriculares Gamer", precio: 32000.5, stock: 12) {
                    id
                    nombre
                    precio
                    stock
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $mutation]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'crearProducto' => [
                        'nombre' => 'Auriculares Gamer',
                        'precio' => 32000.5,
                        'stock' => 12,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Auriculares Gamer',
            'stock' => 12,
        ]);
    }

    public function test_mutation_validates_input(): void
    {
        $mutation = '
            mutation {
                crearProducto(nombre: "", precio: -10, stock: -5) {
                    id
                }
            }
        ';

        $response = $this->postJson('/graphql', ['query' => $mutation]);

        // Lighthouse returns 200 with 'errors' array on validation error
        $response->assertOk()
            ->assertJsonStructure(['errors']);
    }
}
