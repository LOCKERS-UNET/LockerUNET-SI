# Despliegue de Lockers UNET

Guía para publicar la app en un servidor Linux propio. Está escrita para una máquina virtual de **Azure for Students**
con Ubuntu 24.04, pero sirve igual para cualquier servidor con Ubuntu.

La app necesita: PHP 8.5, MySQL 8, Nginx, Composer, Node 22 (solo para compilar el frontend) y un cron. **No** necesita
Redis ni un worker de colas: no hay trabajos en cola y los correos salen en el momento.

## 1. Crear la máquina virtual (Azure for Students)

1. Entrar a <https://azure.microsoft.com/free/students> con el correo `@unet.edu.ve` (no pide tarjeta).
2. Crear una máquina virtual:
   - Imagen: **Ubuntu Server 24.04 LTS**.
   - Tamaño: **B1s** (1 vCPU, 1 GB). Está dentro de los servicios gratuitos de los primeros 12 meses (750 horas al mes:
     alcanza para tenerla encendida todo el mes).
   - Autenticación con clave SSH.
   - Puertos de entrada: **22** (SSH), **80** (HTTP) y **443** (HTTPS).
3. En la IP pública de la máquina, ponerle una **etiqueta DNS** (por ejemplo `lockersunet`). Así la app queda en
   `https://lockersunet.<región>.cloudapp.azure.com` sin comprar dominio.

> **Costos.** Los primeros 12 meses la VM B1s y su disco entran en los servicios gratuitos; los $100 de crédito cubren lo
> demás (la IP pública cuesta unos pocos dólares al mes). Después del primer año, la cuenta de estudiante se renueva con
> otros $100 mientras sigas siendo estudiante, pero la VM pasa a cobrarse del crédito (del orden de $10–13 al mes en total):
> alcanza para unos 8–9 meses. **Si el crédito se acaba, Azure deshabilita la suscripción y la página deja de verse** hasta
> pasar a pago por uso. Revisa el saldo en *Cost Management* cada cierto tiempo.

## 2. Preparar el servidor

Conectarse por SSH y, como usuario con `sudo`:

```bash
# Memoria de intercambio: 1 GB de RAM se queda corto al compilar el frontend
sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# PHP 8.5 (repositorio de Ondřej Surý), Nginx, MySQL y utilidades
sudo add-apt-repository -y ppa:ondrej/php
sudo apt update
sudo apt install -y nginx mysql-server git unzip \
    php8.5-fpm php8.5-cli php8.5-mysql php8.5-mbstring php8.5-xml php8.5-curl php8.5-zip php8.5-bcmath php8.5-intl

# Composer
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer

# Node 22 (solo para compilar el frontend)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash - && sudo apt install -y nodejs
```

Comprobar: `php -v` debe decir **8.5.12 o más nuevo**.

## 3. Base de datos

```bash
sudo mysql
```

```sql
CREATE DATABASE lockers_unet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lockers'@'localhost' IDENTIFIED BY 'UNA-CONTRASEÑA-LARGA';
GRANT ALL PRIVILEGES ON lockers_unet.* TO 'lockers'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 4. Código y configuración

```bash
sudo mkdir -p /var/www && sudo chown $USER:www-data /var/www
cd /var/www
git clone https://github.com/LOCKERS-UNET/LockerUNET-SI.git
cd LockerUNET-SI
git checkout main

cp .env.example .env
```

Editar `.env` (nunca subirlo al repo):

```dotenv
APP_NAME="Lockers UNET"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://lockersunet.<región>.cloudapp.azure.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lockers_unet
DB_USERNAME=lockers
DB_PASSWORD=UNA-CONTRASEÑA-LARGA

# Correo: Gmail con contraseña de aplicación
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=la-cuenta@gmail.com
MAIL_PASSWORD=la-contraseña-de-aplicación
MAIL_FROM_ADDRESS=la-cuenta@gmail.com
MAIL_FROM_NAME="Lockers UNET"

# Sin proceso de render en el servidor (SSR): la app se dibuja en el navegador
INERTIA_SSR_ENABLED=false

LOG_LEVEL=warning
```

Instalar y preparar:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
rm -f public/hot                      # si existe, la app buscaría el servidor de Vite de desarrollo

php artisan key:generate
php artisan migrate --seed --force    # SOLO la primera vez: crea las tablas, el admin, los lockers y los aranceles
php artisan storage:link
php artisan optimize

sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

> **Admin sembrado.** El seeder crea `adminBE@unet.edu.ve` con una contraseña que está escrita en el repo. Antes de
> abrir la app a los estudiantes, crear el admin real y cambiar esa contraseña.

## 5. Nginx

Crear `/etc/nginx/sites-available/lockers-unet`:

```nginx
server {
    listen 80;
    server_name lockersunet.<región>.cloudapp.azure.com;
    root /var/www/LockerUNET-SI/public;

    index index.php;
    charset utf-8;
    client_max_body_size 10M;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/lockers-unet /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

## 6. HTTPS (Let's Encrypt, gratis)

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d lockersunet.<región>.cloudapp.azure.com
```

Certbot renueva el certificado solo.

> Si algún día se pone un proxy delante (Cloudflare, un balanceador), hay que configurar los *trusted proxies* en
> `bootstrap/app.php`; con Nginx directo, como aquí, no hace falta.

## 7. Tareas programadas

`app:check-payments` marca los pagos vencidos y avisa a los estudiantes cada día. Necesita el cron de Laravel:

```bash
crontab -e
```

```cron
* * * * * cd /var/www/LockerUNET-SI && php artisan schedule:run >> /dev/null 2>&1
```

## 8. Respaldo diario de la base

```bash
mkdir -p ~/respaldos
crontab -e
```

```cron
30 3 * * * mysqldump --single-transaction -u lockers -p'UNA-CONTRASEÑA-LARGA' lockers_unet | gzip > ~/respaldos/lockers-$(date +\%F).sql.gz && find ~/respaldos -mtime +14 -delete
```

## 9. Publicar una nueva versión

```bash
cd /var/www/LockerUNET-SI
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -f public/hot
php artisan migrate --force           # nunca --seed ni migrate:fresh en producción
php artisan optimize
php artisan up
```

## Comprobaciones después de publicar

- `https://…/login` abre con candado (HTTPS) y sin errores.
- Registrarse con un correo `@unet.edu.ve` de prueba: llega el código.
- Entrar como admin y abrir cada sección del menú.
- `php artisan schedule:list` muestra `app:check-payments`.
- `storage/logs/laravel.log` no muestra errores nuevos.
