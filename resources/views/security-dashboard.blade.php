<h1>Panel de seguridad</h1>
<p>Exitosos: {{ $stats['success'] }}</p>
<p>Fallidos: {{ $stats['failed'] }}</p>
<p>Bloqueados: {{ $stats['blocked'] }}</p>
<p><a href="{{ route('security-dashboard.export') }}">Exportar evidencias (CSV)</a></p>

<h2>IPs sospechosas (últimos 5 min)</h2>
<table border="1">
<tr><th>IP</th><th>Fallos</th><th>Clasificación</th></tr>
@foreach($suspiciousIps as $item)
<tr><td>{{ $item->ip_address }}</td>
<td>{{ $item->total }}</td>
<td>{{ $item->total >= 5 ? 'CRÍTICO' : 'ALERTA' }}</td></tr>
@endforeach
</table>

<h2>Cuentas bajo ataque (últimos 5 min)</h2>
<table border="1">
<tr><th>Cuenta (Email)</th><th>Fallos</th><th>Clasificación</th></tr>
@foreach($suspiciousAccounts as $account)
<tr><td>{{ $account->identifier }}</td>
<td>{{ $account->total }}</td>
<td>{{ $account->total >= 5 ? 'CRÍTICO' : 'ALERTA' }}</td></tr>
@endforeach
</table>

<h2>Historial reciente de eventos</h2>
<table border="1">
<tr><th>Fecha</th><th>Cuenta</th><th>IP</th><th>Estado</th><th>User Agent</th></tr>
@foreach($recentEvents as $event)
<tr><td>{{ $event->created_at }}</td>
<td>{{ $event->identifier }}</td>
<td>{{ $event->ip_address }}</td>
<td>{{ $event->status }}</td>
<td>{{ $event->user_agent }}</td></tr>
@endforeach
</table>
