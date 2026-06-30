# Deployment Guide — CRM Platform (Step by Step)

Ye guide aapko zero se lekar live deployment tak le jayegi. Do paths hain — Docker (recommended, aasan) aur Bare-metal/VPS (Docker ke bina). Jo bhi aapke server pe available ho wo follow karein.

---

## Pehle: Server Requirements Check Karein

Deploy karne se pehle confirm karein ke aapke paas ye hai:

- Ek server/VPS (DigitalOcean, AWS, Hostinger VPS, ya koi bhi Linux server) — minimum 2GB RAM
- Ek domain name jo us server ki IP pe point ho raha ho
- SSH access us server tak
- WhatsApp Business API access (Meta Business Manager se) — agar abhi tak nahi hai to ye sabse pehle setup karein, isme time lagta hai (verification process)

Agar in mein se koi cheez missing hai, deployment shuru karne se pehle wo arrange kar lein.

---

# Path A: Docker Deployment (Recommended)

## Step 1 — Server Pe Login Karein

```bash
ssh root@your-server-ip
```

## Step 2 — Docker Install Karein

```bash
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo apt install docker-compose-plugin -y
```

Confirm karein install ho gaya:
```bash
docker --version
docker compose version
```

## Step 3 — Project Files Server Pe Le Jayein

Agar code GitHub pe hai:
```bash
git clone https://github.com/your-repo/crm-platform.git
cd crm-platform
```

Agar zip file se upload kar rahe hain (jo maine diya):
```bash
# Apne local computer se server pe zip bhejein
scp fullproject.zip root@your-server-ip:/var/www/

# Server pe wapas jaa kar
cd /var/www/
unzip fullproject.zip
mv fullproject crm-platform
cd crm-platform
```

## Step 4 — Environment File Setup Karein

```bash
cp .env.example .env
nano .env
```

Ye values zaroor fill karein (baqi defaults theek hain):

```
APP_URL=https://yourdomain.com
DB_DATABASE=crm_platform
DB_USERNAME=crm_user
DB_PASSWORD=  ← ek strong password yahan dalein

WHATSAPP_PHONE_NUMBER_ID=  ← Meta dashboard se
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_VERIFY_TOKEN=  ← khud ek random string banayein (e.g. "myCrm2026SecretToken")
WHATSAPP_APP_SECRET=

ANTHROPIC_API_KEY=  ← Anthropic console se (console.anthropic.com)
```

**Important**: `DB_HOST=db` hi rehne dein (already set hai) — ye Docker container ka naam hai, isay 127.0.0.1 mat karein, warna database connect nahi hoga.

Save karne ke liye: `Ctrl+O`, phir `Enter`, phir `Ctrl+X`

## Step 5 — Docker Containers Build Aur Start Karein

```bash
docker compose build
docker compose up -d
```

Ye 5 containers start karega: app, nginx, database, redis, queue worker, scheduler. Status check karein:
```bash
docker compose ps
```
Sab "Up" dikhne chahiye.

## Step 6 — Laravel Setup Commands Chalayein

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --class=RbacSeeder
```

Ye database tables bana dega aur ek default admin account create karega.

## Step 7 — SSL Certificate Lagayein

Free SSL ke liye Certbot use karein:

```bash
sudo apt install certbot -y
sudo certbot certonly --standalone -d yourdomain.com
```

Certificate files yahan milenge: `/etc/letsencrypt/live/yourdomain.com/`

In files ko deploy folder mein copy karein:
```bash
mkdir -p deploy/ssl
sudo cp /etc/letsencrypt/live/yourdomain.com/fullchain.pem deploy/ssl/
sudo cp /etc/letsencrypt/live/yourdomain.com/privkey.pem deploy/ssl/
```

`deploy/nginx.conf` file mein `server_name yourdomain.com` ko apne actual domain se replace karein, phir nginx container restart karein:
```bash
docker compose restart nginx
```

## Step 8 — Test Karein

Browser mein jayein: `https://yourdomain.com/login`

Login karein:
- Email: `admin@example.com`
- Password: `ChangeMe123!`

**Foran password change karein** — `/admin/users` page pe jaa kar apna khud ka admin account banayein, phir is default account ko deactivate kar dein.

---

# Path B: Bare-Metal / VPS (Docker Ke Bina)

Agar aapke server pe pehle se PHP/MySQL/Nginx installed hai aur Docker nahi chahte.

## Step 1 — Required Software Install Karein

```bash
sudo apt update
sudo apt install php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath mysql-server nginx supervisor git unzip -y

# Composer install karein
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Step 2 — MySQL Database Banayein

```bash
sudo mysql
```
```sql
CREATE DATABASE crm_platform;
CREATE USER 'crm_user'@'localhost' IDENTIFIED BY 'apna-strong-password';
GRANT ALL PRIVILEGES ON crm_platform.* TO 'crm_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## Step 3 — Code Server Pe Le Jayein

