# Scoreboard Data Persistence - Issue Analysis & Fix

## 🔍 Problem Identified

The scoreboard data (game statistics like total games, recent games, popular maps) was being **reset on every deployment** because:

1. **Good News**: Your `.digitalocean/app.yaml` (used by GitHub Actions) already has persistent storage configured! ✅
2. **Potential Issue**: The persistent disk might not be actually attached to the running app

## 📋 Current Configuration Status

### ✅ What's Already Correct

Your `.digitalocean/app.yaml` has proper persistent storage configured:

```yaml
services:
  - name: metaserver
    envs:
      - key: DATA_DIR
        value: /var/www/data  # Points to persistent disk
    
    disks:
      - name: metaserver-data
        path: /var/www/data
        size_gb: 1  # Persistent 1GB disk
```

### 🔧 What I Fixed

1. **Updated `deploy/app.yaml`** - Was outdated and showed ephemeral `/tmp` storage. Now matches `.digitalocean/app.yaml`
2. **Added troubleshooting documentation** - See `deploy/PERSISTENT_STORAGE.md`
3. **Updated `DIGITALOCEAN_SETUP.md`** - Added persistent storage troubleshooting section

## ✅ Verification Steps

### Step 1: Check DigitalOcean Dashboard

**This is the most important step!**

1. Go to https://cloud.digitalocean.com/apps
2. Click your app → **Settings** tab
3. Scroll to **Metaserver** component
4. Look for **Disks** section

**Expected:** You should see `metaserver-data` (1 GB) mounted at `/var/www/data`

