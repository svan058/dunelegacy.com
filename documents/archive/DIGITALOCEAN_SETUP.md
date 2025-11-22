# DigitalOcean Setup Guide

Complete guide to deploy Dune Legacy website and metaserver on DigitalOcean App Platform.

---

## Part 1: DigitalOcean App Platform Setup

### Step 1: Create DigitalOcean Account

1. Go to https://cloud.digitalocean.com/registrations/new
2. Sign up (free $200 credit for 60 days)
3. Verify your email

### Step 2: Connect GitHub

1. Go to https://cloud.digitalocean.com/apps
2. Click **"Create App"**
3. Choose **GitHub** as source
4. Click **"Manage Access"** → **"Install & Authorize"**
5. Select **svan058/dunelegacy.com** repository
6. Click **"Install & Authorize"**

### Step 3: Create App from Spec File

**Option A: Using the Web Interface**

1. After connecting GitHub, click **"Edit Your App Spec"** (bottom of page)
2. Delete the default YAML
3. Copy the contents from `.digitalocean/app.yaml`
4. Paste into the editor
5. Click **"Next"** → Review settings → **"Create Resources"**

**Option B: Using doctl CLI** (recommended for automation)

```bash
# Install doctl
brew install doctl

# Authenticate
doctl auth init

# Create app from spec file
doctl apps create --spec .digitalocean/app.yaml
```

### Step 4: Get App ID and API Token

**Get App ID:**
```bash
doctl apps list
# Copy the ID column value
```

Or from web: https://cloud.digitalocean.com/apps → Click your app → URL shows app ID

