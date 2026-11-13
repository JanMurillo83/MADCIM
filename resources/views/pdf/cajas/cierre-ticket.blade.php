<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Corte de caja {{ $caja->id }}</title>
    <style>
        @page { size: letter landscape; margin: 5mm; }
        body { margin: 0; }
    </style>
    @include('pdf.cajas.cierre-estilos')
</head>
<body>
    @include('pdf.cajas.cierre-contenido')
</body>
</html>
