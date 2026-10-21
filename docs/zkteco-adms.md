# Integración ZKTeco MB160ID mediante ADMS/iClock

## Arquitectura

El dispositivo se identifica por su número de serie (`SN`) registrado en
`dispositivos_biometricos`, y debe estar activo. No se requieren tokens, encabezados de
autenticación ni credenciales personalizadas. El número de serie identifica el catálogo, pero
no constituye una prueba de autenticidad.

Los registros recibidos se almacenan primero en `eventos_adms`. Esto permite conservar el
payload original, detectar reenvíos, dejar eventos pendientes de vinculación y diagnosticar
errores sin duplicar el módulo de checadas.

Cuando el identificador biométrico está vinculado a un empleado, el evento se procesa mediante
`RegistrarChecadaService`. Las validaciones y la inmutabilidad de las checadas manuales se
mantienen sin cambios.

## Endpoints

| Método | Endpoint | Uso |
| --- | --- | --- |
| GET | `/iclock/cdata?SN=SERIE` | Inicialización y opciones |
| POST | `/iclock/cdata?SN=SERIE&table=ATTLOG` | Recepción de checadas |
| GET | `/iclock/getrequest?SN=SERIE` | Consulta de comandos |
| POST | `/iclock/devicecmd?SN=SERIE` | Resultado de comandos |

Los endpoints no utilizan la sesión web ni requieren autenticación de Filament.

## Formato de checadas aceptado

Se aceptan líneas separadas por salto de línea y campos separados por tabulador. El formato
mínimo esperado es:

```text
100001	2026-10-21 08:02:00	0
```

También se aceptan pares con nombre, por ejemplo:

```text
PIN=100001	TIME=2026-10-21 08:02:00	STATUS=0
```

`STATUS=0` se interpreta como entrada y `STATUS=1` como salida. Cuando el firmware no envía
estado, se registra como entrada para que el procesamiento posterior no se pierda.

## Autorización y vinculación

Cada dispositivo requiere:

- Número de serie.
- Estado activo.
- Sucursal opcional.

Cada empleado debe tener un registro en `empleado_dispositivo_ids` con el identificador que el
equipo envía en `PIN`. No se crean empleados automáticamente.

Si el identificador no está vinculado, el evento permanece con estado `pendiente` y conserva
su payload original.

## Duplicados y errores

La huella del evento se calcula con el número de serie y la línea original recibida. Los
reenvíos del mismo registro quedan como duplicados y no generan una segunda checada.

La respuesta de recepción usa texto plano compatible con el intercambio ADMS:

- `200 OK` con cuerpo `OK` cuando todas las líneas fueron persistidas como procesadas,
  pendientes o duplicadas ya conocidas.
- `422` con cuerpo `ERROR: REJECTED` si al menos una línea no pudo interpretarse o procesarse.

El receptor no devuelve un detalle por línea ni un formato de conteos personalizado, porque el
firmware puede requerir la confirmación estándar `OK`. En un lote mixto, el dispositivo debe
reenviar el lote después de recibir el error; las líneas ya persistidas se reconocerán como
duplicadas y la respuesta podrá convertirse en `OK`, mientras la línea rechazada seguirá
disponible en `eventos_adms` para diagnóstico. Esta estrategia evita duplicar checadas, pero no
permite confirmar individualmente cada línea desde la respuesta HTTP.

Estados posibles:

- `procesado`: se creó la checada existente.
- `pendiente`: empleado no vinculado.
- `rechazado`: formato inválido o error de procesamiento.

La última comunicación y la última checada procesada se registran en el dispositivo.

## Configuración inicial

Registrar el dispositivo sin credenciales ADMS:

```bash
php artisan tinker
```

```php
$sucursal = App\Models\Sucursal::where('codigo', 'HUA')->firstOrFail();
App\Models\DispositivoBiometrico::create([
    'nombre' => 'Reloj Huatulco',
    'numero_serie' => 'SERIE_DEL_EQUIPO',
    'modelo' => 'ZKTeco MB160ID',
    'sucursal_id' => $sucursal->id,
    'activo' => true,
]);
```

En el equipo configure la URL del servidor y el número de serie que ya exista en el catálogo.

## Configuración de empleados

```php
App\Models\EmpleadoDispositivoId::create([
    'empleado_id' => $empleado->id,
    'dispositivo_biometrico_id' => $dispositivo->id,
    'identificador' => '100001',
]);
```

## VPS y seguridad

- Publicar los endpoints únicamente por HTTPS cuando el firmware lo permita.
- Si el firmware solo admite HTTP, restringir el acceso por IP/VPN o reverse proxy.
- En producción se recomienda configurar una allowlist de IP en el reverse proxy o VPS cuando
  se conozca la IP pública de salida del cliente. Esta restricción no está habilitada todavía.
- Sin autenticación ni restricción de red, un tercero que conozca o suplante el número de serie
  podría enviar registros falsos.
- No exponer endpoints de configuración del dispositivo sin autenticación.
- Revisar periódicamente eventos pendientes y rechazados.
- No se almacenan rostros, huellas ni plantillas biométricas.

## Limitaciones pendientes de validar con el equipo físico

- Formato exacto de `ATTLOG`, zona horaria y estado de entrada/salida.
- Compatibilidad del firmware con HTTPS.
- Confirmación del reenvío automático después de una interrupción de internet.
- Necesidad de comandos adicionales en `getrequest` o `devicecmd`.
