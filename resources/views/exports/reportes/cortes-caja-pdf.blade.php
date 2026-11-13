<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><title>Cortes de Caja</title><style>
        @page { size: letter landscape; margin: 5mm; }
        body { margin: 0; }
        .page-break { page-break-after: always; }
    </style>
    @include('pdf.cajas.cierre-estilos')</head><body>
@foreach($cortes as $corte)
    @php
        $caja = clone $corte->caja;
        $caja->forceFill($corte->getAttributes());
        $caja->setRelation('sucursal', $corte->sucursal);
        $caja->setRelation('usuarioCierre', $corte->usuarioCierre);
        $caja->setRelation('movimientos', $corte->movimientos);
        $pageBreakBefore = ! $loop->first;
    @endphp
    @include('pdf.cajas.cierre-contenido')
@endforeach
</body></html>
