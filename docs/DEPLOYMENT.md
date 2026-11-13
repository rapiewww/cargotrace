# CargoTrace Production Deployment Guide

This guide describes how to deploy CargoTrace to an Ubuntu VPS (22.04 LTS or 24.04 LTS) using Docker Compose and Caddy for automatic HTTPS, zero-configuration TLS certificate renewal, reverse proxying, and scheduled background workers.

---

## 1. Architecture Overview

A production CargoTrace instance comprises three Docker services and one host reverse proxy:

```
                  ┌─────────────────────────────────────────┐
                  │          Internet / HTTPS :443          │
                  └────────────────────┬────────────────────┘
                                       │
                                       ▼
                  ┌─────────────────────────────────────────┐
                  │       Caddy Web Server (Host TLS)       │
                  │   - Automatic Let's Encrypt / ZeroSSL   │
                  │   - Security Headers & Hidden File Gate │
                  └────────────────────┬────────────────────┘
                                       │ HTTP :8000
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│ Docker Compose Network: cargotrace_default                                   │
│                                                                             │
│  ┌───────────────────────┐             ┌──────────────────────────────────┐ │
│  │   cargotrace_app      │             │       cargotrace_scheduler       │ │
│  │  - Non-root user      │             │  - Runs `artisan schedule:work`  │ │
│  │  - Multi-stage build  │             │  - Hourly delay evaluations      │ │
│  │  - Healthcheck (/up)  │             │  - Dependent on app & db         │ │
│  └───────────┬───────────┘             └────────────────┬─────────────────┘ │
│              │                                          │                   │
│              └───────────────────┬──────────────────────┘                   │
│                                  │                                          │
│                                  ▼                                          │
│                    ┌───────────────────────────┐                            │
│                    │       cargotrace_db       │                            │
│                    │  - MySQL 8.4              │                            │
│                    │  - Persistent storage vol │                            │
│                    └───────────────────────────┘                            │
└─────────────────────────────────────────────────────────────────────────────┘
```

- **`cargotrace_app`**: Serves web and API requests, performs atomic migrations on startup, and exposes a container healthcheck at `http://localhost:8000/up`.
- **`cargotrace_scheduler`**: Runs `php artisan schedule:work` to execute scheduled background commands, including the hourly `cargotrace:evaluate-delays` job.
- **`cargotrace_db`**: MySQL 8.4 relational database engine with persistent Docker volume storage.
- **Caddy**: Runs on the host OS as systemd service, binds `:80` and `:443`, terminates TLS automatically, and proxies to `127.0.0.1:8000`.

---

## 2. Local Docker Build Verification

Before deploying to a remote host, verify the Docker container build locally:

```bash
# 1. Build and boot containers locally
docker compose up --build -d

# 2. Check running container status
docker compose ps

# 3. Verify healthcheck on port 8000
curl -f http://localhost:8000/up

# 4. View application and migration logs
docker compose logs -f app

# 5. Stop containers when verification is complete
docker compose down
```

---

## 3. Ubuntu VPS Production Setup (Step-by-Step)

### Step 3.1: Server Prerequisites & Firewall

Connect to your Ubuntu VPS via SSH:

```bash
ssh deploy@your-server-ip
```

Update system packages and install Docker, Docker Compose, Git, and Caddy:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl git ufw debian-keyring debian-archive-keyring apt-transport-https

# Install official Docker engine
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER

# Install Caddy
curl -1sLF 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | sudo gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg
curl -1sLF 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | sudo tee /etc/apt/sources.list.d/caddy-stable.list
sudo apt update
sudo apt install -y caddy

# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow http
sudo ufw allow https
sudo ufw --force enable
```

*Note: Log out and log back in for docker group membership to take effect.*

---

### Step 3.2: Clone Repository & Configure Environment

Clone the repository to `/opt/cargotrace` (or your preferred deployment directory):

```bash
sudo mkdir -p /opt/cargotrace
sudo chown -R $USER:$USER /opt/cargotrace
git clone https://github.com/your-org/cargotrace.git /opt/cargotrace
cd /opt/cargotrace
```

Create production configuration from the provided template:

```bash
cp .env.production.example .env
```

Generate a secure application key:

```bash
# Generate random 32-character base64 key
echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env
```

Open `.env` in an editor and configure:

```bash
nano .env
```

Ensure the following production values are configured:

```ini
APP_NAME="CargoTrace"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cargotrace.yourdomain.com

# Reverse Proxy
TRUSTED_PROXIES=*

# Database Credentials
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=cargotrace
DB_USERNAME=cargotrace
DB_PASSWORD=YOUR_GENERATE_RANDOM_SECURE_PASSWORD

# Session Security
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Logging
LOG_CHANNEL=stderr
LOG_LEVEL=info

