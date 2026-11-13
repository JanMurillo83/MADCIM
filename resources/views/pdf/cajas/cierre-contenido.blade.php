@php
    $reporte = $caja->reporte_cierre ?? [];
    $tieneSnapshot = is_array($reporte) && array_key_exists('ventas_totales', $reporte);
    $categorias = ['Madera', 'Carpintería', 'Rentas de madera', 'Renta de Equipos', 'Ferretería', 'Matto. A equipos'];
    $ventas = $reporte['ventas_totales'] ?? array_fill_keys($categorias, 0);
    $devoluciones = $reporte['devoluciones'] ?? array_fill_keys($categorias, 0);
    $credito = $reporte['ventas_credito'] ?? array_fill_keys($categorias, 0);
    $recuperados = $reporte['creditos_recuperados'] ?? array_fill_keys($categorias, 0);
    $bancos = $reporte['bancos'] ?? [];
    $denominaciones = $caja->denominaciones_efectivo ?? [];
    $logo = public_path('images/madcim-logo-horizontal.png');
    $totalTarjetas = collect($caja->movimientos)->filter(fn ($m) => $m->tipo === 'Ingreso' && in_array(mb_strtolower((string)$m->metodo_pago), ['tarjeta crédito', 'tarjeta débito'], true))->sum('importe');
    $efectivoEntrega = (float)$caja->total_ingresos_cash - (float)$caja->total_egresos_cash;
@endphp
<div class="corte-sheet {{ !empty($pageBreakBefore) ? 'page-break-before' : '' }}">
<table class="head"><tr>
    <td style="width:39%"><div class="title">│ CORTE DE CAJA</div><div class="fine">{{ $caja->sucursal?->nombre ?? 'Sucursal' }} · {{ $caja->nombre ?: 'Caja '.$caja->id }}</div></td>
    <td style="width:28%" class="brand"><img src="data:image/png;base64,{{ base64_encode(file_get_contents($logo)) }}" style="width:155px;height:auto"></td>
    <td style="width:33%" class="date">FECHA: <b>{{ optional($caja->fecha_cierre)->format('d-M-y') }}</b><br><span class="fine">Responsable: {{ $caja->usuarioCierre?->name ?? '' }}</span></td>
</tr></table>
@unless($tieneSnapshot)<div class="box"><div class="bar gold">CORTE HISTÓRICO: el desglose por categoría y banco no se guardaba en este cierre.</div></div>@endunless
<table class="cols">
<tr><td>
    <div class="box"><div class="bar">VENTAS TOTALES <span class="amount">$ {{ number_format(array_sum($ventas), 2) }}</span></div><table class="data"><tr><th>Categoría</th><th class="right">Importe</th></tr>@foreach($categorias as $categoria)<tr><td>{{ $categoria }}</td><td class="right">$ {{ number_format((float)($ventas[$categoria] ?? 0),2) }}</td></tr>@endforeach</table></div>
</td><td>
    <div class="box"><div class="bar">DEPÓSITOS EN BANCOS <span class="amount">$ {{ number_format((float)($reporte['bancos_total'] ?? 0),2) }}</span></div><table class="data"><tr><th>BANCO DE DEPÓSITO</th><th>TIPO DE COBRO</th><th class="right">IMPORTE</th></tr>@forelse($bancos as $deposito)<tr><td>{{ $deposito['banco'] }}</td><td>{{ $deposito['metodo_pago'] }}</td><td class="right">$ {{ number_format((float)$deposito['importe'],2) }}</td></tr>@empty<tr><td colspan="3" class="center">{{ $tieneSnapshot ? 'Sin depósitos no efectivo' : 'Sin información histórica de bancos' }}</td></tr>@endforelse<tr class="total"><td colspan="2">TOTAL</td><td class="right">$ {{ number_format((float)($reporte['bancos_total'] ?? 0),2) }}</td></tr></table></div>
</td></tr>
<tr><td>
    <div class="box"><div class="bar red">DEVOLUCIONES S/VENTAS <span class="amount">$ {{ number_format(array_sum($devoluciones), 2) }}</span></div><table class="data"><tr><th>Categoría</th><th class="right">Importe</th></tr>@foreach($categorias as $categoria)<tr><td>{{ $categoria }}</td><td class="right">$ {{ number_format((float)($devoluciones[$categoria] ?? 0),2) }}</td></tr>@endforeach</table></div>
</td><td>
    <div class="box"><div class="bar red">SALIDAS DE EFECTIVO <span class="amount">$ {{ number_format((float)($reporte['salidas_efectivo'] ?? $caja->total_egresos_cash),2) }}</span></div>@forelse($caja->movimientos->where('tipo','Egreso')->where('metodo_pago','Efectivo') as $egreso)<div class="line">{{ $egreso->observaciones ?: $egreso->fuente ?: 'Salida de efectivo' }} <b>$ {{ number_format((float)$egreso->importe,2) }}</b></div>@empty<div class="line">Sin salidas de efectivo</div>@endforelse</div>
