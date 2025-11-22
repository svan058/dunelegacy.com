# 🔧 Troubleshooting Guide

Common issues and their solutions.

---

## Quick Diagnostics

```bash
# Check website
curl -I https://dunelegacy.com

# Check metaserver
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"

# Check GitHub Actions
gh run list --limit 5

# Check droplet status
ssh root@metaserver.dunelegacy.com "systemctl status apache2"
```

---

## Website Issues

### Website Not Loading

**Symptom:** dunelegacy.com returns error or doesn't load

**Diagnosis:**
```bash
# Check App Platform status
doctl apps list
doctl apps get <APP_ID>

# Check recent deployments
doctl apps list-deployments <APP_ID>
```

**Solutions:**
1. Check DigitalOcean App Platform dashboard for deployment errors
2. Review GitHub Actions logs for deployment failures
3. Verify DNS is pointing to App Platform
4. Try manual redeployment: `doctl apps create-deployment <APP_ID>`

---

### CSS/JS Not Loading

**Symptom:** Website loads but styling is broken

**Solutions:**
1. Check browser console for 404 errors
2. Verify file paths in HTML are correct (relative paths)
3. Clear CDN cache in App Platform dashboard
4. Hard refresh browser (Cmd+Shift+R)

---

## Metaserver Issues

### Metaserver Not Responding

**Symptom:** curl to metaserver.php returns connection error

**Diagnosis:**
```bash
# Test by IP
curl "http://<DROPLET_IP>/metaserver.php?action=list"

# Test by domain
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"

# SSH and check Apache
ssh root@<DROPLET_IP>
systemctl status apache2
tail -f /var/log/apache2/metaserver-error.log
```

**Solutions:**

**If IP works but domain doesn't:**
- DNS issue - check `dig metaserver.dunelegacy.com`
- Wait for DNS propagation (up to 24 hours max)

**If neither work:**
```bash
# Restart Apache
ssh root@<DROPLET_IP>
systemctl restart apache2

# Check Apache is listening
netstat -tlnp | grep :80

# Check firewall
ufw status
```

---

### Metaserver Returns "ERROR: Invalid action"

**Symptom:** Metaserver responds but returns error

**Diagnosis:**
```bash
# Check exact URL
curl -v "http://metaserver.dunelegacy.com/metaserver.php?action=list"
```

**Solutions:**
1. Verify URL has `?action=list` (not `/list`)
2. Check PHP is processing files:
   ```bash
   ssh root@<DROPLET_IP>
   php -v  # Should show PHP 8.3
   cat /var/www/html/metaserver.php | head -20  # Verify file exists
   ```
3. Check Apache PHP module:
   ```bash
   apache2ctl -M | grep php
   ```

---

### Data Not Persisting

**Symptom:** Game statistics reset after deployment

**Diagnosis:**
```bash
ssh root@<DROPLET_IP>

# Check data directory exists
ls -la /var/www/data/

# Check permissions
stat /var/www/data/servers.dat
stat /var/www/data/stats.json

# Check DATA_DIR environment variable
grep DATA_DIR /etc/apache2/sites-available/metaserver.conf
```

**Solutions:**

**If directory doesn't exist:**
```bash
mkdir -p /var/www/data
chown www-data:www-data /var/www/data
chmod 755 /var/www/data
systemctl restart apache2
```

**If permissions wrong:**
```bash
chown -R www-data:www-data /var/www/data
chmod 755 /var/www/data
chmod 644 /var/www/data/*.{dat,json}
```

**If DATA_DIR not set:**
```bash
vim /etc/apache2/sites-available/metaserver.conf
# Add: SetEnv DATA_DIR /var/www/data
systemctl restart apache2
```

---

## Deployment Issues

### GitHub Actions Failing

**Symptom:** Push to main doesn't deploy

**Diagnosis:**
```bash
# Check recent runs
gh run list --limit 10

# View failed run logs
gh run view <RUN_ID>
```

**Common Failures:**

**"Permission denied (publickey)"**
- SSH key secret is wrong or missing
- Solution:
  ```bash
  cd deploy
  ./setup-github-actions.sh  # Re-run setup
  ```

