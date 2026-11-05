<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login de Seguridad • Sistema Multicanal</title>
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
</head>
<body class="h-full flex flex-col font-sans">
    <!-- Header -->
    <header class="border-b border-slate-800 bg-slate-950/70 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center font-bold text-white shadow-lg shadow-blue-500/20 text-lg">
                    🛡️
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        Seguridad & Auditoría de Accesos
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-medium">TP Logging</span>
                    </h1>
                    <p class="text-xs text-slate-400">Detección de Fuerza Bruta • Bloqueo por IP y Cuenta • Canal Rotativo</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="/" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition flex items-center gap-1.5">
                    <span>📦</span> Volver a Inventario
                </a>
                <a href="/security-dashboard" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-400 border border-indigo-500/20 transition flex items-center gap-1.5">
                    <span>📊</span> Panel de Monitoreo
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left 7 Cols: Login Card -->
        <div class="lg:col-span-7 bg-slate-800/60 border border-slate-700/60 rounded-2xl p-6 sm:p-8 shadow-xl">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-white">Prueba de autenticación</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Ingresa tus credenciales o simula intentos fallidos para verificar las reglas</p>
                </div>
                <span class="text-xs font-mono px-2.5 py-1 rounded-md bg-slate-900 text-blue-400 border border-slate-700">POST /security-login</span>
            </div>

            <!-- Alert Feedback Box -->
            <div id="alert-box" class="hidden mb-5 p-4 rounded-xl border text-sm">
                <div class="flex items-center justify-between">
                    <span id="alert-title" class="font-semibold"></span>
                    <span id="alert-status" class="text-xs font-mono px-2 py-0.5 rounded"></span>
                </div>
                <p id="alert-message" class="text-xs mt-1 opacity-90"></p>
            </div>

            <form id="security-login-form" method="POST" action="/security-login" onsubmit="enviarLogin(event)" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-medium text-slate-400 mb-1.5">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="test@example.com" required placeholder="usuario@ejemplo.com"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                </div>

                <div>
                    <label for="password" class="block text-xs font-medium text-slate-400 mb-1.5">Contraseña</label>
                    <input type="password" id="password" name="password" value="password" required placeholder="••••••••"
                        class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition">
                </div>

                <div class="pt-2 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="setCredenciales('test@example.com', 'password')" class="px-2.5 py-1.5 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 border border-emerald-500/20 text-xs font-medium transition">
                            ✓ Usar Válidas
                        </button>
                        <button type="button" onclick="setCredenciales('test@example.com', 'clave-erronea')" class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 text-xs font-medium transition">
                            ✕ Usar Incorrectas
                        </button>
                    </div>

                    <button type="submit" id="btn-submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white font-semibold text-sm transition shadow-lg shadow-blue-600/25 flex items-center gap-2">
                        <span>Ingresar</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right 5 Cols: Rules & Console Reference -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl p-5 shadow-xl space-y-3">
                <h3 class="text-sm font-semibold text-white flex items-center gap-2">
                    <span>📋</span> Umbrales de Detección (5 min)
                </h3>
                <div class="space-y-2 text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between">
                        <span class="text-slate-300">Login válido</span>
                        <span class="font-mono text-emerald-400">INFO • success</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between">
                        <span class="text-slate-300">1–2 fallos</span>
                        <span class="font-mono text-amber-400">WARNING • failed</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between">
                        <span class="text-slate-300">3–4 fallos</span>
                        <span class="font-mono text-orange-400">ALERT • fuerza bruta</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between">
                        <span class="text-slate-300">≥ 5 fallos</span>
                        <span class="font-mono text-rose-400">CRITICAL • bloqueo 10m</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-slate-900/70 border border-slate-800 flex items-center justify-between">
                        <span class="text-slate-300">IP / Cuenta bloqueada</span>
                        <span class="font-mono text-purple-400">HTTP 429 • blocked</span>
                    </div>
                </div>
            </div>

            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 shadow-xl">
                <div class="flex items-center justify-between pb-2.5 border-b border-slate-800 mb-2.5">
                    <span class="text-xs font-semibold text-slate-300 uppercase tracking-wider font-mono">Respuesta del Servidor</span>
                    <span class="text-[11px] font-mono text-slate-500">storage/logs/security-*.log</span>
                </div>
                <pre id="response-log" class="font-mono text-xs text-slate-400 overflow-x-auto leading-relaxed">Esperando intento de autenticación...</pre>
            </div>
        </div>

    </main>

    <script>
        function setCredenciales(email, password) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = password;
        }

        async function enviarLogin(e) {
            e.preventDefault();
            const form = document.getElementById('security-login-form');
            const alertBox = document.getElementById('alert-box');
            const alertTitle = document.getElementById('alert-title');
            const alertStatus = document.getElementById('alert-status');
            const alertMessage = document.getElementById('alert-message');
            const responseLog = document.getElementById('response-log');

            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await res.json();
                responseLog.textContent = JSON.stringify({ status: res.status, body: data }, null, 2);

                alertBox.classList.remove('hidden', 'bg-emerald-500/10', 'border-emerald-500/30', 'text-emerald-300', 'bg-amber-500/10', 'border-amber-500/30', 'text-amber-300', 'bg-rose-500/10', 'border-rose-500/30', 'text-rose-300');

                if (res.status === 200) {
                    alertBox.classList.add('bg-emerald-500/10', 'border-emerald-500/30', 'text-emerald-300');
                    alertTitle.textContent = '✓ Acceso Autorizado';
                    alertStatus.textContent = 'HTTP 200';
                    alertMessage.innerHTML = '';
                    alertMessage.appendChild(document.createTextNode((data.message || '') + '. Redirigiendo al '));
                    const link = document.createElement('a');
                    link.href = '/security-dashboard';
                    link.className = 'underline font-semibold';
                    link.textContent = 'Panel de Monitoreo';
                    alertMessage.appendChild(link);
                    alertMessage.appendChild(document.createTextNode('...'));
                    setTimeout(() => window.location.href = '/security-dashboard', 1000);
                } else if (res.status === 429) {
                    alertBox.classList.add('bg-rose-500/10', 'border-rose-500/30', 'text-rose-300');
                    alertTitle.textContent = '⛔ Bloqueo Temporal Activo';
                    alertStatus.textContent = 'HTTP 429';
                    alertMessage.textContent = data.message;
                } else {
                    alertBox.classList.add('bg-amber-500/10', 'border-amber-500/30', 'text-amber-300');
                    alertTitle.textContent = '⚠️ Fallo de Autenticación';
                    alertStatus.textContent = `HTTP ${res.status}`;
                    alertMessage.textContent = data.message || 'Verifica las credenciales ingresadas.';
                }
            } catch (err) {
                responseLog.textContent = 'Error de red: ' + err.message;
            }
        }
    </script>
</body>
</html>