**If you DON'T see the disk:**
- The persistent storage was never attached (even though it's in the YAML)
- The app needs to be redeployed to apply the disk configuration

### Step 2: Check Current Deployment

```bash
# Get your app ID
doctl apps list

# View current app spec
doctl apps spec get <YOUR_APP_ID>
```

Look for this section in the output:
```yaml
disks:
  - name: metaserver-data
    path: /var/www/data
    size_gb: 1
```

**If you don't see this**, the disk isn't configured on the live app.

### Step 3: Test Data Persistence

```bash
# 1. Check current stats
curl "https://dunelegacy.com/metaserver/" | grep "Total Games"
# Note the number

# 2. Add a test game
curl "https://dunelegacy.com/metaserver/metaserver.php?action=add&port=28747&secret=test123&name=TestGame&map=TestMap&numplayers=1&maxplayers=8&version=0.98.6"

# 3. Check stats again (should increment by 1)
curl "https://dunelegacy.com/metaserver/" | grep "Total Games"

# 4. Trigger a redeployment
# Option A: Push a commit to main branch
# Option B: Manual trigger via GitHub Actions

# 5. After deployment, check stats AGAIN
curl "https://dunelegacy.com/metaserver/" | grep "Total Games"
# ❌ If it resets to 0 or previous value: Disk NOT working
# ✅ If it keeps the new count: Disk IS working!
```

## 🔧 How to Fix (If Disk Not Attached)

### Option 1: Force Reapply the App Spec (Recommended)

```bash
# This will update the app with the current spec
cd /Users/stefanvanderwel/development/dune/dunelegacy.com
doctl apps update $DIGITALOCEAN_APP_ID --spec .digitalocean/app.yaml
```

This should attach the persistent disk if it wasn't already.

### Option 2: Manual Configuration via DigitalOcean UI

1. Go to https://cloud.digitalocean.com/apps
2. Click your app → **Settings** → **metaserver** component
3. Scroll to **Disks** section
4. Click **Add Disk**
5. Configure:
   - **Name:** `metaserver-data`
   - **Mount path:** `/var/www/data`
   - **Size:** 1 GB
6. Click **Save** → App will redeploy automatically

### Option 3: Check if App Was Created Before Disk Config Was Added

If the app was initially created WITHOUT the disk configuration, adding it to the YAML might not automatically attach it. You may need to:

```bash
# Delete and recreate (WARNING: This will reset current data!)
doctl apps delete $DIGITALOCEAN_APP_ID

# Recreate with disk configuration
doctl apps create --spec .digitalocean/app.yaml
```

**⚠️ This will reset ALL current statistics!** Only do this if you're okay losing existing data.

## 📊 What Data Gets Persisted

Once the persistent disk is working:

### Files in `/var/www/data/`:
- **`servers.dat`** - Serialized PHP array of active game servers
  - Format: PHP serialize() format
  - Contains: IP, port, secret, name, map, players, version, lastUpdate
  - Auto-expires servers after 60 seconds

- **`stats.json`** - JSON file with game statistics
  - `total_games` - All-time game count (should only increase!)
  - `recent_games[]` - Last 30 days of games (array of game objects)
  - `popular_maps{}` - Map name → play count

### Expected Behavior:
- ✅ **Total Games (All Time)** should NEVER decrease
- ✅ **Recent Games** should persist across deployments
- ✅ **Popular Maps** counts should accumulate
- ✅ **Active Servers** will naturally expire/refresh (60s timeout)

## 🎯 Quick Health Check

Run this one-liner to check if data is persisting:

```bash
echo "Before: $(curl -s https://dunelegacy.com/metaserver/ | grep -oP 'Total Games.*?(\d+)' | tail -1)" && \
curl -s "https://dunelegacy.com/metaserver/metaserver.php?action=add&port=28747&secret=test$(date +%s)&name=Test&map=Test&numplayers=1&maxplayers=8&version=0.98.6" && \
sleep 2 && \
echo "After: $(curl -s https://dunelegacy.com/metaserver/ | grep -oP 'Total Games.*?(\d+)' | tail -1)"
```

The "Total Games" count should increment by 1.

## 📈 Monitoring

Visit https://dunelegacy.com/metaserver/ regularly to verify:

1. **Total Games (All Time)** - Should steadily increase
2. **Games (Last 30 Days)** - Should show recent activity
3. **Popular Maps** - Should show most-played maps

If any of these reset to 0 after a deployment, the persistent disk is **still not working**.

## 💰 Cost Impact

- **Persistent Disk (1 GB):** $0.10/month
- **Total app cost:** ~$5.10/month (was $5.00/month)
- **Benefit:** Permanent game statistics! 📊

## 📚 Additional Documentation

- **Full details:** See `deploy/PERSISTENT_STORAGE.md`
- **Troubleshooting:** See `DIGITALOCEAN_SETUP.md` (updated with new section)

## 🎬 Next Steps

1. ✅ Code changes complete (YAML files synced)
2. ⏳ **YOU NEED TO:** Verify disk is attached in DigitalOcean dashboard
3. ⏳ **YOU NEED TO:** Test data persistence after next deployment
4. ⏳ **YOU NEED TO:** Commit and push these changes

## 🚀 Deployment

After verifying the disk configuration:

```bash
cd /Users/stefanvanderwel/development/dune/dunelegacy.com

# Review changes
git status
git diff

# Commit the fixes
git add deploy/app.yaml DIGITALOCEAN_SETUP.md deploy/PERSISTENT_STORAGE.md SCOREBOARD_FIX.md
git commit -m "Fix: Configure persistent storage for metaserver scoreboard data

- Updated deploy/app.yaml to match .digitalocean/app.yaml
- Added persistent disk configuration (1GB at /var/www/data)
- Set DATA_DIR environment variable
- Added troubleshooting documentation
- Fixes issue where game statistics were reset on every deployment"

# Push to trigger deployment
git push origin main
```

## ❓ Questions to Answer

1. **Can you see the disk in DigitalOcean dashboard?**
   - Yes → Great! Just need to test persistence
   - No → Need to reapply the app spec or manually add the disk

2. **After next deployment, does "Total Games" persist?**
   - Yes → ✅ Problem solved!
   - No → Need to troubleshoot disk mounting

Let me know what you find in the DigitalOcean dashboard! 🔍