**"METASERVER_DROPLET_IP not found"**
- Secret not configured
- Solution:
  ```bash
  gh secret set METASERVER_DROPLET_IP --body "<YOUR_IP>"
  ```

**"Host key verification failed"**
- SSH host key not trusted
- Solution: Workflow includes `ssh-keyscan`, re-run deployment

---

### Auto-Deploy Not Triggering

**Symptom:** Push to main doesn't trigger workflow

**Diagnosis:**
```bash
# Check workflow file exists
ls -la .github/workflows/

# Check GitHub Actions are enabled
gh api repos/svan058/dunelegacy.com/actions/permissions
```

**Solutions:**
1. Verify changes are in `metaserver/**` directory (workflow only triggers on metaserver changes)
2. Push to `main` branch (not a different branch)
3. Check workflow file syntax:
   ```bash
   cat .github/workflows/deploy-metaserver-droplet.yml
   ```
4. Manually trigger:
   ```bash
   gh workflow run "Deploy Metaserver to Droplet"
   ```

---

### Manual Deploy Doesn't Work

**Symptom:** `git pull` on droplet fails

**Diagnosis:**
```bash
ssh root@<DROPLET_IP>
cd /var/www/html
git status
git pull origin main
```

**Solutions:**

**"Permission denied" or ownership issues:**
```bash
chown -R root:root /var/www/html
git reset --hard origin/main
git pull origin main
```

**"Could not resolve host 'github.com'"**
```bash
# DNS issue
ping github.com
# Check internet connectivity
curl -I https://github.com
```

**Local changes conflict:**
```bash
git stash
git pull origin main
```

---

## DNS Issues

### Domain Not Resolving

**Symptom:** Can't access via domain name

**Diagnosis:**
```bash
# Check DNS resolution
dig metaserver.dunelegacy.com
dig dunelegacy.com

# Check from multiple locations
# https://dnschecker.org
```

**Solutions:**
1. Wait 5-15 minutes for propagation (up to 24 hours max)
2. Verify GoDaddy DNS settings:
   - `dunelegacy.com` → App Platform IP
   - `metaserver.dunelegacy.com` → Droplet IP
3. Check TTL settings (600 seconds = 10 min cache)
4. Flush local DNS cache:
   ```bash
   sudo dscacheutil -flushcache
   sudo killall -HUP mDNSResponder
   ```

---

### Wrong IP in DNS

**Symptom:** Domain points to old/wrong IP

**Solution:**
1. Update A record in GoDaddy
2. Wait for TTL to expire (10 minutes with TTL=600)
3. Verify: `dig metaserver.dunelegacy.com`

---

## SSH Issues

### Can't SSH to Droplet

**Symptom:** `ssh root@<IP>` fails

**Diagnosis:**
```bash
# Test connection
ssh -v root@<DROPLET_IP>

# Check if droplet is running
doctl compute droplet list
```

**Solutions:**

**"Connection refused":**
- Droplet might be off
- Solution: `doctl compute droplet-action reboot <DROPLET_ID>`

**"Permission denied (publickey)":**
- SSH key not configured
- Solution:
  ```bash
  # Add your public key via DigitalOcean dashboard
  # Or reset root password
  doctl compute droplet-action password-reset <DROPLET_ID>
  ```

**"Connection timed out":**
- Network/firewall issue
- Solution: Check DigitalOcean firewall rules

---

## Data Corruption

### servers.dat Corrupted

**Symptom:** PHP unserialize errors in logs

**Solution:**
```bash
ssh root@<DROPLET_IP>

# Backup corrupted file
cp /var/www/data/servers.dat /var/www/data/servers.dat.bak

# Reset servers file
rm /var/www/data/servers.dat

# Metaserver will recreate on next request
curl "http://localhost/metaserver.php?action=list"
```

**Prevention:** Enable droplet backups ($1.20/mo)

---

### stats.json Corrupted

**Symptom:** Status page shows errors or no stats