# Map Tile & External Services
CARGOTRACE_MAP_TILE_URL=https://tile.openstreetmap.org/{z}/{x}/{y}.png
NOMINATIM_BASE_URL=https://nominatim.openstreetmap.org
```

---

### Step 3.3: Configure Caddy Reverse Proxy

Edit `/etc/caddy/Caddyfile`:

```bash
sudo nano /etc/caddy/Caddyfile
```

Add the following configuration (replace `cargotrace.yourdomain.com` with your real domain):

```caddy
cargotrace.yourdomain.com {
    # Block access to hidden files and sensitive directories
    @blocked path */.*
    respond @blocked 404

    # Reverse proxy to CargoTrace container on port 8000
    reverse_proxy 127.0.0.1:8000 {
        header_up Host {host}
        header_up X-Real-IP {remote_host}
        header_up X-Forwarded-For {remote_host}
        header_up X-Forwarded-Proto https
    }

    # Compression
    encode zstd gzip

    # Logging
    log {
        output file /var/log/caddy/cargotrace.access.log {
            roll_size 50mb
            roll_keep 5
        }
    }
}
```

Reload Caddy:

```bash
sudo systemctl restart caddy
```

---

### Step 3.4: Launch Production Containers

From `/opt/cargotrace`:

```bash
# Build images and start services in background
docker compose up --build -d

# Check service health
docker compose ps
```

Verify that `cargotrace_app`, `cargotrace_scheduler`, and `cargotrace_db` are all reported as healthy or running.

Check healthcheck endpoint:

```bash
curl -f https://cargotrace.yourdomain.com/up
```

---

## 4. Scheduled Delay Evaluation & Background Workers

CargoTrace includes the `cargotrace:evaluate-delays` command which evaluates ETA delays and dispatches notifications.

In production:
- The `cargotrace_scheduler` container automatically runs `php artisan schedule:work`.
- The schedule in `routes/console.php` executes `cargotrace:evaluate-delays` hourly.
- The command is idempotent: repeated executions produce no duplicate notifications or redundant database records.

To manually trigger delay evaluation inside the running container:

```bash
docker compose exec app php artisan cargotrace:evaluate-delays
```

---

## 5. Database Backup and Restoration

### Automated Nightly Backup Script

Create backup script at `/opt/cargotrace/backup.sh`:

```bash
#!/bin/bash
set -e

BACKUP_DIR="/opt/cargotrace/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
FILENAME="$BACKUP_DIR/cargotrace_db_$TIMESTAMP.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "Creating MySQL database backup..."
docker compose -f /opt/cargotrace/docker-compose.yml exec -T db \
    mariadb-dump -u cargotrace -p"$(grep DB_PASSWORD /opt/cargotrace/.env | cut -d '=' -f2)" cargotrace \
    | gzip > "$FILENAME"

echo "Backup created at $FILENAME"

# Retain backups for 14 days
find "$BACKUP_DIR" -type f -name "cargotrace_db_*.sql.gz" -mtime +14 -delete
```

Make executable and add to host crontab (`crontab -e`):

```cron
0 2 * * * /opt/cargotrace/backup.sh >> /var/log/cargotrace_backup.log 2>&1
```

### Database Restoration

To restore from a backup:

```bash
gunzip < /opt/cargotrace/backups/cargotrace_db_20261113_020000.sql.gz | \
    docker compose exec -T db mysql -u cargotrace -pYOUR_DB_PASSWORD cargotrace
```

---

## 6. Zero-Downtime Application Updates & Rollback

### Deploying a New Release

To deploy an updated release:

```bash
cd /opt/cargotrace

# 1. Fetch latest changes
git pull origin main

# 2. Build updated images and recreate containers seamlessly
docker compose up --build -d

# 3. Verify health status
docker compose ps
curl -f https://cargotrace.yourdomain.com/up
```

The container entrypoint will automatically:
- Wait for the database.
- Run any new migrations (`php artisan migrate --force`).
- Warm all production caches (`config:cache`, `route:cache`, `view:cache`, `event:cache`).

### Rollback Procedure

If an issue occurs after a deployment:

```bash
cd /opt/cargotrace

# 1. Check git history and checkout previous stable commit or tag
git checkout <previous-commit-or-tag>

# 2. Rebuild and restart containers
docker compose up --build -d

# 3. If a database migration needs to be rolled back:
docker compose exec app php artisan migrate:rollback --step=1 --force

# 4. Verify system health
curl -f https://cargotrace.yourdomain.com/up
```

---

## 7. Security Hardening & Gate Checklist

- [x] **Non-root execution**: Container runs under `USER www-data`.
- [x] **Zero source leak**: `.dockerignore` blocks `.env*`, `.git`, `.claude`, `.gemini`, `tests/`.
- [x] **Middleware header protection**: `SecurityHeadersMiddleware` sets `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, and `Strict-Transport-Security`.
- [x] **URL probe gate**: Requests attempting to read `/.env`, `/.git`, or `/.claude` return `404 Not Found`.
- [x] **Rate limiting**:
  - `POST /login`: 10 requests per minute (`throttle:10,1`).
  - `GET /track/{tracking_number}`: 30 requests per minute (`throttle:30,1`).
- [x] **Configurable OSM tile server**: Set via `CARGOTRACE_MAP_TILE_URL` in `.env`.
- [x] **Health check**: Built-in `/up` route returns HTTP 200 and is polled every 30s by Docker.

