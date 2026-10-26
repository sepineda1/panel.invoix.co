# Panel de despliegue de INVOIX

Este panel crea una instalación de INVOIX por empresa. El código de facturación no se clona ni se vuelve multi-tenant: cada empresa corre la misma imagen de Docker, con su `.env`, su base de datos y su prefijo de Redis. Si la empresa es Gogo, el sitio queda en `https://gogo.invoix.co`.

El panel es el único proceso que habla con el socket de Docker.

## Arranque local en Windows

Hace falta Docker Desktop. No hace falta PHP ni Composer en el equipo.

```powershell
docker compose -f docker-compose.dev.yml up -d --build
```

Cuando el panel responda:

- Dirección: http://localhost:8095
- Correo: `support@invoix.co`
- Contraseña: `invoix2027!`

Esa contraseña es la del operador local. Cámbiala antes de publicar el servidor.

Las pruebas:

```powershell
docker compose -f docker-compose.dev.yml exec panel php artisan test
```

MySQL y Redis del panel no se publican al equipo. El puerto 8095 es el acceso directo al panel. Caddy escucha 80 y 443 para el mismo flujo que en producción, con TLS desactivado en local.

## Despliegue en Ubuntu

### DNS

Un solo registro comodín. No se crea un registro por empresa.

```text
*.invoix.co      A     IP_DEL_SERVIDOR
panel.invoix.co  A     IP_DEL_SERVIDOR
```

`panel.invoix.co` a veces no queda cubierto por el comodín, según el DNS. Conviene crearlo explícito.

### Puertos

El servidor debe aceptar tráfico en **80** y **443**. Caddy es el único proceso que los publica. El panel, MySQL y Redis no tienen puertos abiertos hacia internet.

### Variables

En el servidor, copia `.env.example` a `.env` y completa:

| Variable | Para qué |
| --- | --- |
| `APP_KEY` | Cifrado del panel. Generala con `docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)), PHP_EOL;"` |
| `APP_URL` | `https://panel.invoix.co` |
| `DB_PASSWORD` | Contraseña del usuario MySQL `panel` |
| `MYSQL_ROOT_PASSWORD` | El panel la usa para crear la base de cada empresa |
| `PANEL_ADMIN_EMAIL` | Primer operador |
| `PANEL_ADMIN_PASSWORD` | Contraseña de ese operador. El seeder no la cambia si el operador ya existe |
| `PANEL_WEBHOOK_SECRET` | Secreto compartido con GitHub Actions |
| `PANEL_DOMAIN` | Por defecto `invoix.co` |
| `INVOIX_IMAGE` | Imagen inicial, por ejemplo `ghcr.io/sepineda1/invoix:latest` |
| `ACME_EMAIL` | Correo de Let's Encrypt |

### Primer arranque

```bash
sudo mkdir -p /var/lib/invoix/tenants /var/lib/invoix/caddy
echo "# Generado por el panel." | sudo tee /var/lib/invoix/caddy/tenants.caddy
docker compose up -d --build
```

El entrypoint del panel ejecuta las migraciones y el seeder antes de servir tráfico. Entra en `https://panel.invoix.co` con el operador del `.env`.

Si la imagen de INVOIX en GHCR es privada, en el servidor:

```bash
docker login ghcr.io
```

El panel guarda los archivos de cada empresa en `/var/lib/invoix/tenants/{slug}/`. El `.env` queda con permisos `600`. Las contraseñas de base y el `APP_KEY`, cuando están en la base del panel, van cifrados.

### Actualizar todas

Hay dos caminos y los dos encolan el mismo trabajo:

- El botón **Actualizar todas**, que usa la última imagen conocida.
- El webhook `POST /webhooks/github/image`, con cuerpo `{"image":"ghcr.io/ORG/invoix:SHA"}` y cabecera `X-Hub-Signature-256`.

El trabajo recorre las empresas en línea o fallidas de una en una. Si una falla, queda marcada y el resto sigue.

## Copiar las plantillas a INVOIX

Estas plantillas no se aplican solas. Cuando quieras publicar la imagen, copia el contenido de `templates/invoix/` a la raíz del repositorio de INVOIX (`D:\OYARCEGROUP\invoix.co` no se modifica desde aquí):

- `Dockerfile`
- `docker/php/entrypoint.production.sh`
- `docker/php/php.ini`
- `docker/nginx/default.conf`
- `.github/workflows/image.yml`

En GitHub, en el repositorio de INVOIX, crea los secretos `PANEL_WEBHOOK_URL` (`https://panel.invoix.co/webhooks/github/image`) y `PANEL_WEBHOOK_SECRET` (el mismo valor que en el panel).

El contrato que esa imagen tiene que cumplir está en [docs/contrato-imagen-invoix.md](docs/contrato-imagen-invoix.md). Incluye el cambio del seeder para que use `ERP_ADMIN_PASSWORD`.

## Qué hace el panel al crear una empresa

1. Crea la base `invoix_{slug}` y el usuario del mismo nombre, solo con permiso sobre esa base.
2. Escribe `/var/lib/invoix/tenants/{slug}/.env`.
3. Crea el almacenamiento de Laravel.
4. Escribe el `docker-compose.yml` de esa empresa.
5. Levanta `{slug}-app`, `{slug}-web`, `{slug}-horizon` y `{slug}-scheduler` con la imagen publicada.
6. Espera a que `/up` responda 200, hasta dos minutos.
7. La deja en línea y recarga Caddy.

Si falla a la mitad, la base no se borra. **Reintentar** continúa desde el último paso que sí terminó.
