# Cambios en INVOIX antes de subirlo

Estos cambios van en `D:\OYARCEGROUP\invoix.co`. El panel no los aplica solo. Sin ellos, crear una empresa en Ubuntu deja el sitio vacío o con una contraseña distinta a la que muestra el panel.

El Docker de `docker-compose.yml` que usas en Windows se queda. Es el entorno local: monta el código y trae su propio MySQL y Redis. Lo que se agrega es la imagen de producción, que es otra cosa.

## 1. Copiar las plantillas

Origen: `templates/invoix/` de este panel. Destino: la raíz de INVOIX.

| Archivo en el panel | Dónde queda en INVOIX |
| --- | --- |
| `templates/invoix/Dockerfile` | `Dockerfile` en la raíz. Hoy no existe. |
| `templates/invoix/docker/php/entrypoint.production.sh` | `docker/php/entrypoint.production.sh` |
| `templates/invoix/docker/nginx/default.conf` | `docker/nginx/default.conf` |
| `templates/invoix/.github/workflows/image.yml` | `.github/workflows/image.yml` |

`docker/php/php.ini` de INVOIX ya sirve. No lo reemplaces por el de la plantilla: el de INVOIX tiene OPcache y el Dockerfile de producción lo copia tal cual.

`docker/php/entrypoint.sh` y `docker/php/Dockerfile` se dejan. Los usa el compose local.

### Nginx y el entorno local

La plantilla de Nginx no apunta a `app:9000`. Usa `__FPM_HOST__`, y el entrypoint de producción lo cambia por el contenedor de esa empresa (`gogo-app`, `facturamia-app`, etc.). Si varias empresas usaran el nombre fijo `app`, se pisarían en la red.

El compose local monta `docker/nginx/default.conf`. Antes de reemplazarlo:

1. Copia el archivo actual a `docker/nginx/default.dev.conf`.
2. Pon la plantilla en `docker/nginx/default.conf`.
3. En el servicio `nginx` de `docker-compose.yml`, cambia el volumen para que monte `default.dev.conf` en `/etc/nginx/conf.d/default.conf`.

Así Windows sigue hablando con `app:9000` y la imagen de producción usa el host de cada empresa.

## 2. Contraseña del administrador

En `database/seeders/DatabaseSeeder.php`, el alta hoy hace esto:

```php
$password = Str::password(14, symbols: false);
```

Esa línea ignora `ERP_ADMIN_PASSWORD`. El panel escribe esa variable y muestra esa contraseña una sola vez. Tiene que quedar así:

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

Si el correo ya existe, no se toca y no se borra nada. El resto del seeder (geografía, roles, PUC, impuestos) se deja. Esos seeders se vuelven a ejecutar en cada arranque de la empresa, así que tienen que seguir pudiendo correr dos veces sin duplicar datos.

## 3. HTTPS detrás de Caddy

INVOIX no confía en el proxy. Caddy termina el certificado y le habla a Nginx por HTTP. Sin esto, Laravel cree que el sitio es `http` y las redirecciones salen en HTTP.

En `bootstrap/app.php`, dentro de `withMiddleware`, agrega:

```php
$middleware->trustProxies(at: '*');
```

## 4. Publicar la imagen

`package-lock.json` y `composer.lock` ya están. El `Dockerfile` nuevo los necesita.

Haz commit y push a `main` del repositorio `sepineda1/invoix.co`. GitHub construye y publica:

- `ghcr.io/sepineda1/invoix:latest`
- `ghcr.io/sepineda1/invoix:` más los siete caracteres del commit

En ese repositorio, en Settings, Secrets and variables, Actions, crea:

| Secreto | Valor |
| --- | --- |
| `PANEL_WEBHOOK_URL` | `https://panel.invoix.co/webhooks/github/image` |
| `PANEL_WEBHOOK_SECRET` | El mismo valor de `PANEL_WEBHOOK_SECRET` en el `.env` del servidor del panel |

El workflow avisa al panel cuando la imagen queda publicada. El botón **Actualizar todas** usa esa misma cola.

Si el paquete en GHCR queda privado, en el servidor Ubuntu hay que entrar una vez con `docker login ghcr.io`. Si no, el panel no puede bajar la imagen.

## Qué no hay que hacer

- No convertir INVOIX en multi-tenant.
- No clonar el repositorio una vez por empresa.
- No montar el código dentro de los contenedores de los clientes.
- No subir el `.env` de INVOIX. La imagen no lleva contraseñas, PIN de la DIAN ni `APP_KEY`. Eso lo escribe el panel por empresa.
- No hace falta un registro DNS por empresa. Con `*.invoix.co` alcanza.

Cuando el push a `main` termine y la imagen exista en GHCR, INVOIX ya se puede crear desde el panel en Ubuntu.
