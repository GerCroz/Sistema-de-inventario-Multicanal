<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use App\Services\ProductoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function __construct(
        protected ProductoService $productoService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->productoService->getAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100',
            'precio' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
        ]);

        $producto = $this->productoService->create($datos);

        return response()->json($producto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Producto $producto): JsonResponse
    {
        return response()->json($producto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => 'sometimes|required|string|max:100',
            'precio' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
        ]);

        $actualizado = $this->productoService->update($producto, $datos);

        return response()->json($actualizado);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        $this->productoService->delete($producto);

        return response()->json(null, 204);
    }
}
