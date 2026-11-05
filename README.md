# 📦 Sistema de Inventario Multicanal + 🛡️ Módulo de Logging y Seguridad

Aplicación desarrollada en **Laravel 12/13 (PHP 8.3+)** que integra dos módulos principales:
1. **Sistema de Inventario Multicanal**: Gestión de productos y sincronización en tiempo real mediante **REST**, **GraphQL**, **WebSockets (Laravel Reverb)** y **gRPC**.
2. **Logging y Detección de Incidentes de Seguridad**: Sistema de auditoría de accesos, detección de ataques de fuerza bruta, bloqueo automático temporal por IP y por cuenta, canal de logs rotativo independiente y panel de monitoreo (SIEM) con exportación de evidencias en CSV.

---

## 🚀 Arquitectura y Tecnologías Implementadas

### 📦 Módulo 1: Inventario Multicanal
1. **REST API**: Endpoint estándar JSON CRUD con validaciones y códigos HTTP (`GET`, `POST`, `PUT`, `DELETE`).
2. **GraphQL**: Implementado con **Lighthouse**, permitiendo selección granular de campos (eliminación de over-fetching), queries y mutaciones para creación y actualización de stock.
3. **WebSockets en Tiempo Real**: Infraestructura nativa con **Laravel Reverb**, emitiendo el evento `ProductoActualizado` en el canal público `inventario` para sincronizar navegadores en vivo sin recargar la página.
4. **gRPC (Microservicio)**: Servicio RPC sobre **HTTP/2 y Protocol Buffers binario** (`grpc/producto.proto`), con servidor y cliente de benchmarking en Node.js y Python.
5. **Capa de Servicios**: `App\Services\ProductoService` centraliza las reglas de negocio y asegura que cualquier modificación de stock emita los eventos de tiempo real sin importar la interfaz de entrada.

### 🛡️ Módulo 2: Logging y Detección de Incidentes de Seguridad
1. **Canal de Logs Independiente (`security`)**: Configurado en `config/logging.php` con rotación diaria (`driver: daily`, retención de 30 días) en `storage/logs/security-YYYY-MM-DD.log`.
2. **Tabla de Auditoría (`login_attempts`)**: Registro persistente de cada intento de autenticación (`identifier`, `ip_address`, `status`, `user_agent`, `created_at`) con índice compuesto para consultas rápidas de ventana temporal.
3. **Motor de Detección y Respuesta Automática (`SecurityLoginController`)**:
   - **Login válido**: Registra estado `success`, regenera sesión y emite log nivel `INFO`.
   - **1–2 fallos en 5 minutos**: Registra estado `failed` y emite log nivel `WARNING`.
   - **3–4 fallos en 5 minutos**: Emite alerta temprana nivel `ALERT` (`Posible ataque de fuerza bruta`).
   - **≥ 5 fallos en 5 minutos (por IP o por Cuenta)**: Activa bloqueo temporal de **10 minutos** en caché (`security:block:<sha256>`) y emite log nivel `CRITICAL`.
   - **Intentos durante bloqueo activo**: Rechaza inmediatamente con **HTTP 429 (Too Many Requests)**, registra evento `blocked` y emite `WARNING`.
4. **Panel de Monitoreo SIEM (`SecurityDashboardController`)**:
   - Protegido mediante autenticación y autorización (`Gate: viewSecurityDashboard`).
   - Indicadores KPI de las últimas 24 horas (`Exitosos`, `Fallidos`, `Bloqueados`).
   - Tablas en tiempo real de **IPs Sospechosas** y **Cuentas bajo Ataque** en los últimos 5 minutos clasificadas en `ALERTA` o `CRÍTICO`.
   - **Exportación de Evidencias**: Descarga directa del historial completo en formato CSV (`/security-dashboard/export`).

---

## 📋 Requisitos Previos

- **PHP** >= 8.3 (con extensiones `pdo_sqlite`, `mbstring`, `openssl`, `curl`)
- **Composer** 2.x
- **Node.js** >= 18.x y **npm** (para assets y microservicio gRPC)

---

## 🛠️ Instalación Paso a Paso (Clonar y Ejecutar)

Puedes instalar todo automáticamente con el script de setup o seguir los pasos manuales:

### Opción A: Instalación rápida en un comando
```bash
git clone https://github.com/GerCroz/Sistema-de-inventario-Multicanal.git
cd Sistema-de-inventario-Multicanal
composer run setup
php artisan db:seed
```

### Opción B: Instalación manual paso a paso
```bash
# 1. Clonar el repositorio
git clone https://github.com/GerCroz/Sistema-de-inventario-Multicanal.git
cd Sistema-de-inventario-Multicanal

# 2. Instalar dependencias de PHP y Node.js
composer install
npm install

# 3. Crear archivo de entorno y generar clave de aplicación
cp .env.example .env
php artisan key:generate

# 4. Crear archivo de base de datos SQLite (si no existe) y ejecutar migraciones + seeders
# En Linux/macOS:
touch database/database.sqlite
# En Windows (PowerShell):
New-Item -ItemType File -Path database/database.sqlite -Force

php artisan migrate --seed
php artisan config:clear

# 5. Compilar assets de frontend
npm run build
```

