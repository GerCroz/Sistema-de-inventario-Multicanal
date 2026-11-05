<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sistema de Inventario Multicanal</title>
    <!-- Fonts & Tailwind -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        mono: ['JetBrains Mono', 'monospace'],
                    }
                }
            }
        }
    </script>
    <!-- Pusher & Echo via CDN as fallback / standalone guarantee -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js" integrity="sha384-d73s78A1a22C1T2n/0e+3D3sR6zX8aG6X3v1o7H7+yC6qX8y2+2T2w1Z0v8K9T0" crossorigin="anonymous" onerror="this.onerror=null;this.removeAttribute('integrity');this.src='https://js.pusher.com/8.2.0/pusher.min.js'"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.16.1/echo.iife.js" integrity="sha512-1+2u7O6p8z+g9F1Q+1uG4K6f9/3A8vN7cM3f9q6vR5eW2xK8u7G1p+5O9Z6v7aB3w==" crossorigin="anonymous" onerror="this.onerror=null;this.removeAttribute('integrity');this.src='https://cdnjs.cloudflare.com/ajax/libs/laravel-echo/1.16.1/echo.iife.js'"></script>
    <style>
        .flash-update {
            animation: highlightRow 1.6s ease-out;
        }
        @keyframes highlightRow {
            0% { background-color: rgba(59, 130, 246, 0.45); }
            100% { background-color: transparent; }
        }
    </style>
