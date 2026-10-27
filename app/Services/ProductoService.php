<?php

namespace App\Services;

use App\Events\ProductoActualizado;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Collection;

class ProductoService
{
    /**
     * Get all products.
     *
     * @return Collection<int, Producto>
     */
    public function getAll(): Collection
    {
        return Producto::all();
    }

    /**
     * Find a product by its ID.
     */
    public function findById(int $id): ?Producto
    {
        return Producto::find($id);
    }

    /**
     * Create a new product.
     *
     * @param  array{nombre: string, precio: float|numeric-string, stock: int}  $data
     */
    public function create(array $data): Producto
    {
        $producto = Producto::create($data);

        // Emit WebSocket broadcast if the event class exists
        if (class_exists(ProductoActualizado::class)) {
            event(new ProductoActualizado($producto));
        }

        return $producto;
    }

    /**
     * Update an existing product.
     *
     * @param  array{nombre?: string, precio?: float|numeric-string, stock?: int}  $data
     */
    public function update(Producto $producto, array $data): Producto
    {
        $producto->update($data);

        if (class_exists(ProductoActualizado::class)) {
            event(new ProductoActualizado($producto));
        }

        return $producto;
    }

    /**
     * Update product stock.
     */
    public function updateStock(Producto $producto, int $newStock): Producto
    {
        $producto->update(['stock' => $newStock]);

        if (class_exists(ProductoActualizado::class)) {
            event(new ProductoActualizado($producto));
        }

        return $producto;
    }

    /**
     * Delete a product.
     */
    public function delete(Producto $producto): bool
    {
        return (bool) $producto->delete();
    }
}
