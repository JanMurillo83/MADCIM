# Matriz de dependencias de la extracción RH

## Alcance y criterio

Esta matriz documenta la auditoría inicial del clúster `RecursosHumanos` de
MADCIM realizada el 22 de octubre de 2026. La extracción se considera
reversible: este documento no elimina ni modifica el clúster existente, y las
migraciones de MADCIM continúan siendo la fuente histórica hasta que exista un
plan de transferencia validado.

Las clasificaciones usadas son:

- **Migrar:** pertenece exclusivamente a Recursos Humanos y debe tener una
  implementación equivalente en RHSystem.
- **Adaptar:** pertenece a RH, pero depende de una abstracción compartida que
  debe reemplazarse por una interfaz independiente.
- **Reemplazar:** la implementación actual está acoplada a MADCIM y no debe
  copiarse literalmente.
- **Mantener:** debe permanecer en MADCIM porque soporta otros módulos o no es
  parte del dominio RH.
- **Revisar:** no hay evidencia suficiente para decidir sin revisar contratos,
  datos operativos o consumidores externos.

## Resumen de arquitectura actual

MADCIM usa Laravel 12, PHP 8.4 y Filament 5. El clúster RH está registrado
como `app/Filament/Clusters/RecursosHumanos` y comparte el mismo runtime,
base de datos, autenticación y modelo `User` con los módulos comerciales.

El núcleo RH detectado está compuesto por:

- Personal: `Empleado`, `Contrato`, `MovimientoLaboral`, `BajaEmpleado` y
  `HistorialSalarial`.
- Asistencia: `Horario`, `Checada`, `Incidencia` y sus enums de tipo, estado y
  método.
- Vacaciones: `SaldoVacacion` y `SolicitudVacacion`.
- Nómina: `PeriodoNomina`, `NominaSemanal` y `ConceptoNomina`.
- Expedientes: `DocumentoEmpleado` y las páginas de alertas de vencimiento.
- Biométricos: `DispositivoBiometrico`, `EmpleadoDispositivoId`, `EventoAdms`
  y el flujo ADMS/iClock.
- Aplicación administrativa: 13 recursos/páginas Filament bajo el clúster,
  reportes de asistencia y escritorio de asistencia.

## Matriz por componente

