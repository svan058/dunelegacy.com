# 🚀 Quickstart Guide

**Goal:** Deploy the Dune Legacy website and metaserver in 5 minutes.

---

## Prerequisites

```bash
# Required tools
brew install doctl gh

# Authenticate
doctl auth init
gh auth login
```

---

## Deploy in 3 Steps

### 1️⃣ Create Metaserver Droplet (3 min)

```bash
cd deploy
./create-droplet.sh
```

**Output:** Droplet IP address (save this!)

---

### 2️⃣ Enable Auto-Deploy (2 min)

```bash
cd deploy
./setup-github-actions.sh
```

**Prompts:**
- Droplet IP: (from step 1)
- SSH key: Press `1` for default

**Result:** Code auto-deploys on every push! ✅

---

### 3️⃣ Update DNS (5 min wait time)

**GoDaddy DNS:**
1. Go to https://dcc.godaddy.com/domains
2. Find `dunelegacy.com` → DNS
3. Add A record:
   ```
   Type: A
   Name: metaserver
   Value: <YOUR_DROPLET_IP>
   TTL: 600
   ```

**Wait 5-15 minutes for DNS propagation.**

**Test:**
```bash
curl http://metaserver.dunelegacy.com/metaserver.php?action=list
```

---

## ✅ Done!

**Your setup:**
- ✅ Static website at https://dunelegacy.com (auto-deploys)
- ✅ Metaserver at https://metaserver.dunelegacy.com (auto-deploys)
- ✅ Persistent data storage (survives deployments)

---

## Daily Usage

**Update code:**
```bash
# 1. Make changes
vim metaserver/metaserver.php

# 2. Push
git commit -am "Update"
git push origin main

# 3. Auto-deploys in 20 seconds! ✅
```

**Monitor:**
- GitHub Actions: https://github.com/svan058/dunelegacy.com/actions
- Metaserver status: http://metaserver.dunelegacy.com/index.php

---

## Cost

- App Platform (website): $0-3/month
- Droplet (metaserver): $6/month
- **Total: ~$6-9/month**

---

## Help

- 📖 [Full deployment guide](deployment.md)
- 🏗️ [Architecture overview](architecture.md)
- 🔧 [Troubleshooting](troubleshooting.md)

