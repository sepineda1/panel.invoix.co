# Contrato de la imagen de INVOIX

El panel no modifica el repositorio de INVOIX. Publica instalaciones a partir de una imagen ya construida. Esta imagen tiene que cumplir lo de abajo. Las plantillas listas para copiar están en `templates/invoix/`.

## Imagen

- Una sola imagen para las cuatro piezas: PHP-FPM, Nginx, Horizon y el scheduler.
- El código va dentro de la imagen. No se monta el código del host.
- La imagen no contiene `.env`, certificados, PIN de la DIAN ni contraseñas de clientes.
- GHCR publica dos tags en cada push a `main`: `latest` y el SHA corto de siete caracteres. El nombre es `ghcr.io/ORG/invoix`.
- El workflow avisa al panel con `POST` y la firma `X-Hub-Signature-256` del cuerpo `{"image":"ghcr.io/ORG/invoix:SHA"}`.

## Procesos

El compose de cada empresa arranca la misma imagen con un comando distinto:

| Contenedor | Comando |
| --- | --- |
| `{slug}-app` | `php-fpm` |
| `{slug}-web` | `nginx -g 'daemon off;'` |
| `{slug}-horizon` | `php artisan horizon` |
| `{slug}-scheduler` | `php artisan schedule:work` |

Nginx escucha el puerto 80 solo dentro de la red Docker. Habla con PHP-FPM en el host que el panel inyecta como `FPM_HOST` (`{slug}-app`). Caddy, fuera de la imagen, publica el 443.

El volumen de almacenamiento se monta en `/var/www/html/storage` en las cuatro piezas. Ahí viven los archivos subidos. El enlace `public/storage` lo crea el entrypoint.

## Arranque

`docker/php/entrypoint.production.sh` hace esto y después ejecuta el comando que recibió:

1. Espera a MySQL y a Redis.
2. Crea el enlace de storage si no existe.
3. Con un lock en `storage/framework/.migrate.lock`, ejecuta `php artisan migrate --force` una vez por arranque, aunque las cuatro piezas arranquen juntas.
4. Si existen `ERP_ADMIN_EMAIL` y `ERP_ADMIN_PASSWORD`, ejecuta `php artisan db:seed --force`. No borra datos.

`/up` es la ruta de salud de Laravel y tiene que responder 200 cuando la aplicación ya puede atender.

Variables mínimas que el panel escribe y la imagen debe respetar:

- `APP_NAME`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_KEY`
- zona `America/Bogota`
- MySQL compartido (`DB_HOST=mysql`)
- Redis compartido con `REDIS_PREFIX={slug}-`, `REDIS_DB=0` y `REDIS_CACHE_DB=0`. No se separan clientes con `SELECT`.
- `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=database`
- `FILESYSTEM_DISK=local`, `LOG_CHANNEL=stderr`
- `ERP_COMPANY_NAME`, `ERP_ADMIN_NAME`, `ERP_ADMIN_EMAIL`, `ERP_ADMIN_PASSWORD`

No incluye credenciales de la DIAN. Eso lo configura cada cliente dentro de su INVOIX.

## Seeder del primer usuario

El seeder actual de INVOIX genera una contraseña propia y no lee `ERP_ADMIN_PASSWORD`. Hay que cambiarlo al copiar estas plantillas, si no la contraseña que muestra el panel no coincide con la del usuario.

En `database/seeders/DatabaseSeeder.php`, el alta del administrador queda así:

```php
$email = env('ERP_ADMIN_EMAIL', 'admin@example.com');
if (! User::where('email', $email)->exists()) {
    $password = env('ERP_ADMIN_PASSWORD');
    if (! is_string($password) || $password === '') {
        $password = Str::password(14, symbols: false);
        $this->command?->warn("Usuario administrador: $email / $password");
    }

    $user = User::create([
        'company_id' => $company->id,
        'name' => env('ERP_ADMIN_NAME', 'Administrador'),
        'email' => $email,
        'password' => $password,
        'active' => true,
    ]);
    $user->assignRole('Administrador');
}
```

El seeder sigue sin borrar datos: si el correo ya existe, no lo toca. El resto de seeders (PUC, impuestos, roles) se dejan como están, siempre que también sean idempotentes.