| Componente actual | Evidencia | Clasificación | Tratamiento en RHSystem |
| --- | --- | --- | --- |
| `app/Models/Empleado.php` | Personal, sucursal, horario, usuario y relaciones RH | Migrar | Crear modelo y migración propios; conservar identificadores históricos. |
| `app/Models/Contrato.php` | Contratos de empleados | Migrar | Migrar con FK al empleado independiente. |
| `app/Models/MovimientoLaboral.php` | Altas, cambios y movimientos laborales | Migrar | Mantener historial inmutable o auditado. |
| `app/Models/HistorialSalarial.php` | Historial salarial | Migrar | Copiar reglas y precisión monetaria; validar con nómina. |
| `app/Models/BajaEmpleado.php` | Bajas y servicio de gestión | Migrar | Migrar estados y reglas de cierre. |
| `app/Models/Horario.php` | Horarios y reglas de asistencia | Migrar | Conservar zona horaria, tolerancias y cruces de día. |
| `app/Models/Checada.php` | Registro original protegido contra update/delete | Migrar | Conservar inmutabilidad, deduplicación y trazabilidad. |
| `app/Models/Incidencia.php` | Incidencias y sincronización con asistencia | Migrar | Copiar estados, tipos y reglas de autorización. |
| `app/Models/SaldoVacacion.php` | Saldos por empleado | Migrar | Transferir saldos históricos antes de habilitar escritura. |
| `app/Models/SolicitudVacacion.php` | Solicitudes y flujo de vacaciones | Migrar | Implementar autorización propia y auditoría. |
| `app/Models/PeriodoNomina.php` | Periodos, cierre y revisión | Migrar | Validar periodo semanal y bloqueo de cierre. |
| `app/Models/NominaSemanal.php` | Nómina y desglose de asistencia | Migrar | Reproducir cálculo antes de cambiar el sistema activo. |
| `app/Models/ConceptoNomina.php` | Conceptos de nómina | Migrar | Migrar catálogo y reglas de cálculo. |
| `app/Models/DocumentoEmpleado.php` | Expediente y documentos del empleado | Migrar | Usar almacenamiento y URLs propios de RHSystem. |
| `app/Models/DispositivoBiometrico.php` | Dispositivos por sucursal | Migrar | Mantener secretos solo como hash y rotación propia. |
| `app/Models/EmpleadoDispositivoId.php` | Vinculación PIN/empleado/dispositivo | Migrar | Conservar unicidad y estado de vinculación. |
| `app/Models/EventoAdms.php` | Payload ADMS, huella y reprocesamiento | Migrar | Mantener payload, deduplicación y estados de error. |
| `app/Services/AdmsChecadaService.php` | Recepción ADMS/iClock | Adaptar | Extraer contrato de ingestión; crear endpoints API versionados y rate limiting propio. |
| `app/Services/RegistrarChecadaService.php` | Registro de checadas | Migrar | Copiar como servicio de dominio independiente. |
| `app/Services/EvaluarAsistenciaService.php` | Evaluación diaria | Migrar | Verificar resultados contra MADCIM con fechas históricas. |
| `app/Services/ClasificarMarcacionesService.php` | Clasificación entrada/salida | Migrar | Mantener reglas y pruebas de casos frontera. |
| `app/Services/SincronizarIncidenciasAsistenciaService.php` | Incidencias derivadas | Migrar | Ejecutar dentro de RHSystem, no mediante modelos MADCIM. |
| `app/Services/GenerarNominaSemanalService.php` | Cálculo de nómina | Migrar | Comparación paralela obligatoria antes de activar escritura. |
| `app/Services/ExportarNominaOperativaService.php` | Salida hacia operación | Revisar | Identificar consumidores del módulo comercial antes de reemplazar. |
| `app/Services/Gestionar*Service.php` | Bajas, incidencias, periodos y vacaciones | Migrar | Migrar reglas, validaciones y eventos de dominio. |
| `app/Services/GenerarReporteAsistenciaService.php` | Reportes de asistencia | Migrar | Reubicar consultas y exportaciones en RHSystem. |
| `app/Console/Commands/ActualizarVencimientosExpediente.php` | Tarea programada de expedientes | Migrar | Registrar scheduler y notificaciones propios. |
| `routes/console.php` (`rh:actualizar-vencimientos`) | Scheduler diario | Migrar | Crear comando equivalente independiente. |
| `app/Filament/Clusters/RecursosHumanos` | Recursos, formularios, tablas y páginas RH | Migrar | Copiar UX después de estabilizar dominio y autorización. |
| `app/Http/Controllers/AdmsController.php` | `/iclock/cdata`, `getrequest`, `devicecmd` | Adaptar | Crear controlador API de RHSystem sin depender de `App\Models`. |
| `app/Http/Controllers/NominaPdfController.php` | PDFs de nómina y recibos | Migrar | Reubicar plantillas, permisos y almacenamiento. |
| `app/Models/Sucursal.php` | Sucursal también usada por ventas, cajas e inventario | Adaptar | Crear catálogo RH de sucursales con `external_key`; sincronizar por API o importación explícita. |
| `app/Models/User.php` | Autenticación compartida y `RolePermissions` | Reemplazar | RHSystem necesita usuarios, sesiones y autorización propios. |
| `app/Support/RolePermissions.php` | Permisos codificados del monolito | Reemplazar | Usar permisos propios; no copiar clases internas de MADCIM. |
| `app/Models/Configuracion.php` | Configuración general del sistema comercial | Adaptar | Definir configuración RH separada y variables de entorno propias. |
| `app/Models/DocumentoSerie.php` | Series de documentos comerciales | Mantener | No forma parte del núcleo RH; solo revisar si nómina usa series. |
| `app/Models/Cliente*`, ventas, compras, caja e inventario | Módulos comerciales consumidores de sucursal/usuario | Mantener | Permanecen en MADCIM; no migrar por arrastre. |
| `app/Models/Concerns/BelongsToSucursalScope.php` | Scope compartido por RH y operación | Reemplazar | Implementar scope RH con un catálogo local y reglas explícitas. |
| `storage` y configuración `filesystems` | Documentos y archivos | Adaptar | Bucket/disco y políticas de acceso propios; planificar copia histórica. |
| Notificaciones, auditoría y logs | No se identificó un subsistema RH separado | Revisar | Inventariar consumidores y definir auditoría mínima antes de migrar. |

## Tablas y migraciones RH

Las migraciones específicas identificadas son:

