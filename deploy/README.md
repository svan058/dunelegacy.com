# Deployment Guide

## DigitalOcean App Platform Setup

### Prerequisites

1. **DigitalOcean Account**: Sign up at https://digitalocean.com
2. **GitHub Repository**: Push this repo to GitHub at `dunelegacy/dunelegacy.com`
3. **GoDaddy DNS**: Access to `dunelegacy.com` DNS settings

### Step 1: Create App on DigitalOcean

**Via Web Console:**
1. Go to https://cloud.digitalocean.com/apps
2. Click "Create App"
3. Select "GitHub" as source
4. Authorize DigitalOcean to access your GitHub
5. Select repository: `dunelegacy/dunelegacy.com`
6. Branch: `main`
7. Choose "Use existing spec" and upload `app.yaml`
8. Review and Create

**Via CLI (doctl):**
```bash
# Install doctl
brew install doctl  # macOS
# or: snap install doctl  # Linux

# Authenticate
doctl auth init

# Create app from spec
doctl apps create --spec deploy/app.yaml

# Get app ID
doctl apps list

# Monitor deployment
doctl apps list-deployments <APP_ID>
```

### Step 2: Configure Custom Domain

**In DigitalOcean:**
1. Go to your app → Settings → Domains
2. Add domain: `dunelegacy.com`
3. Add domain: `www.dunelegacy.com`
4. Copy the CNAME/A record values

**In GoDaddy:**
1. Go to https://dcc.godaddy.com/domains
2. Select `dunelegacy.com`
3. Click "DNS" → "Manage DNS"
4. Add records:
   ```
   Type    Name    Value                           TTL
   A       @       <DigitalOcean IP>              600
   CNAME   www     <DigitalOcean domain>          600
   ```
5. Save changes

**Wait for DNS propagation** (5-60 minutes)

### Step 3: Enable HTTPS

DigitalOcean automatically provisions Let's Encrypt SSL certificates for your custom domains.

**Verify:**
- https://dunelegacy.com (should work)
- https://www.dunelegacy.com (should work)
- https://dunelegacy.com/metaserver/ (should work)

### Step 4: Update Game Client

Update the metaserver URL in the game's source code:

**File:** `dunelegacy/include/Definitions.h`
```cpp
#define DEFAULT_METASERVER "https://dunelegacy.com/metaserver/metaserver.php"
```

Rebuild and release new version.

---

## Costs

**DigitalOcean App Platform:**
- Static site (website): **$0/month** (free tier)
- PHP service (metaserver): **$5/month** (basic-xxs)
- **Total: ~$5/month**

No database needed yet (using file storage).

---

## Monitoring

**App Platform Dashboard:**
- https://cloud.digitalocean.com/apps
- View logs, metrics, deployments

**Metaserver Status:**
- https://dunelegacy.com/metaserver/

**Logs:**
```bash
doctl apps logs <APP_ID> --type RUN
doctl apps logs <APP_ID> --type BUILD
```

---

## Troubleshooting

### Website not loading
1. Check deployment status: `doctl apps list-deployments <APP_ID>`
2. View logs: `doctl apps logs <APP_ID>`
3. Verify DNS: `dig dunelegacy.com`

### Metaserver not working
1. Check service health: App Platform → Components → metaserver
2. View logs: `doctl apps logs <APP_ID> --type RUN`
3. Test locally:
   ```bash
   cd metaserver
   php -S localhost:8080
   curl "http://localhost:8080/metaserver.php?action=list"
   ```

### DNS not resolving
1. Check GoDaddy DNS records
2. Wait for propagation: https://dnschecker.org
3. Verify DigitalOcean domain settings

---

## Local Development

**Test website:**
```bash
cd website
python3 -m http.server 8000
# Visit: http://localhost:8000
```

**Test metaserver:**
```bash
cd metaserver
php -S localhost:8080
# Visit: http://localhost:8080
# API: http://localhost:8080/metaserver.php?action=list
```

---

## Deployment Flow

1. **Make changes** to website or metaserver
2. **Commit and push** to GitHub `main` branch
3. **Auto-deploy** triggered on DigitalOcean
4. **Live in ~2 minutes** at https://dunelegacy.com

---

## Rollback

If a deployment fails:
```bash
# List deployments
doctl apps list-deployments <APP_ID>

# Rollback to previous
doctl apps rollback <APP_ID> --deployment-id <PREVIOUS_DEPLOYMENT_ID>
```

---

## Support

- **DigitalOcean Docs**: https://docs.digitalocean.com/products/app-platform/
- **Community**: https://www.digitalocean.com/community
- **Support Tickets**: https://cloud.digitalocean.com/support/tickets