**Create API Token:**
1. Go to https://cloud.digitalocean.com/account/api/tokens
2. Click **"Generate New Token"**
3. Name: `github-actions-deploy`
4. Scopes: **Read and Write**
5. Click **"Generate Token"**
6. **Copy the token immediately** (you won't see it again!)

---

## Part 2: GitHub Secrets Setup

Add secrets to enable GitHub Actions deployment:

1. Go to https://github.com/svan058/dunelegacy.com/settings/secrets/actions
2. Click **"New repository secret"**

**Add these secrets:**

| Name | Value | Where to get it |
|------|-------|----------------|
| `DIGITALOCEAN_ACCESS_TOKEN` | Your API token | From Step 4 above |
| `DIGITALOCEAN_APP_ID` | Your app ID (e.g., `abc123...`) | From Step 4 above |

Example:
```
DIGITALOCEAN_ACCESS_TOKEN = dop_v1_1234567890abcdef...
DIGITALOCEAN_APP_ID = 12345678-1234-1234-1234-123456789abc
```

---

## Part 3: Custom Domain Setup (dunelegacy.com)

### Step 1: Add Domain to DigitalOcean App

1. Go to https://cloud.digitalocean.com/apps
2. Click your app → **Settings** tab
3. Scroll to **Domains** section
4. Click **"Add Domain"**
5. Enter: `dunelegacy.com`
6. DigitalOcean will show DNS records you need to add

### Step 2: Configure GoDaddy DNS

1. Log in to GoDaddy: https://dcc.godaddy.com/domains
2. Find `dunelegacy.com` → Click **"DNS"**
3. **Add these records** (DigitalOcean will show exact values):

**For root domain (dunelegacy.com):**
```
Type: A
Name: @
Value: <DigitalOcean IP address>
TTL: 600 seconds
```

**For www subdomain:**
```
Type: CNAME
Name: www
Value: <your-app>.ondigitalocean.app
TTL: 600 seconds
```

**Alternative (if A record doesn't work):**
```
Type: CNAME
Name: @
Value: <your-app>.ondigitalocean.app
TTL: 600 seconds
```

### Step 3: Wait for DNS Propagation

- DNS changes take 5-60 minutes to propagate
- Check status: `dig dunelegacy.com` or https://dnschecker.org
- DigitalOcean will auto-provision SSL certificate when DNS is ready

---

## Part 4: Testing Your Deployment

### Test Website
```bash
# Should show HTML content
curl https://dunelegacy.com
# Or visit in browser: https://dunelegacy.com
```

### Test Metaserver
```bash
# List servers (should return "0" initially)
curl https://dunelegacy.com/metaserver/metaserver.php?action=list

# Add a test server (replace YOUR_PORT with a number like 28747)
curl "https://dunelegacy.com/metaserver/metaserver.php?action=add&port=YOUR_PORT&name=TestServer&map=TestMap&numplayers=1&maxplayers=8&version=0.98.6"

# List again (should show your test server)
curl https://dunelegacy.com/metaserver/metaserver.php?action=list

# View in browser
open https://dunelegacy.com/metaserver/
```

---

## Part 5: Update Game to Use New Metaserver

After DigitalOcean is live, update the game's default metaserver URL:

**In the main game repo:**
```bash
cd /Users/stefanvanderwel/development/dune/dunelegacy
```

**Edit `include/Definitions.h`:**
```cpp
// Change from:
#define DEFAULT_METASERVER "http://dunelegacy.sourceforge.net/metaserver/metaserver.php"

// To:
#define DEFAULT_METASERVER "https://dunelegacy.com/metaserver/metaserver.php"
```

**Edit `config/Dune Legacy.ini`:**
```ini
# Change from:
MetaServer = http://dunelegacy.sourceforge.net/metaserver/metaserver.php

# To:
MetaServer = https://dunelegacy.com/metaserver/metaserver.php
```

**Commit and push:**
```bash
git add include/Definitions.h config/Dune\ Legacy.ini
git commit -m "Update metaserver URL to dunelegacy.com"
git push
```

---

## Part 6: Automated Deployment (GitHub Actions)

Once secrets are added, deployment is automatic:

1. Make changes to website or metaserver locally
2. Commit and push to `main` branch
3. GitHub Actions automatically deploys to DigitalOcean
4. Monitor deployment: https://github.com/svan058/dunelegacy.com/actions

**Manual deployment (if needed):**
- Go to https://github.com/svan058/dunelegacy.com/actions
- Click "Deploy to DigitalOcean" workflow
- Click **"Run workflow"** → **"Run workflow"**

---

## Monitoring and Logs

**App Platform Dashboard:**
- https://cloud.digitalocean.com/apps
- View logs, metrics, and deployment history

**View Logs via CLI:**
```bash
# Website logs
doctl apps logs $DIGITALOCEAN_APP_ID --type run --component website

# Metaserver logs
doctl apps logs $DIGITALOCEAN_APP_ID --type run --component metaserver
```

**Health Check:**
The metaserver has an automatic health check that pings `/metaserver/metaserver.php?action=list` every 30 seconds.

---

## Costs

**DigitalOcean App Platform Pricing (as of 2024):**
- Static Site (website): **$0** (included in free tier)
- Basic PHP Service (metaserver): **$5/month**
- Total: **~$5/month**

**Free Tier:**
- $200 credit for 60 days for new accounts
- Plenty of time to test before any charges

---

## Troubleshooting

### Metaserver shows "ERROR: Invalid action"
- Check URL format: `?action=list` (not `/list`)
- Verify PHP service is running in DigitalOcean dashboard

### DNS not resolving
- Wait 15-30 minutes for propagation
- Check with: `dig dunelegacy.com`
- Verify GoDaddy DNS records match DigitalOcean instructions

### "servers.dat" permission errors
- Check DigitalOcean logs: `doctl apps logs ...`
- The app automatically creates `/var/www/data/` directory with proper permissions in the Dockerfile
- DigitalOcean App Platform persistent disk is mounted at `/var/www/data`

### Scoreboard data is being reset on deployment
- Ensure the persistent disk is properly configured in `app.yaml` (lines 39-42)
- Verify DATA_DIR environment variable points to `/var/www/data` (not `/tmp`)
- Check disk is mounted: Look in DigitalOcean dashboard → App → Settings → Metaserver component
- Data files: `servers.dat` and `stats.json` should persist across deployments

### GitHub Actions deployment fails
- Verify secrets are set correctly in GitHub
- Check action logs: https://github.com/svan058/dunelegacy.com/actions
- Ensure `doctl` can authenticate

---

## Next Steps

1. ✅ Set up DigitalOcean App Platform
2. ✅ Configure GitHub Secrets
3. ✅ Point domain from GoDaddy
4. ✅ Test metaserver functionality
5. ✅ Update game to use new metaserver URL
6. 🎮 Release new game version with updated metaserver!

---

## Support Resources

- **DigitalOcean Docs:** https://docs.digitalocean.com/products/app-platform/
- **doctl Reference:** https://docs.digitalocean.com/reference/doctl/
- **GitHub Actions Docs:** https://docs.github.com/actions
- **GoDaddy DNS Help:** https://www.godaddy.com/help/manage-dns-records-680