**Solution:**
```bash
ssh root@<DROPLET_IP>

# Backup
cp /var/www/data/stats.json /var/www/data/stats.json.bak

# Reset
echo '{"total_games":0,"recent_games":[],"popular_maps":{}}' > /var/www/data/stats.json
chown www-data:www-data /var/www/data/stats.json

# Test
curl http://localhost/index.php
```

---

## Performance Issues

### Slow Response Times

**Diagnosis:**
```bash
# Test response time
time curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"

# Check droplet resources
ssh root@<DROPLET_IP>
top
df -h
free -m
```

**Solutions:**

**High CPU/RAM:**
- Upgrade droplet size: `doctl compute droplet-action resize <DROPLET_ID> --size s-2vcpu-2gb`

**Disk full:**
```bash
# Clean up logs
cd /var/log
du -sh *
truncate -s 0 apache2/*.log
```

**Too many expired servers:**
- Increase SERVER_TIMEOUT in metaserver.php
- Or reduce timeout for faster cleanup

---

## Cloud-Init Issues

### Droplet Created But Not Configured

**Symptom:** Droplet exists but Apache/PHP not installed

**Diagnosis:**
```bash
ssh root@<DROPLET_IP>

# Check cloud-init status
cloud-init status

# Check cloud-init logs
cat /var/log/cloud-init-output.log
tail -100 /var/log/cloud-init.log
```

**Solutions:**

**Cloud-init still running:**
- Wait 5-10 more minutes
- Watch progress: `tail -f /var/log/cloud-init-output.log`

**Cloud-init failed:**
- Review error in logs
- Manually install:
  ```bash
  apt update
  apt install -y apache2 php libapache2-mod-php git
  # Then follow deployment.md manual steps
  ```

**Start over:**
```bash
# Delete and recreate
doctl compute droplet delete <DROPLET_ID>
cd deploy && ./create-droplet.sh
```

---

## Emergency Procedures

### Complete Droplet Failure

1. **Create new droplet:**
   ```bash
   cd deploy
   ./create-droplet.sh
   ```

2. **Update DNS** (in GoDaddy) to new IP

3. **Update GitHub secret:**
   ```bash
   gh secret set METASERVER_DROPLET_IP --body "<NEW_IP>"
   ```

4. **Data Recovery:**
   - If old droplet accessible, backup `/var/www/data/`
   - If backups enabled, restore from snapshot
   - Otherwise, statistics reset (servers rebuild automatically)

---

### Website Down

1. **Check App Platform status:** https://status.digitalocean.com
2. **Check deployment:** https://cloud.digitalocean.com/apps
3. **Rollback if needed:**
   ```bash
   git revert HEAD
   git push origin main
   ```

---

## Getting Help

### Useful Commands

```bash
# Check all systems
curl -I https://dunelegacy.com
curl "http://metaserver.dunelegacy.com/metaserver.php?action=list"
gh run list --limit 5
doctl compute droplet list

# View logs
ssh root@metaserver "tail -100 /var/log/apache2/metaserver-error.log"
gh run view --log

# System status
doctl apps list
doctl compute droplet list
```

### Log Locations

- **Apache errors:** `/var/log/apache2/metaserver-error.log`
- **Apache access:** `/var/log/apache2/metaserver-access.log`
- **Cloud-init:** `/var/log/cloud-init-output.log`
- **System:** `/var/log/syslog`
- **GitHub Actions:** https://github.com/svan058/dunelegacy.com/actions

### Support Resources

- **DigitalOcean Docs:** https://docs.digitalocean.com
- **DigitalOcean Support:** https://cloud.digitalocean.com/support
- **GitHub Actions Docs:** https://docs.github.com/actions

---

## Prevention Checklist

- [ ] Enable automated backups on droplet ($1.20/mo)
- [ ] Set up uptime monitoring (UptimeRobot)
- [ ] Configure firewall rules
- [ ] Add SSL certificate (Let's Encrypt)
- [ ] Test disaster recovery procedures
- [ ] Document custom configurations
- [ ] Keep GitHub secrets up to date

