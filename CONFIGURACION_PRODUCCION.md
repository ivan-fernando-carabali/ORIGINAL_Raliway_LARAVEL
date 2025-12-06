# Configuración para Producción - Smart Inventory

## Variables de entorno necesarias en Railway

Agrega estas variables en el panel de Railway o en tu archivo `.env`:

```env
# ===== SANCTUM (IMPORTANTE PARA CORS Y AUTENTICACIÓN) =====
SANCTUM_STATEFUL_DOMAINS="localhost,localhost:4200,127.0.0.1,127.0.0.1:4200,127.0.0.1:8000,laravelsmartinventory-production.up.railway.app,*.railway.app"

# ===== CORS =====
# El archivo config/cors.php ya está configurado para permitir todas las URLs necesarias

# ===== SESSION =====
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=.railway.app
SESSION_SECURE_COOKIE=true

# ===== FRONTEND URL =====
FRONTEND_URL="https://tu-frontend-url.com"
```

## Comandos para desplegar en Railway

1. **Limpiar caché de configuración:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

2. **Publicar assets de storage:**
```bash
php artisan storage:link
```

3. **Verificar que la API funciona:**
```bash
curl https://laravelsmartinventory-production.up.railway.app/api/ping
```

## Verificar CORS

Si sigues teniendo problemas de CORS, verifica que el archivo `config/cors.php` tenga esta configuración:

```php
<?php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Content-Disposition'],
    'max_age' => 86400,
    'supports_credentials' => true,
];
```

## Problemas comunes

1. **"Token no encontrado"**: Asegúrate de que el usuario esté autenticado y el token se guarde en localStorage.

2. **"CORS error"**: Verifica que las URLs del frontend estén en la lista de `allowed_origins` en `cors.php`.

3. **"401 Unauthorized"**: El token ha expirado o es inválido. El usuario debe volver a iniciar sesión.

4. **Las imágenes no cargan**: Asegúrate de ejecutar `php artisan storage:link` en el servidor de Railway.

