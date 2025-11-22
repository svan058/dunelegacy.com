# ✨ Simple Droplet Deployment Guide

## 🎯 The Simple Way

**Setup once, then just push code to GitHub!**

---

## One-Time Setup (10 minutes total)

### Step 1: Create the Droplet (3 minutes)

```bash
cd /Users/stefanvanderwel/development/dune/dunelegacy.com/deploy
./create-droplet.sh
```

**What happens:**
- ✅ Creates Ubuntu VM
- ✅ Installs Apache + PHP
- ✅ Deploys your metaserver code
- ✅ Configures everything
- ✅ Gives you the IP address

**Output:**
```
✅ Droplet created successfully!
   IP: 167.172.123.456

✅ Metaserver is working correctly!
```

Save that IP address! ⬆️

---

### Step 2: Set Up Auto-Deploy (2 minutes)

```bash
# Install GitHub CLI (if not already installed)
brew install gh

# Authenticate
gh auth login

# Configure auto-deployment
./setup-github-actions.sh
```

**What it asks:**
1. Droplet IP address (from Step 1)
2. Which SSH key to use (usually just press 1 for default)

**What it does:**
- ✅ Tests SSH connection to your droplet
- ✅ Adds secrets to GitHub
- ✅ Enables automatic deployments

**Done!** Auto-deploy is now configured! 🎉

---

### Step 3: Update DNS (5 minutes)

**In GoDaddy:**
1. Go to https://dcc.godaddy.com/domains
2. Find `dunelegacy.com` → **DNS**
3. Add A record:
   ```
   Type: A
   Name: metaserver
   Value: <YOUR_DROPLET_IP>
   TTL: 600
   ```
4. Save

**Wait 5-15 minutes** for DNS to propagate.

**Test:**
```bash
dig metaserver.dunelegacy.com
curl http://metaserver.dunelegacy.com/metaserver.php?action=list
```

---

## 🚀 Daily Usage (Super Simple!)

### Updating Metaserver Code

**Old way (manual):**
```bash
ssh root@droplet
cd /var/www/html
git pull
```

**New way (automatic):**
```bash
# 1. Make your changes
vim metaserver/metaserver.php

# 2. Commit and push
git add metaserver/
git commit -m "Update metaserver logic"
git push origin main

# 3. That's it! 
# GitHub Actions automatically deploys to droplet!
```

**Monitor deployment:**
- Go to https://github.com/svan058/dunelegacy.com/actions
- Watch it deploy in real-time (~10-20 seconds)
- ✅ Green checkmark = Live!

---

## 📊 How It Works

```
┌──────────────┐      ┌─────────────┐      ┌──────────────┐
│  You         │      │  GitHub     │      │  Droplet     │
│              │      │  Actions    │      │              │
├──────────────┤      ├─────────────┤      ├──────────────┤
│ 1. Edit code │ ───▶ │ 2. Detects  │ ───▶ │ 3. Runs      │
│ 2. git push  │      │    push     │      │    git pull  │
│              │      │ 3. Runs SSH │      │ 4. Updates   │
│              │      │    deploy   │      │    live!     │
└──────────────┘      └─────────────┘      └──────────────┘
```

**What GitHub Actions does:**
1. Sees you pushed code to `main` branch
2. Checks if `metaserver/**` files changed
3. SSHs into your droplet
4. Runs `git pull origin main`
5. Updates file permissions
6. Done! New code is live!

**Time:** 10-20 seconds from push to live 🚀

---

## 🎮 Update Game Client

Once DNS is working, update the game to use the new metaserver:

```cpp
// In dunelegacy/include/Definitions.h
#define DEFAULT_METASERVER "http://metaserver.dunelegacy.com/metaserver.php"
```

---

## 🔍 Monitoring & Debugging

### View Deployment Logs
https://github.com/svan058/dunelegacy.com/actions

### SSH into Droplet (if needed)
```bash
ssh root@YOUR_DROPLET_IP

# Check Apache logs
tail -f /var/log/apache2/metaserver-error.log

# Check data files
ls -la /var/www/data/
cat /var/www/data/stats.json

# Check running version
cd /var/www/html
git log -1
```

### Test Metaserver
```bash
# Via IP
curl http://YOUR_DROPLET_IP/metaserver.php?action=list

# Via domain (after DNS)
curl http://metaserver.dunelegacy.com/metaserver.php?action=list

# View status page
open http://metaserver.dunelegacy.com/index.php
```

---

## 💰 Cost

**Droplet:** $6/month  
**GitHub Actions:** Free (way under limits)

**Total:** $6/month with automatic deployments! ✅

---

## 🆘 Troubleshooting

### "METASERVER_DROPLET_IP not found"
You need to run `./setup-github-actions.sh` first to add the secrets.

### "Permission denied (publickey)"
Your SSH key isn't configured correctly. Run:
```bash
ssh-copy-id root@YOUR_DROPLET_IP
# Or manually add your public key to droplet
```

### GitHub Actions stuck/failing
Check the logs: https://github.com/svan058/dunelegacy.com/actions
Most common issue: SSH key not properly added to secrets.

### DNS not resolving
Wait 15-30 minutes. Check with:
```bash
dig metaserver.dunelegacy.com
```

---

## ✨ Summary

**Setup (one-time):**
1. `./create-droplet.sh` → Creates droplet
2. `./setup-github-actions.sh` → Enables auto-deploy
3. Update DNS in GoDaddy

**Daily usage:**
```bash
# Make changes
vim metaserver/metaserver.php

# Deploy (automatic!)
git commit -am "My changes"
git push origin main

# ✅ Live in 20 seconds!
```

**That's it!** No manual SSH, no complicated deployment. Just push to GitHub! 🚀

