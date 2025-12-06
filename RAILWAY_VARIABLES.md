# Variables de Entorno para Railway - Smart Inventory

## ⚠️ IMPORTANTE: Cambiar estas variables en Railway

En el panel de Railway, ve a tu servicio `laravel_smart_inventory` → pestaña **Variables** y cambia estas variables:

### 🔴 CAMBIAR ESTAS (Base de Datos):

```env
DB_CONNECTION="mysql"
DB_HOST="${RAILWAY_PRIVATE_DOMAIN}"
DB_PORT="3306"
DB_DATABASE="railway"
DB_USERNAME="root"
DB_PASSWORD="JCKlLIBubsmoWUcKwwdVAqEQBdUzCnPj"
```

**O si Railway no resuelve `${RAILWAY_PRIVATE_DOMAIN}`, usa las variables automáticas de Railway:**

```env
DB_CONNECTION="mysql"
DB_HOST="${MYSQLHOST}"
DB_PORT="${MYSQLPORT}"
DB_DATABASE="${MYSQLDATABASE}"
DB_USERNAME="${MYSQLUSER}"
DB_PASSWORD="${MYSQLPASSWORD}"
```

### ✅ MANTENER ESTAS (Ya están bien):

```env
APP_NAME="SmartInventory"
APP_ENV="production"
APP_KEY="base64:8S40MShiXtudAKBzWRC4Vu+I19943BzfJkcezvqiRPY="
APP_DEBUG="false"
APP_URL="https://laravelsmartinventory-production.up.railway.app"
APP_TIMEZONE="America/Bogota"
LOG_CHANNEL="stack"
LOG_LEVEL="debug"
SESSION_DRIVER="file"
SESSION_LIFETIME="120"
QUEUE_CONNECTION="database"
CACHE_STORE="database"
MAIL_MAILER="smtp"
MAIL_HOST="smtp.gmail.com"
MAIL_PORT="587"
MAIL_USERNAME="smartinventory685@gmail.com"
MAIL_PASSWORD="igqtzwrjedtjwsgp"
MAIL_ENCRYPTION="tls"
MAIL_FROM_ADDRESS="smartinventory685@gmail.com"
MAIL_FROM_NAME="Smart Inventory"
FRONTEND_URL="http://localhost:4200"
```

### 🔵 AGREGAR ESTA (Sanctum para Android):

```env
SANCTUM_STATEFUL_DOMAINS="localhost,localhost:4200,127.0.0.1,127.0.0.1:4200,127.0.0.1:8000,laravelsmartinventory-production.up.railway.app,*.railway.app"
```

### 📝 NOTAS IMPORTANTES:

- **NO uses `127.0.0.1`** en Railway, eso solo funciona en tu PC local
- Railway resuelve automáticamente las variables `${MYSQLHOST}`, `${MYSQLPORT}`, etc. si están en el mismo proyecto
- Si no funcionan las variables automáticas, usa los valores directos del servicio MySQL
- Después de cambiar las variables, Railway reiniciará automáticamente el servicio
- Verifica que el deploy esté funcionando en la pestaña "Deployments"

