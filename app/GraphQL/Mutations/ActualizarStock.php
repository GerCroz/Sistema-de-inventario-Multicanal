<?php

namespace App\GraphQL\Mutations;

use App\Models\Producto;
use App\Services\ProductoService;

class ActualizarStock
{
    public function __construct(
        protected ProductoService $productoService
    ) {}

    /**
     * @param  null  $_
     * @param  array{id: int|string, stock: int}  $args
     */
    public function __invoke($_, array $args): ?Producto
    {
        $producto = $this->productoService->findById((int) $args['id']);

        if (! $producto) {
            return null;
        }

        return $this->productoService->updateStock($producto, $args['stock']);
    }
}
