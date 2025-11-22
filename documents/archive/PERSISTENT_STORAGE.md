# Persistent Storage Configuration for Metaserver

## Problem
The scoreboard data (game statistics) was being reset on every deployment because data files were stored in ephemeral `/tmp` storage instead of persistent disk.

## Solution
Configure DigitalOcean persistent disk to preserve data across deployments.

## Current Configuration

### Files That Need to Persist
- `/var/www/data/servers.dat` - Active server list (expires after 60s, but needs to survive brief restarts)
- `/var/www/data/stats.json` - Game statistics (total games, recent games, popular maps)

### Persistent Disk Configuration (`app.yaml`)

```yaml
services:
  - name: metaserver
    envs:
      - key: DATA_DIR
        scope: RUN_AND_BUILD_TIME
        value: /var/www/data
    
    # Persistent disk mounted at /var/www/data
    disks:
      - name: metaserver-data
        path: /var/www/data
        size_gb: 1
```

### Dockerfile Setup

The Dockerfile creates `/var/www/data` with proper permissions:

```dockerfile
RUN mkdir -p /var/www/data && \
    chown -R www-data:www-data /var/www/data
```

### Metaserver PHP Configuration

`metaserver.php` reads the `DATA_DIR` environment variable:

```php
if (!defined('DATA_DIR')) {
    define('DATA_DIR', getenv('DATA_DIR') ?: __DIR__);
}
if (!defined('DATA_FILE')) {
    define('DATA_FILE', DATA_DIR . '/servers.dat');
}
if (!defined('STATS_FILE')) {
    define('STATS_FILE', DATA_DIR . '/stats.json');
}
```

## Verification Steps

### 1. Check DigitalOcean Dashboard

1. Go to https://cloud.digitalocean.com/apps
2. Click your app → **Components** → **metaserver**
3. Scroll to **Disks** section
4. Verify: `metaserver-data` disk is attached at `/var/www/data` (1 GB)

### 2. Check Environment Variables

```bash
# Get app ID
doctl apps list

# Check component configuration
doctl apps spec get <APP_ID>
```

Verify output shows:
```yaml
envs:
  - key: DATA_DIR
    value: /var/www/data
disks:
  - name: metaserver-data
    path: /var/www/data
    size_gb: 1
```

### 3. Test Data Persistence

```bash
# 1. Add a test game
curl "https://dunelegacy.com/metaserver/metaserver.php?action=add&port=28747&secret=test123&name=PersistenceTest&map=TestMap&numplayers=1&maxplayers=8&version=0.98.6"

# 2. Check stats
curl "https://dunelegacy.com/metaserver/"
# Note the "Total Games (All Time)" number

# 3. Trigger a deployment (push to main branch or manual deploy)

# 4. After deployment completes, check stats again
curl "https://dunelegacy.com/metaserver/"
# The "Total Games (All Time)" should be PRESERVED (not reset to 0)
```

### 4. View Logs to Verify DATA_DIR

```bash
# View metaserver logs
doctl apps logs <APP_ID> --type run --component metaserver

# Look for any warnings about data directory
```

If you see warnings like:
```
Warning: Data directory /tmp/metaserver is not writable
```

Then the persistent disk is **NOT** properly configured.

You should see the DATA_DIR being used as `/var/www/data`.

## Common Issues

### Issue: Disk Not Mounted
**Symptom:** Stats reset on every deployment
**Solution:** 
1. Check DigitalOcean dashboard for disk attachment
2. Re-deploy using the correct `app.yaml` with disk configuration
3. May need to recreate the app if disk wasn't configured initially

### Issue: Wrong DATA_DIR
**Symptom:** Files created in wrong location
**Solution:** 
1. Verify `DATA_DIR` environment variable is set to `/var/www/data`
2. Check it's scoped to `RUN_AND_BUILD_TIME`
3. Redeploy after fixing

### Issue: Permission Errors
**Symptom:** Cannot write to `/var/www/data`
**Solution:**
1. Verify Dockerfile creates directory with correct permissions
2. Apache runs as `www-data` user
3. Directory ownership should be `www-data:www-data`

## Migration from Ephemeral Storage

If you had data in `/tmp/metaserver` that you want to preserve:

**Unfortunately, ephemeral data is lost**. Once persistent storage is configured:
- New deployments will preserve data going forward
- Historical statistics before the fix are lost
- Game server registrations will rebuild automatically (they expire after 60s anyway)

## Monitoring

Check the metaserver status page regularly:
- https://dunelegacy.com/metaserver/

Key metrics that should **increase over time** (not reset):
- **Total Games (All Time)** - should only go up
- **Popular Maps** - should accumulate
- **Recent Games (Last 30 Days)** - should show history

If these reset to 0 after deployment, persistent storage is **NOT** working.

## Cost

Persistent disk: **$0.10/GB/month**
- 1 GB disk = **$0.10/month**
- Negligible compared to $5/month service cost

## Related Files

- `.digitalocean/app.yaml` - **Active config** (used by GitHub Actions)
- `deploy/app.yaml` - **Reference copy** (should match `.digitalocean/app.yaml`)
- `metaserver/Dockerfile` - Container setup
- `metaserver/metaserver.php` - Data persistence logic
- `.github/workflows/deploy.yml` - Deployment automation

