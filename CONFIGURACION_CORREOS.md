# 📧 Configuración de Correos para Alertas

## ✅ Tabla notifications creada correctamente

Ya se creó la tabla `notifications` en la base de datos.

## ⚙️ Configurar el envío de correos

Edita tu archivo `.env` en `C:\xampp\htdocs\LARAVEL-PROYECTO\project\.env`

### Opción 1: Mailtrap (RECOMENDADO para desarrollo)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=tu-username-mailtrap
MAIL_PASSWORD=tu-password-mailtrap
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@smartinventory.com
MAIL_FROM_NAME="Smart Inventory"

FRONTEND_URL=http://localhost:4200
```

**Cómo obtener credenciales de Mailtrap:**
1. Ve a https://mailtrap.io/
2. Crea una cuenta gratuita
3. Copia el username y password de SMTP

### Opción 2: Gmail (Para producción)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-contraseña-de-aplicacion
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@smartinventory.com
MAIL_FROM_NAME="Smart Inventory"

FRONTEND_URL=http://localhost:4200
```

**Importante:** Para Gmail necesitas:
1. Activar "Verificación en 2 pasos"
2. Generar una "Contraseña de aplicación" en https://myaccount.google.com/apppasswords

### Opción 3: Solo logs (Desarrollo local)

```env
MAIL_MAILER=log
FRONTEND_URL=http://localhost:4200
```

Los correos se guardan en `storage/logs/laravel.log`

## 🧪 Probar el envío de correos

Después de configurar el `.env`, ejecuta:

```bash
cd C:\xampp\htdocs\LARAVEL-PROYECTO\project
php artisan config:clear
php artisan test:email-alert
```

## 🔄 Después de configurar

1. Limpia la caché:
```bash
php artisan config:clear
php artisan cache:clear
```

2. Genera una alerta de prueba:
   - Haz una salida de inventario que deje un producto con stock 0 o bajo
   - Revisa tu correo o Mailtrap

3. Verifica que Angular esté corriendo:
```bash
cd C:\xampp\htdocs\ANGULAR-PROYECTO\proyecto
ng serve
```

## 📝 Notas

- Laravel está configurado actualmente con `MAIL_MAILER=log`
- Los correos se están intentando enviar pero se guardan en logs
- La tabla `notifications` ya existe y funciona correctamente
- El error "NgClass" en Angular ya fue corregido