</td></tr>
<tr><td>
    <div class="box"><div class="bar gold">VENTAS A CRÉDITO <span class="amount">$ {{ number_format(array_sum($credito), 2) }}</span></div><table class="data"><tr><th>Categoría</th><th class="right">Importe</th></tr>@foreach($categorias as $categoria)<tr><td>{{ $categoria }}</td><td class="right">$ {{ number_format((float)($credito[$categoria] ?? 0),2) }}</td></tr>@endforeach</table></div>
</td><td>
    <div class="box"><div class="bar">TOTAL DE EFECTIVO X ENTREGAR <span class="amount">$ {{ number_format($efectivoEntrega,2) }}</span></div></div>
    <div class="box"><div class="bar">EFECTIVO TABULADO</div><table class="data"><tr><th>BILLETE / MONEDA</th><th class="center">CANTIDAD</th><th class="right">IMPORTE</th></tr>
        @foreach(['billete_1000'=>1000,'billete_500'=>500,'billete_200'=>200,'billete_100'=>100,'billete_50'=>50,'billete_20'=>20] as $clave=>$valor)
            @if((int)($denominaciones[$clave] ?? 0)>0)<tr><td class="center">$ {{ number_format($valor,2) }}</td><td class="center">{{ (int)$denominaciones[$clave] }}</td><td class="right">$ {{ number_format($valor*(int)$denominaciones[$clave],2) }}</td></tr>@endif
        @endforeach
        @php
            $totalMonedas = 0;
            foreach (['moneda_20'=>20,'moneda_10'=>10,'moneda_5'=>5,'moneda_2'=>2,'moneda_1'=>1,'moneda_0_5'=>0.5] as $clave => $valor) {
                $totalMonedas += $valor * (int) ($denominaciones[$clave] ?? 0);
            }
        @endphp
        @if($totalMonedas > 0)<tr><td colspan="2" class="center">MONEDAS</td><td class="right">$ {{ number_format($totalMonedas,2) }}</td></tr>@endif
        <tr class="total"><td colspan="2" class="right">TOTAL</td><td class="right">$ {{ number_format((float)$caja->efectivo_contado,2) }}</td></tr>
    </table></div>
</td></tr>
<tr><td><div class="box"><div class="bar">VENTAS DE CONTADO <span class="amount">$ {{ number_format((float)($reporte['ventas_contado'] ?? 0), 2) }}</span></div></div></td>
<td><div class="box"><div class="bar">TOTAL COBRADO CON TARJETAS <span class="amount">$ {{ number_format((float)$totalTarjetas,2) }}</span></div></div></td></tr>
<tr><td>
    <div class="box"><div class="bar">CRÉDITOS RECUPERADOS <span class="amount">$ {{ number_format(array_sum($recuperados), 2) }}</span></div><table class="data"><tr><th>Categoría</th><th class="right">Importe</th></tr>@foreach($categorias as $categoria)<tr><td>{{ $categoria }}</td><td class="right">$ {{ number_format((float)($recuperados[$categoria] ?? 0),2) }}</td></tr>@endforeach</table></div>
</td><td>
    <div class="box"><div class="line">FONDO FIJO PARA MANEJO DE CAMBIO <b>$ {{ number_format((float)($reporte['fondo_fijo'] ?? $caja->saldo_inicial_cash),2) }}</b></div><div class="line"><b>EFECTIVO TABULADO: $ {{ number_format((float)$caja->efectivo_contado,2) }}</b></div><div class="line">FALTANTE O SOBRANTE <b class="{{ (float)$caja->total_diferencia < 0 ? 'bad' : 'good' }}">$ {{ number_format((float)$caja->total_diferencia,2) }}</b></div><div class="bar {{ (float)$caja->total_diferencia < 0 ? 'red' : '' }}">RESULTADO <span class="amount">{{ (float)$caja->total_diferencia < 0 ? 'FALTANTE' : ((float)$caja->total_diferencia > 0 ? 'SOBRANTE' : 'CUADRADO') }}</span></div></div>
</td></tr>
<tr><td><div class="box"><div class="bar">TOTAL VENTAS CONTADO Y CRÉDITO RECUPERADO <span class="amount">$ {{ number_format((float)($reporte['ventas_contado'] ?? 0) + array_sum($recuperados), 2) }}</span></div></div></td>
<td><div class="box"><div class="bar">OBSERVACIONES</div><div class="obs">{{ $caja->observaciones_cierre }}</div></div></td></tr>
</table>
</div>
