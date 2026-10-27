# 📦 Sistema de Inventario Multicanal

Aplicación desarrollada en **Laravel 12 / PHP 8.4** para la gestión de productos y exposición de datos utilizando 4 mecanismos de comunicación heterogéneos: **REST**, **GraphQL**, **WebSockets (Laravel Reverb)** y **gRPC**.

---

## 🚀 Arquitectura y Tecnologías Implementadas

1. **REST API**: Endpoint estándar JSON CRUD con validaciones y códigos HTTP (`GET`, `POST`, `PUT`, `DELETE`).
2. **GraphQL**: Implementado con **Lighthouse**, permitiendo selección granular de campos (eliminación de over-fetching), queries y mutaciones para creación y actualización de stock.
3. **WebSockets en Tiempo Real**: Infraestructura nativa con **Laravel Reverb**, emitiendo el evento `ProductoActualizado` en el canal público `inventario` para sincronizar navegadores en vivo sin recargar la página.
4. **gRPC (Microservicio)**: Servicio RPC sobre **HTTP/2 y Protocol Buffers binario** (`grpc/producto.proto`), con servidor y cliente de benchmarking en Node.js y Python.
5. **Capa de Servicios**: `App\Services\ProductoService` centraliza las reglas de negocio y asegura que cualquier modificación de stock emita los eventos de tiempo real sin importar la interfaz de entrada.

---

## 🛠️ Instalación y Configuración

```bash
# 1. Clonar el repositorio
git clone https://github.com/GerCroz/Sistema-de-inventario-Multicanal.git
cd Sistema-de-inventario-Multicanal

# 2. Instalar dependencias PHP y Node
composer install
npm install

# 3. Configurar entorno y base de datos
cp .env.example .env
php artisan key:generate
php artisan migrate --seed

# 4. Compilar assets de frontend
npm run build
```

---

## 🖥️ Guía de Ejecución

Para iniciar todos los servicios del ecosistema multicanal:

```bash
# Terminal 1: Servidor Web Laravel (Frontend, REST y GraphQL)
php artisan serve

# Terminal 2: Servidor de WebSockets (Laravel Reverb)
php artisan reverb:start

# Terminal 3: Cola de Trabajos de Laravel (Broadcast en segundo plano)
php artisan queue:work

# Terminal 4: Microservicio gRPC
node grpc/server.cjs
```

---

## 🧪 Pruebas y Verificación

### 1. Frontend Reactivo & WebSockets
Abre en tu navegador [http://127.0.0.1:8000](http://127.0.0.1:8000) en dos ventanas contiguas. Modifica el stock de cualquier producto con `+` o `-` y observa cómo ambas ventanas se actualizan en vivo con efecto flash y registro en la consola Reverb.

### 2. REST API
```bash
# Listar todos los productos
curl http://127.0.0.1:8000/api/productos

# Crear un producto
curl -X POST http://127.0.0.1:8000/api/productos \
  -H "Content-Type: application/json" \
  -d '{"nombre": "Mouse Gamer", "precio": 18500, "stock": 20}'
```

### 3. GraphQL
Puedes probarlo desde el **Probador Interactivo** en [http://127.0.0.1:8000](http://127.0.0.1:8000) o mediante POST a `http://127.0.0.1:8000/graphql`:
```graphql
query {
  productos {
    nombre
    precio
  }
}
```

### 4. gRPC vs REST Benchmark
Ejecuta el cliente de prueba para comparar directamente latencia y tamaño de respuesta entre Protocol Buffers y JSON:
```bash
node grpc/client.cjs 1
```

### 5. Suite de Pruebas Automatizadas
```bash
php artisan test --compact
# 15 tests pasando (REST, GraphQL, Event Broadcasting)
```

---

## 📊 Tabla Comparativa

| Criterio | REST (HTTP/1.1 + JSON) | GraphQL | gRPC (HTTP/2 + Protobuf) | WebSockets (Reverb) |
|---|---|---|---|---|
| **Obtener producto** | `GET /api/productos/{id}` | `query { producto(id: 1) { ... } }` | RPC `ObtenerProducto(ProductoRequest)` | Pub/Sub (No para consulta puntual) |
| **Selección de campos** | ❌ Objeto completo | ✅ **Nativo y óptimo** | ⚠️ Estructura fija en `.proto` | ❌ Servidor decide el evento |
| **Crear producto** | `POST /api/productos` (201) | `mutation { crearProducto(...) }` | RPC `CrearProducto` | ❌ Inadecuado para CRUD |
| **Cambios en vivo** | ❌ Requiere polling | ⚠️ Requiere Subscriptions | ⚠️ Server-Streaming | ✅ **Nativo y en tiempo real (Push)** |
| **Tamaño de respuesta** | **~152 bytes** | **~65 - 80 bytes** | **~32 bytes (Ultra compacto)** | **~130 bytes** |
| **Tiempo de respuesta** | Moderado (~18 ms) | Moderado (~25 ms) | **Ultra rápido (~2.5 ms binario)** | Inmediato (<5 ms entrega push) |

---

## 📄 Licencia
Proyecto académico bajo licencia [MIT](LICENSE).