```bash
cd /var/www
sudo git clone https://github.com/your-repo/crm-platform.git
# ya zip se: unzip fullproject.zip && mv fullproject crm-platform
cd crm-platform
sudo chown -R www-data:www-data /var/www/crm-platform
```

## Step 4 — Environment File Setup Karein

```bash
cp .env.example .env
nano .env
```

Yahan **`DB_HOST=127.0.0.1`** karein (Docker wale step se different — kyunki ab MySQL isi server pe hai, alag container mein nahi). Baqi WhatsApp/AI keys wahi fill karein jo upar bataya.

## Step 5 — Deploy Script Chalayein

```bash
chmod +x deploy/deploy.sh
./deploy/deploy.sh
```

Ye automatically: composer install, key generate, migrate, aur caching kar dega.

```bash
php artisan db:seed --class=RbacSeeder
```

## Step 6 — Queue Worker Aur Scheduler Setup Karein

Queue worker (background messages bhejne ke liye):
```bash
sudo cp deploy/supervisor-queue.conf /etc/supervisor/conf.d/
sudo nano /etc/supervisor/conf.d/supervisor-queue.conf
# command line mein path /var/www/crm-platform sahi hai confirm karein
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start crm-queue:*
```

Scheduler (nightly backup ke liye):
```bash
crontab -u www-data -e
```
Ye line add karein (`deploy/crontab.txt` mein bhi hai):
```
* * * * * cd /var/www/crm-platform && php artisan schedule:run >> /dev/null 2>&1
```

## Step 7 — Nginx Setup Karein

```bash
sudo cp deploy/nginx.conf /etc/nginx/sites-available/crm-platform
sudo nano /etc/nginx/sites-available/crm-platform
```
Is file mein:
- `server_name yourdomain.com` → apna domain dalein
- `fastcgi_pass app:9000` → ise `fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;` se replace karein (Docker wala syntax yahan kaam nahi karega)
- SSL paths ko Certbot ke actual paths se replace karein (next step)

```bash
sudo ln -s /etc/nginx/sites-available/crm-platform /etc/nginx/sites-enabled/
sudo nginx -t
```

## Step 8 — SSL Lagayein

```bash
sudo apt install certbot python3-certbot-nginx -y
sudo certbot --nginx -d yourdomain.com
sudo systemctl restart nginx
```

## Step 9 — Test Karein

`https://yourdomain.com/login` pe jaa kar same admin credentials se login karein jaisa Path A mein bataya.

---

# Dono Paths Ke Baad — Ye Zaroor Karein

## 1. WhatsApp Webhook Connect Karein

Meta Business Dashboard mein jayein → WhatsApp → Configuration → Webhook:

- Callback URL: `https://yourdomain.com/api/webhooks/whatsapp`
- Verify Token: wahi value jo aapne `.env` mein `WHATSAPP_VERIFY_TOKEN` ki rakhi thi

"Verify and Save" click karein. Agar error aaye, to `.env` ka token aur Meta dashboard ka token match check karein.

## 2. Ek Test Message Bhej Kar Confirm Karein

Apne WhatsApp Business number pe khud se ek message bhejein, phir CRM ke `/inbox` page pe check karein ke wo message aaya ya nahi. Agar nahi aaya:
```bash
docker compose logs app --tail=50
```
ya bare-metal pe:
```bash
tail -50 storage/logs/laravel.log
```

## 3. Real Admin Account Banayein

`/admin/users` pe jaa kar apna real admin account banayein, phir default `admin@example.com` ko deactivate kar dein.

## 4. Backup Test Karein

```bash
docker compose exec app php artisan backup:run
```
ya bare-metal pe:
```bash
php artisan backup:run
```

`storage/app/backups/` folder mein ek `.sql` file dikhni chahiye.

---

# Agar Koi Cheez Kaam Na Kare

| Problem | Pehle Ye Check Karein |
|---|---|
| Website nahi khulti | `docker compose ps` (sab "Up" hain?) ya `sudo systemctl status nginx php8.3-fpm` |
| 500 error | Logs check karein: `storage/logs/laravel.log` |
| Database error | `.env` mein DB_HOST sahi hai? (Docker = `db`, bare-metal = `127.0.0.1`) |
| WhatsApp message nahi aa raha | Webhook URL Meta dashboard mein sahi hai? Verify token match karta hai? |
| Login nahi ho raha | Migrations chal chuki hain? `php artisan migrate:status` se check karein |

---

Agar kisi specific step pe atak jayein, wo exact error message share karein — uske hisaab se specific fix bata sakta hoon.