</head>
<body class="h-full flex flex-col font-sans">
    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-950/70 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20 text-lg">
                    📦
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        Inventario Multicanal
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-medium">TP Sistemas</span>
                    </h1>
                    <p class="text-xs text-slate-400">REST • GraphQL • WebSockets (Reverb) • gRPC</p>
                </div>
            </div>

            <!-- WebSocket Status Badge -->
            <div class="flex items-center gap-3">
                <div id="ws-badge" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/20">
                    <span id="ws-indicator" class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                    <span id="ws-status">Conectando a Reverb...</span>
                </div>
                <a href="/security-dashboard" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 transition flex items-center gap-1.5">
                    <span>🛡️</span> Seguridad & Logs
                </a>
                <button onclick="cargarProductos()" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Refrescar
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left 2 Cols: Products Table & Forms -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card: Formulario Nuevo Producto -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-white flex items-center gap-2">
                        <span>➕</span> Nuevo Producto
                    </h2>
                    <span class="text-xs text-slate-400">Emite evento WebSocket en tiempo real</span>
                </div>

                <form id="form-nuevo-producto" onsubmit="crearProducto(event)" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-slate-400 mb-1">Nombre del Producto</label>
                        <input type="text" id="input-nombre" required placeholder="Ej: Mouse Gamer RGB" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Precio ($)</label>
                        <input type="number" step="0.01" min="0" id="input-precio" required placeholder="18500" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-400 mb-1">Stock Inicial</label>
                        <input type="number" min="0" id="input-stock" required placeholder="20" class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500">
                    </div>
                    <div class="sm:col-span-4 flex justify-end">
                        <button type="submit" class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-medium text-sm transition shadow-lg shadow-blue-600/25 flex items-center gap-2">
                            <span>Guardar Producto</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Card: Lista de Productos -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-slate-700/60 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Stock en Tiempo Real</h2>
                        <p class="text-xs text-slate-400">Canal: <code class="font-mono text-blue-400">inventario</code> | Evento: <code class="font-mono text-blue-400">.producto.actualizado</code></p>
                    </div>
                    <div class="text-xs text-slate-400 font-medium">
                        Total items: <span id="contador-productos" class="text-white font-bold">0</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">ID</th>
                                <th class="py-3 px-4">Nombre</th>
                                <th class="py-3 px-4">Precio</th>
                                <th class="py-3 px-4">Stock</th>
                                <th class="py-3 px-4 text-right">Modificar Stock</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-productos" class="divide-y divide-slate-800">
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">
                                    Cargando productos...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Live Event Log & Tech Comparisons -->
        <div class="space-y-6">

            <!-- Console Log of WebSockets -->
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 shadow-xl flex flex-col h-[380px]">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                        <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider font-mono">Reverb Live Stream</h3>
                    </div>
                    <button onclick="limpiarLogs()" class="text-xs text-slate-500 hover:text-slate-300 transition">Limpiar</button>
                </div>
                <div id="log-container" class="flex-1 overflow-y-auto space-y-2 font-mono text-xs text-slate-400 pr-1">
                    <div class="p-2 rounded bg-slate-900/80 border border-slate-800 text-slate-500">
                        Esperando eventos de WebSocket desde Laravel Reverb...
                    </div>
                </div>
            </div>

            <!-- Testing Quick Links -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-3">
                <h3 class="text-sm font-semibold text-white">Canales & Endpoints Activos</h3>
                
                <div class="space-y-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <span class="font-medium text-slate-300">REST API</span>
                        <a href="/api/productos" target="_blank" class="text-blue-400 hover:underline font-mono">GET /api/productos ↗</a>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <span class="font-medium text-slate-300">GraphQL Endpoint</span>
                        <span class="text-emerald-400 font-mono">POST /graphql</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <span class="font-medium text-slate-300">WebSocket Host</span>
                        <span class="text-purple-400 font-mono">ws://127.0.0.1:8080</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <span class="font-medium text-slate-300">gRPC Server</span>
                        <span class="text-amber-400 font-mono">127.0.0.1:50051</span>
                    </div>
                </div>
            </div>

            <!-- Interactive GraphQL Tester Card -->
            <div class="bg-slate-800/60 border border-emerald-500/30 rounded-2xl p-5 shadow-xl space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                        <span class="text-emerald-400">⚡</span> Probador Interactivo GraphQL
                    </h3>
                    <span class="text-[10px] uppercase font-mono px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">POST /graphql</span>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-medium text-slate-400">Consultas Predefinidas:</label>
                    <div class="grid grid-cols-2 gap-1.5 text-xs">
                        <button onclick="setGqlPreset('todos')" class="p-1.5 text-left rounded bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-800 text-[11px] truncate">1. Todos los campos</button>
                        <button onclick="setGqlPreset('soloNombre')" class="p-1.5 text-left rounded bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-800 text-[11px] truncate">2. Solo nombre y precio</button>
                        <button onclick="setGqlPreset('porId')" class="p-1.5 text-left rounded bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-800 text-[11px] truncate">3. Producto ID: 1</button>
                        <button onclick="setGqlPreset('mutacion')" class="p-1.5 text-left rounded bg-slate-900 hover:bg-slate-700 text-slate-300 border border-slate-800 text-[11px] truncate">4. Mutación Crear</button>
                    </div>

                    <textarea id="gql-query-input" rows="5" class="w-full bg-slate-950 border border-slate-700 rounded-lg p-2.5 font-mono text-xs text-emerald-300 focus:outline-none focus:border-emerald-500 font-mono" spellcheck="false">{
  productos {
    id
    nombre
    precio
    stock
  }
}</textarea>

                    <div class="flex items-center justify-between pt-1">
                        <span id="gql-stats" class="text-[11px] text-slate-400 font-mono">Listo para ejecutar</span>
                        <button onclick="ejecutarGraphQL()" class="px-4 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs transition shadow-lg shadow-emerald-600/30 flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                            Ejecutar Query
                        </button>
                    </div>

                    <pre id="gql-response" class="w-full h-32 bg-slate-950 border border-slate-800 rounded-lg p-2.5 font-mono text-[11px] text-slate-300 overflow-y-auto leading-relaxed">Respuesta de GraphQL aparecerá aquí...</pre>
                </div>
            </div>
        </div>

    </main>

    <!-- Client Script -->
    <script>
        let productos = [];
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Setup Laravel Echo with Reverb
        const echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ config('broadcasting.connections.reverb.key', 'mrtwqqwbwlcyrgdu6x4t') }}',
            wsHost: '{{ config('broadcasting.connections.reverb.options.host', 'localhost') }}',
            wsPort: {{ (int) config('broadcasting.connections.reverb.options.port', 8080) }},
            wssPort: {{ (int) config('broadcasting.connections.reverb.options.port', 8080) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
            disableStats: true
        });

        // Connection State Listeners
        echo.connector.pusher.connection.bind('connected', () => {
            const badge = document.getElementById('ws-badge');
            const ind = document.getElementById('ws-indicator');
            const status = document.getElementById('ws-status');
            badge.className = "inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/20";
            ind.className = "w-2 h-2 rounded-full bg-emerald-400";
            status.textContent = "Conectado a Reverb (WebSockets)";
            addLog("system", "Conexión WebSocket establecida con Laravel Reverb en el puerto 8080.");
        });

        echo.connector.pusher.connection.bind('disconnected', () => {
            const badge = document.getElementById('ws-badge');
            const ind = document.getElementById('ws-indicator');
            const status = document.getElementById('ws-status');
            badge.className = "inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-rose-500/10 text-rose-400 border border-rose-500/20";
            ind.className = "w-2 h-2 rounded-full bg-rose-500";
            status.textContent = "Desconectado de Reverb";
            addLog("error", "Desconectado de Reverb. ¿Está corriendo 'php artisan reverb:start'?");
        });

        // Listen on channel 'inventario' for '.producto.actualizado'
        echo.channel('inventario')
            .listen('.producto.actualizado', (evento) => {
                console.log('Evento recibido de Reverb:', evento);
                addLog('event', `[producto.actualizado] ID: ${evento.id} | ${evento.nombre} -> Stock: ${evento.stock}`);
                actualizarFilaEnMemoria(evento);
            });

        // Load Products via REST
        async function cargarProductos() {
            try {
                const res = await fetch('/api/productos');
                productos = await res.json();
                renderTabla();
            } catch (err) {
                console.error('Error al cargar productos:', err);
            }
        }

        // Render Table
        function renderTabla() {
            const tbody = document.getElementById('tabla-productos');
            const contador = document.getElementById('contador-productos');
            contador.textContent = productos.length;

            if (productos.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-500">No hay productos registrados aún.</td></tr>`;
                return;
            }

            tbody.innerHTML = productos.map(p => `
                <tr id="row-prod-${Number(p.id)}" class="hover:bg-slate-800/40 transition">
                    <td class="py-3 px-4 font-mono text-xs text-slate-400">#${Number(p.id)}</td>
                    <td class="py-3 px-4 font-medium text-white">${escapeHtml(p.nombre)}</td>
                    <td class="py-3 px-4 font-mono text-emerald-400">$${Number(p.precio).toFixed(2)}</td>
                    <td class="py-3 px-4">
                        <span id="stock-val-${Number(p.id)}" class="px-2.5 py-1 rounded-md text-xs font-semibold ${p.stock > 10 ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : (p.stock > 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30')}">
                            ${Number(p.stock)} un.
                        </span>
                    </td>
                    <td class="py-3 px-4 text-right">
                        <div class="inline-flex items-center gap-1">
                            <button onclick="cambiarStock(${Number(p.id)}, -1)" class="w-7 h-7 rounded-lg bg-slate-700/80 hover:bg-slate-600 text-slate-200 text-sm font-bold flex items-center justify-center transition">
                                -
                            </button>
                            <button onclick="cambiarStock(${Number(p.id)}, 1)" class="w-7 h-7 rounded-lg bg-slate-700/80 hover:bg-slate-600 text-slate-200 text-sm font-bold flex items-center justify-center transition">
                                +
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        // Update In Memory & Highlight Row on Broadcast
        function actualizarFilaEnMemoria(prodActualizado) {
            const idx = productos.findIndex(p => p.id === prodActualizado.id);
            if (idx !== -1) {
                productos[idx].stock = prodActualizado.stock;
                productos[idx].nombre = prodActualizado.nombre;
                productos[idx].precio = prodActualizado.precio;
            } else {
                productos.unshift(prodActualizado);
            }
            renderTabla();

            // Flash effect
            const row = document.getElementById(`row-prod-${prodActualizado.id}`);
            if (row) {
                row.classList.remove('flash-update');
                void row.offsetWidth; // trigger reflow
                row.classList.add('flash-update');
            }
        }

        // Change Stock via REST API
        async function cambiarStock(id, delta) {
            const prod = productos.find(p => p.id === id);
            if (!prod) return;
            const nuevoStock = Math.max(0, parseInt(prod.stock) + delta);

            try {
                const res = await fetch(`/api/productos/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ stock: nuevoStock })
                });

                if (!res.ok) {
                    const err = await res.json();
                    alert('Error al actualizar: ' + JSON.stringify(err));
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Create Product via REST
        async function crearProducto(e) {
            e.preventDefault();
            const nombre = document.getElementById('input-nombre').value;
            const precio = parseFloat(document.getElementById('input-precio').value);
            const stock = parseInt(document.getElementById('input-stock').value);

            try {
                const res = await fetch('/api/productos', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ nombre, precio, stock })
                });

                if (res.ok) {
                    document.getElementById('form-nuevo-producto').reset();
                    cargarProductos();
                } else {
                    const err = await res.json();
                    alert('Error de validación: ' + JSON.stringify(err));
                }
            } catch (err) {
                console.error(err);
            }
        }

        // Logging helper
        function addLog(tipo, msg) {
            const c = document.getElementById('log-container');
            const now = new Date().toLocaleTimeString();
            const el = document.createElement('div');
            el.className = 'p-2 rounded bg-slate-900/90 border border-slate-800/80 leading-relaxed break-all';
            
            let color = 'text-slate-300';
            if (tipo === 'event') color = 'text-blue-400 font-semibold';
            if (tipo === 'error') color = 'text-rose-400';
            if (tipo === 'system') color = 'text-emerald-400';

            el.innerHTML = `<span class="text-slate-500">[${escapeHtml(now)}]</span> <span class="${color}">${escapeHtml(msg)}</span>`;
            c.appendChild(el);
            c.scrollTop = c.scrollHeight;
        }

        function limpiarLogs() {
            document.getElementById('log-container').innerHTML = '';
        }

        // GraphQL Explorer Presets & Runner
        const gqlPresets = {
            todos: `{\n  productos {\n    id\n    nombre\n    precio\n    stock\n  }\n}`,
            soloNombre: `{\n  productos {\n    nombre\n    precio\n  }\n}`,
            porId: `{\n  producto(id: 1) {\n    id\n    nombre\n    stock\n  }\n}`,
            mutacion: `mutation {\n  crearProducto(nombre: "Camara Web 4K", precio: 45000, stock: 10) {\n    id\n    nombre\n    precio\n    stock\n  }\n}`
        };

        function setGqlPreset(tipo) {
            if (gqlPresets[tipo]) {
                document.getElementById('gql-query-input').value = gqlPresets[tipo];
            }
        }

        async function ejecutarGraphQL() {
            const query = document.getElementById('gql-query-input').value;
            const resBox = document.getElementById('gql-response');
            const stats = document.getElementById('gql-stats');
            resBox.textContent = "Ejecutando...";
            stats.textContent = "Consultando...";

            const t0 = performance.now();
            try {
                const res = await fetch('/graphql', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ query })
                });

                const t1 = performance.now();
                const json = await res.json();
                const text = JSON.stringify(json, null, 2);
                resBox.textContent = text;
                const duration = (t1 - t0).toFixed(1);
                const bytes = new Blob([text]).size;
                stats.textContent = `⏱️ ${duration} ms | 📦 ${bytes} bytes`;

                // If it was a mutation, refresh table
                if (query.trim().startsWith('mutation')) {
                    cargarProductos();
                }
            } catch (err) {
                resBox.textContent = "Error: " + err.message;
                stats.textContent = "Error en petición";
            }
        }

        // Initial Load
        cargarProductos();
    </script>
</body>
</html>
