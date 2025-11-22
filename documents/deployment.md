# 📦 Deployment Guide

Complete step-by-step deployment instructions for both website and metaserver.

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Initial Setup](#initial-setup)
- [Deploy Website](#deploy-website)
- [Deploy Metaserver](#deploy-metaserver)
- [Configure Auto-Deploy](#configure-auto-deploy)
- [DNS Configuration](#dns-configuration)
- [Verification](#verification)
- [Daily Operations](#daily-operations)

---

## Prerequisites

### Required Accounts
- ✅ GitHub account (repo owner)
- ✅ DigitalOcean account
- ✅ GoDaddy account (for dunelegacy.com domain)

### Required Tools

```bash
# Install doctl (DigitalOcean CLI)
brew install doctl

# Install gh (GitHub CLI)
brew install gh

# Authenticate
doctl auth init
gh auth login
```

### Verify Setup

```bash
# Test doctl
doctl account get

# Test gh
gh repo view svan058/dunelegacy.com
```

---

## Initial Setup

### 1. Clone Repository

```bash
cd ~/development
git clone https://github.com/svan058/dunelegacy.com.git
cd dunelegacy.com
```

### 2. Review Structure

```bash
ls -la
# website/     - Static site files
# metaserver/  - PHP metaserver
# deploy/      - Deployment scripts
# documents/   - This documentation
```

---

## Deploy Website

The website is already deployed on DigitalOcean App Platform (static site). It auto-deploys on push to `main` branch.

### Check Current Deployment

```bash
# List apps
doctl apps list

# Get app details
doctl apps get <APP_ID>
```

### Manual Redeploy (if needed)

```bash
# Trigger rebuild
doctl apps create-deployment <APP_ID>

# Or via GitHub Actions
gh workflow run "Deploy to DigitalOcean" -f component=website
```

### Configuration

Website deployment is controlled by `.digitalocean/app.yaml`:

```yaml
static_sites:
  - name: website
    source_dir: website
    github:
      repo: svan058/dunelegacy.com
      branch: main
      deploy_on_push: true
```

**Website deploys automatically on any push to `main` branch!**

---

## Deploy Metaserver

### Step 1: Create Droplet

```bash
cd deploy
./create-droplet.sh
```

**What it does:**
1. Creates Ubuntu 24.04 droplet ($6/month)
2. Installs Apache + PHP via cloud-init
3. Clones metaserver code from GitHub
4. Configures Apache with proper settings
5. Creates persistent data directory
6. Tests metaserver is responding

**Time:** ~3 minutes

**Output:**
```
✅ Droplet created successfully!
   ID: abc123...
   IP: 167.172.123.456

✅ Metaserver is working correctly!
```

**Save that IP address!**

### Step 2: Verify Droplet

```bash
# Test via IP
curl "http://167.172.123.456/metaserver.php?action=list"
# Should return: OK

# Test status page
open "http://167.172.123.456/index.php"
```

### Step 3: SSH Access (Optional)

```bash
ssh root@167.172.123.456

# Check Apache status
systemctl status apache2

# Check files
ls -la /var/www/html/
ls -la /var/www/data/

# Check logs
tail -f /var/log/apache2/metaserver-error.log
```

---

## Configure Auto-Deploy

Enable automatic deployments via GitHub Actions.

### Step 1: Run Setup Script

```bash
cd deploy
./setup-github-actions.sh
```

**Prompts:**
1. **Droplet IP:** Enter the IP from droplet creation
2. **SSH Key:** Press `1` for default (~/.ssh/id_rsa)

**What it does:**
1. Tests SSH connection to droplet
2. Adds `METASERVER_DROPLET_IP` secret to GitHub
3. Adds `DROPLET_SSH_KEY` secret to GitHub
4. Enables auto-deployment workflow

### Step 2: Verify Secrets

```bash
# List secrets
gh secret list

# Should show:
# METASERVER_DROPLET_IP
# DROPLET_SSH_KEY
```

### Step 3: Test Auto-Deploy

```bash
# Make a small change
echo "// Test auto-deploy" >> metaserver/metaserver.php

# Commit and push
git add metaserver/metaserver.php
git commit -m "Test auto-deploy"
git push origin main

# Watch deployment
gh run watch
```

**Result:** Code deploys to droplet in ~20 seconds!

---

## DNS Configuration

Point your domain to the droplet.

### GoDaddy Configuration

1. Go to https://dcc.godaddy.com/domains
2. Find `dunelegacy.com` → Click **DNS**
3. Add A record:

```
Type: A
Name: metaserver
Value: <YOUR_DROPLET_IP>
TTL: 600 seconds
```

4. Click **Save**

### DNS Propagation

Wait 5-15 minutes for DNS to propagate globally.

**Check progress:**
```bash
# Check DNS
dig metaserver.dunelegacy.com

# Should show:
# metaserver.dunelegacy.com. 600 IN A 167.172.123.456
```

**Online checker:**
https://dnschecker.org/#A/metaserver.dunelegacy.com

---

## Verification

### Test Website

```bash
# Main site
curl -I https://dunelegacy.com
# Should return: 200 OK

# View in browser
open https://dunelegacy.com
```

### Test Metaserver

```bash
# List servers (via domain)
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"
# Returns: OK\n

# Add test server
curl "http://metaserver.dunelegacy.com/metaserver.php?action=add&port=28747&secret=test123&name=TestServer&map=TestMap&numplayers=1&maxplayers=8&version=0.98.6"
# Returns: OK\n

# List again (should show test server)
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"

# View status page
open "http://metaserver.dunelegacy.com/index.php"
```

### Test Auto-Deploy

```bash
# Make a change
echo "# Test change" >> metaserver/index.php

# Push
git commit -am "Test deployment"
git push origin main

# Monitor
gh run watch

# Verify change is live (after ~20 seconds)
curl "http://metaserver.dunelegacy.com/index.php" | grep "Test change"
```

✅ **All systems operational!**

---

## Daily Operations

### Updating Website

```bash
# 1. Edit files
vim website/index.html

# 2. Commit and push
git add website/
git commit -m "Update website content"
git push origin main

# 3. Auto-deploys via App Platform (~2 min)
# Monitor: https://cloud.digitalocean.com/apps
```

### Updating Metaserver

```bash
# 1. Edit files
vim metaserver/metaserver.php

# 2. Commit and push
git add metaserver/
git commit -m "Update metaserver logic"
git push origin main

# 3. Auto-deploys via GitHub Actions (~20 sec)
# Monitor: https://github.com/svan058/dunelegacy.com/actions
```

### Checking Logs

**Website logs:**
```bash
doctl apps logs <APP_ID> --type run --component website
```

**Metaserver logs:**
```bash
ssh root@metaserver.dunelegacy.com
tail -f /var/log/apache2/metaserver-error.log
```

### Checking Data Files

```bash
ssh root@metaserver.dunelegacy.com

# View current servers
cat /var/www/data/servers.dat | php -r 'print_r(unserialize(file_get_contents("php://stdin")));'

# View statistics
cat /var/www/data/stats.json | jq .
```

### Manual Deployment (if needed)

**Metaserver:**
```bash
ssh root@metaserver.dunelegacy.com
cd /var/www/html
git pull origin main
```

**Website:**
```bash
doctl apps create-deployment <APP_ID>
```

---

## Rollback Procedures

### Rollback Metaserver

```bash
ssh root@metaserver.dunelegacy.com
cd /var/www/html

# View recent commits
git log --oneline -10

# Rollback to specific commit
git reset --hard <COMMIT_HASH>

# Or rollback by 1 commit
git reset --hard HEAD~1
```

### Rollback Website

Website rollbacks happen through GitHub:

```bash
# Revert last commit
git revert HEAD
git push origin main

# Or force push previous version (careful!)
git reset --hard HEAD~1
git push --force origin main
```

---

## Disaster Recovery

### Metaserver Droplet Failure

**Backup data (if possible):**
```bash
ssh root@OLD_DROPLET_IP
tar -czf /tmp/metaserver-data.tar.gz /var/www/data/
scp root@OLD_DROPLET_IP:/tmp/metaserver-data.tar.gz ./
```

**Recreate droplet:**
```bash
cd deploy
./create-droplet.sh
# Get new IP

# Restore data
scp metaserver-data.tar.gz root@NEW_DROPLET_IP:/tmp/
ssh root@NEW_DROPLET_IP
cd /var/www
tar -xzf /tmp/metaserver-data.tar.gz
chown -R www-data:www-data /var/www/data
```

**Update DNS and GitHub secrets:**
```bash
# Update DNS in GoDaddy to new IP
# Update GitHub secret:
gh secret set METASERVER_DROPLET_IP --body "NEW_IP_ADDRESS"
```

### Enable Automatic Backups (Recommended)

```bash
# Enable weekly backups on droplet
doctl compute droplet-action enable-backups <DROPLET_ID>

# Cost: +20% (~$1.20/month)
# Benefit: Weekly snapshots of entire droplet
```

---

## Security Hardening (Optional)

### Add SSL Certificate

```bash
ssh root@metaserver.dunelegacy.com

# Install certbot
apt install -y certbot python3-certbot-apache

# Get certificate
certbot --apache -d metaserver.dunelegacy.com

# Auto-renewal is configured automatically
```

### Configure Firewall

```bash
ssh root@metaserver.dunelegacy.com

# Set up UFW firewall
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw allow 22/tcp   # SSH
ufw enable
```

### Restrict SSH Access

```bash
# Disable password authentication (key-only)
vim /etc/ssh/sshd_config
# Set: PasswordAuthentication no
systemctl restart sshd
```

---

## Monitoring (Optional)

### Add Uptime Monitoring

Use a service like:
- **UptimeRobot** (free) - https://uptimerobot.com
- **Pingdom** (paid) - https://www.pingdom.com

**Monitor:**
- http://metaserver.dunelegacy.com/metaserver.php?action=list
- https://dunelegacy.com

### GitHub Actions Notifications

Slack/Discord webhooks for deployment notifications (configure in GitHub repo settings).

---

## Cost Summary

| Component | Monthly Cost |
|-----------|--------------|
| App Platform (website) | $0-3 |
| Droplet (metaserver) | $6 |
| Backups (optional) | $1.20 |
| SSL Certificate | $0 (Let's Encrypt) |
| Domain (GoDaddy) | Existing |
| **Total** | **$6-10.20** |

---

## Support & Troubleshooting

See [troubleshooting.md](troubleshooting.md) for common issues and solutions.

**Need help?**
- Check GitHub Actions logs: https://github.com/svan058/dunelegacy.com/actions
- Check droplet logs: `ssh root@metaserver && tail -f /var/log/apache2/metaserver-error.log`
- Review architecture: [architecture.md](architecture.md)

