# Automated Droplet Setup for Metaserver

## 🚀 Quick Start (One Command!)

```bash
cd /Users/stefanvanderwel/development/dune/dunelegacy.com/deploy
./create-droplet.sh
```

That's it! The script will:
1. ✅ Create a new Droplet ($6/month)
2. ✅ Install Apache + PHP automatically
3. ✅ Clone and deploy your metaserver code
4. ✅ Configure everything
5. ✅ Test that it's working
6. ✅ Give you the IP address

**Time: ~3-4 minutes** (mostly waiting for droplet to install packages)

---

## What the Script Does

### Uses DigitalOcean Cloud-Init
Cloud-init is a system that runs scripts automatically when a VM first boots. Our script:

1. **Creates droplet** via `doctl` API
2. **Injects cloud-init script** that runs on first boot:
   - Updates system
   - Installs Apache, PHP, git
   - Clones your repo
   - Creates `/var/www/data` directory
   - Configures Apache
   - Starts services

3. **Waits** for everything to finish
4. **Tests** the metaserver is working
5. **Prints** next steps

---

## Prerequisites

You already have these:
- ✅ `doctl` installed
- ✅ `doctl` authenticated (`doctl auth init`)
- ✅ DigitalOcean account

---

## After Running the Script

The script outputs something like:

```
✅ Droplet created successfully!

📋 Details:
   ID: abc123...
   Name: dunelegacy-metaserver
   IP: 167.172.123.456

✅ Metaserver is working correctly!

📝 Next Steps:
1. Update DNS...
```

### Step 1: Update DNS (GoDaddy)

1. Go to https://dcc.godaddy.com/domains
2. Find `dunelegacy.com` → Click **DNS**
3. Add new A record:
   ```
   Type: A
   Name: metaserver
   Value: <YOUR_DROPLET_IP from script output>
   TTL: 600
   ```
4. Save

This creates: `metaserver.dunelegacy.com` → Your Droplet

### Step 2: Wait for DNS (5-15 minutes)

```bash
# Check if DNS has propagated
dig metaserver.dunelegacy.com

# Or use online tool
open https://dnschecker.org/#A/metaserver.dunelegacy.com
```

### Step 3: Test via Domain

```bash
# Should work once DNS propagates
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"

# View status page
open "http://metaserver.dunelegacy.com/index.php"
```

### Step 4: Update Game Client

In the main game repository:

```cpp
// dunelegacy/include/Definitions.h
#define DEFAULT_METASERVER "http://metaserver.dunelegacy.com/metaserver.php"
```

### Step 5: Clean Up App Platform (Optional)

Once the droplet is working, remove the metaserver from App Platform:

1. Edit `.digitalocean/app.yaml`
2. Remove the `metaserver` service section
3. Keep only the `website` static site
4. Commit and push

**Savings:** App Platform cost drops from $5/mo to ~$0-3/mo (static site only)

---

## SSH Access

If you need to check logs or debug:

```bash
ssh root@YOUR_DROPLET_IP

# Check Apache logs
tail -f /var/log/apache2/metaserver-error.log

# Check data files
ls -la /var/www/data/
cat /var/www/data/stats.json

# Check cloud-init logs (first boot only)
cat /var/log/cloud-init-output.log
```

---

## Future Deployments

When you update metaserver code:

```bash
ssh root@YOUR_DROPLET_IP "cd /var/www/html && git pull"
```

Or automate with GitHub Actions (see bottom).

---

## Troubleshooting

### Script fails with "doctl: command not found"
```bash
# Install doctl
brew install doctl
doctl auth init
```

### Script creates droplet but metaserver doesn't respond
Cloud-init might need more time. Check progress:
```bash
ssh root@YOUR_DROPLET_IP
tail -f /var/log/cloud-init-output.log
```

### Want to start over?
Delete the droplet and run script again:
```bash
doctl compute droplet delete dunelegacy-metaserver
./create-droplet.sh
```

### Check if metaserver is running
```bash
ssh root@YOUR_DROPLET_IP
systemctl status apache2
curl localhost/metaserver.php?action=list
```

---

## Cost

**Droplet:** $6/month
- 1 GB RAM
- 1 vCPU
- 25 GB SSD
- 1 TB transfer

**vs Current App Platform:** $5/month (but data doesn't persist!)

**Extra $1/month for working persistence = Worth it!** ✅

---

## Automated Deployments (Optional)

Add this to `.github/workflows/deploy-metaserver.yml`:

```yaml
name: Deploy Metaserver to Droplet

on:
  push:
    branches: [main]
    paths: ['metaserver/**']

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy via SSH
        env:
          DROPLET_IP: ${{ secrets.METASERVER_DROPLET_IP }}
          SSH_KEY: ${{ secrets.DROPLET_SSH_KEY }}
        run: |
          mkdir -p ~/.ssh
          echo "$SSH_KEY" > ~/.ssh/id_rsa
          chmod 600 ~/.ssh/id_rsa
          ssh -o StrictHostKeyChecking=no root@$DROPLET_IP \
            "cd /var/www/html && git pull origin main"
```

Add secrets to GitHub:
- `METASERVER_DROPLET_IP` = Your droplet IP
- `DROPLET_SSH_KEY` = Your SSH private key

Now every push to `main` auto-deploys! 🚀

---

## Security (Optional but Recommended)

The script sets up basic security (auto-updates), but for production consider:

```bash
# Set up firewall
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow 22/tcp
ufw enable

# Add SSL certificate (free via Let's Encrypt)
apt install certbot python3-certbot-apache
certbot --apache -d metaserver.dunelegacy.com
```

---

## Summary

**Before:** App Platform with broken persistence, manual setup
**After:** One command → working metaserver with persistent storage

```bash
./create-droplet.sh
# ☕ Wait 3 minutes
# 🎉 Done!
```

Simple! 🚀

