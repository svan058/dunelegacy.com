# Dune Legacy Website & Metaserver - Complete Guide

Everything you need to deploy and maintain the website and metaserver.

---

## 🚀 Quick Deploy (5 Minutes)

```bash
# 1. Create metaserver droplet
cd deploy && ./create-droplet.sh

# 2. Enable auto-deploy
./setup-github-actions.sh

# 3. Update DNS in GoDaddy with the droplet IP
```

**Done!** Website auto-deploys via App Platform. Metaserver auto-deploys via GitHub Actions.

---

## What This Repo Is

- **Website:** Static HTML at https://dunelegacy.com (DigitalOcean App Platform)
- **Metaserver:** PHP API at http://metaserver.dunelegacy.com (DigitalOcean Droplet)
- **Auto-deploy:** Both deploy automatically on `git push origin main`

**Cost:** $6-9/month total

---

## Architecture

```
Website (App Platform)           Metaserver (Droplet)
$0-3/month                       $6/month
Static HTML/CSS/JS               Ubuntu + Apache + PHP
Auto-deploys (~2 min)            Auto-deploys (~20 sec)
       ↓                                ↓
dunelegacy.com              metaserver.dunelegacy.com
```

**Key fact:** Metaserver uses persistent filesystem at `/var/www/data/` for game statistics.

---

## Prerequisites

```bash
brew install doctl gh
doctl auth init
gh auth login
```

---

## Detailed Setup

### Step 1: Create Droplet (3 min)

```bash
cd deploy
./create-droplet.sh
```

**What it does:**
- Creates Ubuntu 24.04 VM
- Installs Apache + PHP
- Deploys metaserver code
- Creates persistent data directory

**Save the IP address it gives you!**

### Step 2: Enable Auto-Deploy (2 min)

```bash
./setup-github-actions.sh
```

**Prompts:**
- Droplet IP (from step 1)
- SSH key (press 1 for default)

**What it does:**
- Adds secrets to GitHub
- Enables auto-deployment workflow

### Step 3: Configure DNS (5 min wait)

**GoDaddy:**
1. Go to https://dcc.godaddy.com/domains
2. `dunelegacy.com` → DNS
3. Add A record:
   ```
   Type: A
   Name: metaserver
   Value: <YOUR_DROPLET_IP>
   TTL: 600
   ```

Wait 5-15 minutes for DNS propagation.

---

## Daily Usage

**Update website or metaserver:**
```bash
# Edit files
vim metaserver/metaserver.php

# Deploy
git commit -am "Update"
git push origin main

# ✅ Auto-deploys!
```

**Monitor deployments:**
- Website: https://cloud.digitalocean.com/apps
- Metaserver: https://github.com/svan058/dunelegacy.com/actions

---

## Data Persistence

**IMPORTANT:** Metaserver has persistent data!

```
/var/www/html/     ← Code (updated by git pull) ✅ Safe to update
/var/www/data/     ← Data (NEVER touched) ⚠️ PERSISTENT
  ├── servers.dat  ← Active game servers
  └── stats.json   ← Game statistics
```

**Deployments only update code, data stays forever.**

---

## Troubleshooting

### Metaserver not responding

```bash
# Test
curl http://metaserver.dunelegacy.com/metaserver.php?action=list

# SSH and check
ssh root@<DROPLET_IP>
systemctl status apache2
tail -f /var/log/apache2/metaserver-error.log
```

### Auto-deploy failing

```bash
# Check GitHub Actions
gh run list

# Re-run setup
cd deploy && ./setup-github-actions.sh
```

### DNS not resolving

```bash
dig metaserver.dunelegacy.com
# Wait 15 minutes for propagation
```

### Data not persisting

```bash
ssh root@<DROPLET_IP>
ls -la /var/www/data/
chown -R www-data:www-data /var/www/data
```

---

## File Structure

```
dunelegacy.com/
├── website/          # Static site files
├── metaserver/       # PHP metaserver
│   ├── metaserver.php   # Main API
│   ├── index.php        # Status page
│   └── download.php     # Downloads
├── deploy/           # Deployment scripts
│   ├── create-droplet.sh
│   └── setup-github-actions.sh
└── .github/workflows/   # Auto-deploy configs
```

---

## Advanced

### Add SSL Certificate

```bash
ssh root@<DROPLET_IP>
apt install -y certbot python3-certbot-apache
certbot --apache -d metaserver.dunelegacy.com
```

### Enable Backups

```bash
doctl compute droplet-action enable-backups <DROPLET_ID>
# +$1.20/month for weekly snapshots
```

### Manual Deploy

```bash
# Website
doctl apps create-deployment <APP_ID>

# Metaserver
ssh root@<DROPLET_IP> "cd /var/www/html && git pull"
```

---

## Safety Rules

✅ **Safe:** Update code via git push (data preserved)  
✅ **Safe:** Restart Apache (data preserved)  
✅ **Safe:** Reboot droplet (data preserved)  
❌ **NEVER:** Delete `/var/www/data/` (loses all game statistics!)  
❌ **NEVER:** Delete the droplet (loses everything!)  
❌ **NEVER:** Run `create-droplet.sh` twice (creates duplicate)

---

## Disaster Recovery

### Droplet failure

```bash
# Create new droplet
cd deploy && ./create-droplet.sh

# Update DNS to new IP
# Update GitHub secret
gh secret set METASERVER_DROPLET_IP --body "<NEW_IP>"

# Data lost unless you had backups enabled
```

**Recommendation:** Enable backups for $1.20/month

---

## Cost Breakdown

- App Platform (website): $0-3/month
- Droplet (metaserver): $6/month
- Backups (optional): $1.20/month
- **Total: $6-10/month**

---

## Support

**Quick checks:**
```bash
curl -I https://dunelegacy.com
curl http://metaserver.dunelegacy.com/metaserver.php?action=list
gh run list
doctl compute droplet list
```

**Need help?** Check GitHub Actions logs or SSH into droplet and view Apache logs.

---

**Last Updated:** 2025-11-22

