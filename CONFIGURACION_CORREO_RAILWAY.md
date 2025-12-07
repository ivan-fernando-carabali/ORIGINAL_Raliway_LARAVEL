# 📧 Configuración de Correo en Railway - Solución Definitiva

## ⚠️ Problema
En producción (Railway) no se están enviando correos de órdenes y alertas, aunque funciona correctamente en local.

## ✅ Solución

### 1. Variables de Entorno Requeridas en Railway

Ve a tu proyecto en Railway → **Variables** y asegúrate de tener estas variables configuradas:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=smartinventory685@gmail.com
MAIL_PASSWORD=igqtzwrjedtjwsgp
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=smartinventory685@gmail.com
MAIL_FROM_NAME="Smart Inventory"
```

### 2. Pasos para Configurar en Railway

1. **Accede a Railway Dashboard**
   - Ve a tu proyecto: https://railway.app
   - Selecciona tu servicio de Laravel

2. **Ve a la sección Variables**
   - Click en **Variables** en el menú lateral
   - O ve a **Settings** → **Variables**

3. **Agrega/Verifica cada variable:**
   - `MAIL_MAILER` = `smtp`
   - `MAIL_HOST` = `smtp.gmail.com`
   - `MAIL_PORT` = `587`
   - `MAIL_USERNAME` = `smartinventory685@gmail.com`
   - `MAIL_PASSWORD` = `igqtzwrjedtjwsgp` (Contraseña de aplicación de Gmail)
   - `MAIL_ENCRYPTION` = `tls`
   - `MAIL_FROM_ADDRESS` = `smartinventory685@gmail.com`
   - `MAIL_FROM_NAME` = `Smart Inventory`

4. **Reinicia el servicio**
   - Después de agregar las variables, Railway debería reiniciar automáticamente
   - Si no, ve a **Deployments** y haz click en **Redeploy**

### 3. Verificar Configuración

#### Opción A: Usar el comando de prueba
```bash
php artisan test:email-alert
```

#### Opción B: Verificar en los logs
Después de intentar crear una orden, revisa los logs en Railway:
- Ve a **Deployments** → Click en el deployment más reciente → **View Logs**
- Busca mensajes que empiecen con `📧 Configuración de correo:`
- Si ves `MAIL_MAILER=log`, las variables no están configuradas correctamente

### 4. Problemas Comunes y Soluciones

#### ❌ Error: "MAIL_MAILER está configurado como 'log'"
**Causa:** La variable `MAIL_MAILER` no está configurada o está mal escrita.

**Solución:**
1. Verifica que en Railway la variable se llame exactamente `MAIL_MAILER` (sin espacios)
2. El valor debe ser `smtp` (en minúsculas)
3. Reinicia el servicio

#### ❌ Error: "Configuración de correo incompleta"
**Causa:** Faltan variables `MAIL_HOST` o `MAIL_USERNAME`.

**Solución:**
1. Verifica que todas las variables estén configuradas
2. Asegúrate de que no tengan espacios extra al inicio o final
3. Reinicia el servicio

#### ❌ Error: "Error de conexión SMTP"
**Causa:** 
- Credenciales incorrectas
- Gmail bloqueando el acceso
- Puerto incorrecto

**Solución:**
1. Verifica que `MAIL_PASSWORD` sea una **Contraseña de aplicación** de Gmail, no tu contraseña normal
2. Para generar una contraseña de aplicación:
   - Ve a https://myaccount.google.com/apppasswords
   - Genera una nueva contraseña para "Mail"
   - Úsala en `MAIL_PASSWORD`
3. Verifica que `MAIL_PORT=587` y `MAIL_ENCRYPTION=tls`

#### ❌ Error: "Connection timeout"
**Causa:** Railway puede tener restricciones de red para SMTP.

**Solución:**
1. Verifica que el puerto 587 esté permitido
2. Considera usar un servicio de correo alternativo como:
   - **SendGrid** (recomendado para producción)
   - **Mailgun**
   - **Postmark**

### 5. Configuración Alternativa: SendGrid (Recomendado para Producción)

Si Gmail sigue dando problemas, usa SendGrid:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=TU_API_KEY_DE_SENDGRID
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.com
MAIL_FROM_NAME="Smart Inventory"
```

### 6. Verificar que Funciona

1. **Crea una orden desde el frontend**
2. **Revisa los logs en Railway:**
   - Deberías ver: `✅ Email enviado exitosamente a: [email]`
3. **Revisa el correo del proveedor**
   - El correo debería llegar en unos segundos

### 7. Logs de Depuración

El código ahora registra información detallada:
- `📧 Configuración de correo:` - Muestra la configuración actual
- `✅ Email enviado exitosamente` - Confirmación de envío
- `❌ Error de conexión SMTP` - Error de conexión
- `❌ Error enviando email` - Otros errores

Revisa estos logs en Railway para diagnosticar problemas.

## 📝 Notas Importantes

- **Nunca** uses tu contraseña normal de Gmail, siempre usa una **Contraseña de aplicación**
- Las variables de entorno en Railway son **case-sensitive**
- Después de cambiar variables, Railway reinicia automáticamente
- Si cambias `MAIL_MAILER` a `log`, los correos se guardarán en `storage/logs/laravel.log` en lugar de enviarse

## 🔍 Comandos Útiles

```bash
# Verificar configuración actual
php artisan tinker
>>> config('mail.default')
>>> config('mail.mailers.smtp.host')
>>> config('mail.from.address')

# Probar envío de correo
php artisan test:email-alert
```

