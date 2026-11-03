<h1>Panel de seguridad</h1>
<p>Exitosos: {{ $stats['success'] }}</p>
<p>Fallidos: {{ $stats['failed'] }}</p>
<p>Bloqueados: {{ $stats['blocked'] }}</p>
<table border="1">
<tr><th>IP</th><th>Fallos</th><th>Clasificación</th></tr>
@foreach($suspiciousIps as $item)
<tr><td>{{ $item->ip_address }}</td>
<td>{{ $item->total }}</td>
<td>{{ $item->total >= 5 ? 'CRÍTICO' : 'ALERTA' }}</td></tr>
@endforeach
</table>