- `create_empleados_table`, `create_horarios_table`,
  `create_checadas_table`, `create_incidencias_table`,
  `create_contratos_table`, `create_documento_empleados_table`.
- `create_saldos_vacaciones_table`, `create_solicitudes_vacaciones_table`,
  `create_bajas_empleados_table`.
- `create_periodos_nomina_table`, `create_nominas_semanales_table`,
  `add_asistencia_a_nominas_semanales_table`,
  `create_conceptos_nomina_table`,
  `add_revision_cierre_periodos_nomina_table` y
  `add_desglose_operativo_nominas_semanales_table`.
- `create_dispositivos_biometricos_table`,
  `create_empleado_dispositivo_ids_table`,
  `create_eventos_adms_table` y
  `make_dispositivo_token_hash_nullable`.

Dependencias de datos que requieren diseño de migración:

- `empleados.sucursal_id` y `dispositivos_biometricos.sucursal_id` dependen de
  `sucursales`.
- `empleados.user_id` y `checadas.autorizada_por_id` dependen de `users`.
- Los documentos dependen del disco de archivos y posiblemente de usuarios
  que los cargaron.
- Nómina depende de empleados, horarios, checadas, incidencias, contratos,
  historial salarial y catálogos de conceptos.
- Los eventos ADMS dependen del dispositivo y del vínculo PIN/empleado.

No se deben ejecutar estas migraciones contra la base operativa de MADCIM.
RHSystem tendrá migraciones nuevas y una importación idempotente, con conteos,
hashes y bitácora de errores.

## Rutas, tareas e integraciones

Se detectaron las rutas de compatibilidad de dispositivos:

- `GET|POST /iclock/cdata`
- `GET /iclock/getrequest`
- `POST /iclock/devicecmd`

Actualmente usan `throttle:adms` y `AdmsController`. La extracción debe
mantener compatibilidad de protocolo y hostname/puerto alcanzable por los
dispositivos, pero el endpoint deberá apuntar a RHSystem durante una ventana
controlada. Se debe probar con payloads reales anonimizados y conservar una
cola o bandeja de eventos pendientes.

La tarea `rh:actualizar-vencimientos` se programa diariamente a las 00:10.
Debe existir una tarea equivalente en RHSystem y una estrategia explícita de
notificaciones, sin depender de scheduler, clases o logs de MADCIM.

No se identificaron rutas API REST versionadas propias de RH en la auditoría
inicial. Esto queda como trabajo de arquitectura para `/api/v1`, incluyendo
autenticación de integración para GLRBioCheck, idempotencia y límites de
frecuencia.

## Riesgos y asuntos abiertos

1. **Usuarios y permisos:** la autorización actual está acoplada a roles
   almacenados en `users`; se requiere catálogo de roles/permisos de RHSystem.
2. **Sucursales:** una sucursal puede ser compartida por operación comercial.
   Debe definirse una clave externa estable y la política de altas/bajas.
3. **Documentos:** no se conoce aún el volumen, disco utilizado ni retención.
   Copiar solo registros sin archivos dejaría expedientes incompletos.
4. **Nómina:** debe ejecutarse una comparación paralela por varios periodos
   cerrados antes de cambiar el sistema de registro.
5. **ADMS:** cambiar el destino sin preservar deduplicación puede crear
   checadas duplicadas; el hash de evento y la serie del dispositivo son
   parte del contrato.
6. **Integraciones externas:** falta confirmar consumidores de exportaciones,
   PDFs, correo y cualquier webhook no declarado en las rutas.
7. **Datos históricos:** la importación debe ser append-only para checadas y
   conservar IDs de origen, timestamps y payloads originales.

## Secuencia segura propuesta

1. Crear RHSystem con base de datos, autenticación, Filament, API y logs
   independientes.
2. Implementar migraciones y modelos RH sin eliminar ni renombrar tablas en
   MADCIM.
3. Crear importadores de catálogos y datos históricos en modo lectura,
   idempotente y auditable.
4. Comparar conteos, relaciones, hashes de eventos ADMS y resultados de
   asistencia/nómina.
5. Ejecutar una ventana de doble lectura, sin doble escritura de checadas.
6. Cambiar gradualmente el endpoint de dispositivos y monitorear pendientes,
   rechazados y duplicados.
7. Mantener MADCIM en modo respaldo hasta completar la reversión documentada.

