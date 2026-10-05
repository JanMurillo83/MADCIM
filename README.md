<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## Reinicio completo del sistema

Antes de ejecutar el reinicio, respalda la base de datos y los documentos fiscales que deban conservarse, confirma la conexión de base de datos activa y suspende las capturas.

```bash
php artisan sistema:reiniciar-completo
```

El comando toma por defecto `database/invdata/CATALOGO PRODUCTOS RENTAS.xlsx`, que debe estar incluido en el despliegue. Se puede indicar otra ruta como argumento opcional. El comando confirma con “sí” por defecto: al presionar Enter o ejecutarse sin interacción (por ejemplo, desde Plesk), continúa; en una terminal interactiva se puede responder “no” para cancelar. El reinicio elimina los datos operativos, saldos de clientes y proveedores, historial de precios de madera y el catálogo actual de productos. El archivo debe contener exactamente 205 productos válidos; todos se cargan con existencia cero. El producto reservado `DEPOGARANTIA` se conserva desde `public/csvdata/productos.csv`, por lo que el catálogo final esperado tiene 206 productos.

El reinicio pone a cero los folios de todas las series y deja las cajas cerradas, sin saldo inicial ni movimientos/acumulados de la operación anterior. Conserva clientes, proveedores, configuración y demás catálogos maestros; agrega grupos o líneas que falten para los productos nuevos. Los precios por M² se conservan desde Configuración y no se reemplazan con los precios del Excel: verifica sus seis valores y el precio de `DEPOGARANTIA` antes de iniciar la captura.

`--force` omite la confirmación interactiva; úsalo únicamente después de verificar la base de datos destino y los respaldos. El comando anterior `sistema:reiniciar-datos` sigue disponible para limpiar operaciones sin reemplazar el catálogo de productos.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
