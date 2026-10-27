<?php

namespace App\GraphQL\Mutations;

use App\Models\Producto;
use App\Services\ProductoService;

class CrearProducto
{
    public function __construct(
        protected ProductoService $productoService
    ) {}

    /**
     * @param  null  $_
     * @param  array{nombre: string, precio: float, stock: int}  $args
     */
    public function __invoke($_, array $args): Producto
    {
        return $this->productoService->create([
            'nombre' => $args['nombre'],
            'precio' => $args['precio'],
            'stock' => $args['stock'],
        ]);
    }
}