> **👤 Usuario de prueba creado por el Seeder (`DatabaseSeeder`):**
> - **Email:** `test@example.com`
> - **Contraseña:** `password`
> *(Este usuario tiene permisos en el Gate `viewSecurityDashboard` para acceder al Panel de Monitoreo).*

---

## 🖥️ Guía de Ejecución

Para levantar el proyecto, abre las terminales necesarias según lo que desees probar:

```bash
# Terminal 1 (Obligatoria): Servidor Web Laravel (Inventario, Login de Seguridad, Dashboard, REST y GraphQL)
php artisan serve

# Terminal 2 (Opcional - para WebSockets en vivo en Inventario): Servidor Laravel Reverb
php artisan reverb:start

# Terminal 3 (Opcional - para colas de broadcast): Worker de Laravel
php artisan queue:work

# Terminal 4 (Opcional - para pruebas gRPC): Microservicio gRPC
node grpc/server.cjs
```

### 🔗 Rutas Principales en el Navegador
- **Dashboard de Inventario Multicanal:** [http://127.0.0.1:8000/](http://127.0.0.1:8000/)
- **Formulario de Login de Seguridad:** [http://127.0.0.1:8000/security-login](http://127.0.0.1:8000/security-login)
- **Panel de Monitoreo de Seguridad (SIEM):** [http://127.0.0.1:8000/security-dashboard](http://127.0.0.1:8000/security-dashboard) *(requiere iniciar sesión primero en `/security-login` con `test@example.com` / `password`)*
- **Exportación de Evidencias CSV:** [http://127.0.0.1:8000/security-dashboard/export](http://127.0.0.1:8000/security-dashboard/export)

---

## 🧪 Pruebas y Verificación

### 1. Pruebas del Módulo de Seguridad y Logging
1. Ingresa a [http://127.0.0.1:8000/security-login](http://127.0.0.1:8000/security-login).
2. Usa los botones rápidos **`✕ Usar Incorrectas`** y presiona **Ingresar** varias veces:
   - En los intentos **1 y 2**: Verás respuesta `HTTP 401` y registros `WARNING` en `storage/logs/security-YYYY-MM-DD.log`.
   - En los intentos **3 y 4**: Se disparará además un evento `ALERT` (`Posible ataque de fuerza bruta`).
   - En el intento **5**: Se registrará un evento `CRITICAL` y la IP/cuenta quedará bloqueada por 10 minutos.
   - En el intento **6**: Recibirás `HTTP 429 (Acceso temporalmente restringido)` con estado `blocked`.
3. Para reiniciar el bloqueo durante tus pruebas manuales, puedes limpiar la caché ejecutando:
   ```bash
   php artisan cache:clear
   ```
4. Luego haz clic en **`✓ Usar Válidas`** (`test@example.com` / `password`) e ingresa para ser redirigido al **Panel de Monitoreo** (`/security-dashboard`), donde podrás ver las métricas, IPs sospechosas, cuentas atacadas y descargar el archivo `security-evidence.csv`.

### 2. Frontend Reactivo & WebSockets (Inventario)
Abre en tu navegador [http://127.0.0.1:8000](http://127.0.0.1:8000) en dos ventanas contiguas. Modifica el stock de cualquier producto con `+` o `-` y observa cómo ambas ventanas se actualizan en vivo con efecto flash y registro en la consola Reverb.

### 3. REST API
```bash
# Listar todos los productos
curl http://127.0.0.1:8000/api/productos

# Crear un producto
curl -X POST http://127.0.0.1:8000/api/productos \
  -H "Content-Type: application/json" \
  -d '{"nombre": "Mouse Gamer", "precio": 18500, "stock": 20}'
```

### 4. GraphQL
Puedes probarlo desde el **Probador Interactivo** en [http://127.0.0.1:8000](http://127.0.0.1:8000) o mediante POST a `http://127.0.0.1:8000/graphql`:
```graphql
query {
  productos {
    nombre
    precio
  }
}
```

### 5. gRPC vs REST Benchmark
Ejecuta el cliente de prueba para comparar directamente latencia y tamaño de respuesta entre Protocol Buffers y JSON:
```bash
node grpc/client.cjs 1
```

### 6. Suite de Pruebas Automatizadas (PHPUnit)
El proyecto cuenta con pruebas automatizadas tanto para la arquitectura multicanal como para todos los escenarios obligatorios de seguridad (login válido, warnings, alertas de fuerza bruta, bloqueo por IP/cuenta, HTTP 429, expiración del bloqueo a los 10 minutos, dashboard y exportación CSV):

```bash
# Ejecutar toda la suite de tests
php artisan test --compact

# Ejecutar únicamente los tests del práctico de Logging y Seguridad
php artisan test --compact tests/Feature/SecurityLoginTest.php
```

---

## 📊 Tabla Comparativa (Módulo Multicanal)

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
