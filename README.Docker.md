# Gemas B2B Portal - Docker Deployment

## 🚀 Quick Start

### Prerequisites
- Docker & Docker Compose installed
- Coolify instance running (optional)

### Local Development

1. **Clone Repository**
```bash
git clone <repository-url>
cd b2b-gemas-project-main
```

2. **Configure Environment**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

3. **Start Services**
```bash
docker-compose up -d --build
```

4. **Access Application**
- Application: http://localhost
- Traefik Dashboard: http://traefik.gemas.local

### Coolify Deployment

1. **Add Repository** in Coolify
2. **Set Environment Variables** from `.env.example`
3. **Deploy** - Coolify will automatically build and deploy

## 📦 Services

### Application (PHP 8.2-Apache)
- **Port**: 80 (via Traefik)
- **Features**:
  - PHP 8.2 with OPcache (max performance)
  - MSSQL drivers for Logo ERP integration
  - Redis session handler
  - Health check endpoint: `/healthcheck.php`

### Traefik (Reverse Proxy)
- **Ports**: 80 (HTTP), 443 (HTTPS)
- **Features**:
  - Automatic SSL with Let's Encrypt
  - HTTP to HTTPS redirect
  - Load balancing

### MySQL (MariaDB 10.11)
- **Port**: 3306 (internal)
- **Features**:
  - UTF8MB4 character set
  - 500 max connections
  - 512MB InnoDB buffer pool

### Redis (Cache & Sessions)
- **Port**: 6379 (internal)
- **Features**:
  - 256MB max memory
  - LRU eviction policy
  - Persistent storage

## 🔧 Configuration

### PHP Settings (php.ini)
- Memory Limit: **512M**
- Upload Max: **50M**
- Execution Time: **300s**
- OPcache: **Maximum performance**

### Database Import

1. **Export from local**
```bash
mysqldump -u root -p gemas_portal > backup.sql
```

2. **Import to Docker**
```bash
docker exec -i gemas_portal_db mysql -u root -p<password> gemas_portal < backup.sql
```

## 🏥 Health Checks

- **Application**: `curl http://localhost/healthcheck.php`
- **Database**: Automatic via Docker health check
- **Redis**: Automatic via Docker health check

## 🔐 Security

- Redis sessions with secure cookies
- HTTPS enforced via Traefik
- PHP expose_php disabled
- File upload restrictions

## 📊 Monitoring

Health check endpoint returns JSON:
```json
{
  "status": "healthy",
  "checks": {
    "database": {"status": "ok"},
    "redis": {"status": "ok"},
    "disk": {"used_percent": 45.2}
  }
}
```

## 🛠️ Troubleshooting

### Container logs
```bash
docker-compose logs -f app
```

### Restart services
```bash
docker-compose restart
```

### Rebuild
```bash
docker-compose down
docker-compose up -d --build
```

## 📝 Notes

- Logo ERP MSSQL connection is external (not in Docker)
- Uploads and PDFs are stored in named volumes
- Redis handles PHP sessions automatically
- Traefik manages SSL certificates automatically
