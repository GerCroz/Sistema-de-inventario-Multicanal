<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de seguridad • Sistema Multicanal</title>
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
                        Panel de seguridad
                        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium">SIEM & Auditoría</span>
                    </h1>
                    <p class="text-xs text-slate-400">Monitoreo de Eventos (24h) • Detección de Fuerza Bruta (Ventana 5m)</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('security-dashboard.export') }}" class="px-3.5 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-lg shadow-emerald-600/25 flex items-center gap-1.5">
                    <span>⬇️</span> Exportar evidencias (CSV)
                </a>
                <a href="/" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition flex items-center gap-1.5">
                    <span>📦</span> Inventario
                </a>
                <form method="POST" action="{{ route('security.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 text-xs font-medium rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/20 transition">
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        
        <!-- KPI Summary Cards (24h) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="bg-slate-800/60 border border-emerald-500/30 rounded-2xl p-5 shadow-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Accesos Exitosos (24h)</p>
                    <p class="text-2xl font-bold text-emerald-400 mt-1">Exitosos: {{ $stats['success'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-xl">
                    ✅
                </div>
            </div>

            <div class="bg-slate-800/60 border border-amber-500/30 rounded-2xl p-5 shadow-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Intentos Fallidos (24h)</p>
                    <p class="text-2xl font-bold text-amber-400 mt-1">Fallidos: {{ $stats['failed'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-xl">
                    ⚠️
                </div>
            </div>

            <div class="bg-slate-800/60 border border-rose-500/30 rounded-2xl p-5 shadow-xl flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Rechazos por Bloqueo (24h)</p>
                    <p class="text-2xl font-bold text-rose-400 mt-1">Bloqueados: {{ $stats['blocked'] }}</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-xl">
                    ⛔
                </div>
            </div>
        </div>

        <!-- Detection Tables: Suspicious IPs & Accounts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Suspicious IPs -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-slate-700/60 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">IPs Sospechosas (Últimos 5 min)</h2>
                        <p class="text-xs text-slate-400">Direcciones IP con 3 o más fallos recientes</p>
                    </div>
                    <span class="text-xs font-mono px-2.5 py-1 rounded bg-slate-900 text-slate-300 border border-slate-700">HAVING COUNT(*) >= 3</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">IP</th>
                                <th class="py-3 px-4">Fallos</th>
                                <th class="py-3 px-4 text-right">Clasificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse($suspiciousIps as $item)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-white">{{ $item->ip_address }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-amber-300">{{ $item->total }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $item->total >= 5 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                            {{ $item->total >= 5 ? 'CRÍTICO' : 'ALERTA' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500 text-xs">No se detectan IPs sospechosas en los últimos 5 minutos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Suspicious Accounts -->
            <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
                <div class="px-5 py-4 border-b border-slate-700/60 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Cuentas bajo Ataque (Últimos 5 min)</h2>
                        <p class="text-xs text-slate-400">Identificadores con múltiples intentos fallidos</p>
                    </div>
                    <span class="text-xs font-mono px-2.5 py-1 rounded bg-slate-900 text-blue-400 border border-slate-700">Protección por Cuenta</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-300">
                        <thead class="bg-slate-900/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Cuenta (Email)</th>
                                <th class="py-3 px-4">Fallos</th>
                                <th class="py-3 px-4 text-right">Clasificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800">
                            @forelse($suspiciousAccounts ?? [] as $account)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-3 px-4 font-mono text-white">{{ $account->identifier }}</td>
                                    <td class="py-3 px-4 font-mono font-bold text-amber-300">{{ $account->total }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="px-2.5 py-1 rounded-md text-xs font-semibold {{ $account->total >= 5 ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                            {{ $account->total >= 5 ? 'CRÍTICO' : 'ALERTA' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="py-6 text-center text-slate-500 text-xs">No se detectan cuentas bajo ataque en los últimos 5 minutos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Audit Trail -->
        <div class="bg-slate-800/60 border border-slate-700/60 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-5 py-4 border-b border-slate-700/60 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-semibold text-white">Historial Reciente de Eventos (Tabla <code class="font-mono text-blue-400">login_attempts</code>)</h2>
                    <p class="text-xs text-slate-400">Últimos 20 eventos registrados en la base de datos de auditoría</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-900/70 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Fecha / Hora</th>
                            <th class="py-3 px-4">Identificador</th>
                            <th class="py-3 px-4">Dirección IP</th>
                            <th class="py-3 px-4">Estado</th>
                            <th class="py-3 px-4">User Agent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @forelse($recentEvents ?? [] as $event)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3 px-4 font-mono text-xs text-slate-400">{{ $event->created_at }}</td>
                                <td class="py-3 px-4 font-medium text-white">{{ $event->identifier ?: '—' }}</td>
                                <td class="py-3 px-4 font-mono text-xs text-blue-400">{{ $event->ip_address }}</td>
                                <td class="py-3 px-4">
                                    @if($event->status === 'success')
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">success</span>
                                    @elseif($event->status === 'blocked')
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-rose-500/20 text-rose-300 border border-rose-500/30">blocked</span>
                                    @else
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-500/20 text-amber-300 border border-amber-500/30">failed</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-xs text-slate-500 truncate max-w-xs">{{ $event->user_agent }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-500">No hay eventos registrados aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</body>
</html>
