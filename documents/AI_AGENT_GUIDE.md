# 🤖 AI Agent Guide

**Quick reference for AI assistants helping with this repository.**

---

## 🎯 What This Repo Is

- **Purpose:** Website + multiplayer metaserver for Dune Legacy game
- **URL:** https://dunelegacy.com (website) + http://metaserver.dunelegacy.com (API)
- **Tech:** Static HTML + PHP metaserver
- **Hosting:** DigitalOcean (App Platform + Droplet)

---

## ⚡ Quick Commands

### Deploy metaserver (first time)
```bash
cd deploy && ./create-droplet.sh
./setup-github-actions.sh
```

### Deploy updates (automatic)
```bash
git push origin main  # Auto-deploys via GitHub Actions
```

### Check status
```bash
curl https://dunelegacy.com  # Website
curl http://metaserver.dunelegacy.com/metaserver.php?action=list  # Metaserver
```

### Troubleshoot
```bash
ssh root@metaserver.dunelegacy.com
tail -f /var/log/apache2/metaserver-error.log
```

---

## 📁 Repository Structure

```
├── website/              # Static site files (HTML/CSS/JS)
├── metaserver/           # PHP metaserver code
│   ├── metaserver.php   # Main API (list/add/update/remove servers)
│   ├── index.php        # Status dashboard
│   └── download.php     # Game downloads
├── deploy/               # Deployment automation
│   ├── create-droplet.sh         # Creates droplet
│   ├── setup-github-actions.sh   # Enables auto-deploy
│   └── app.yaml                  # App Platform config
├── .github/workflows/    # CI/CD
│   ├── deploy.yml                # Website auto-deploy
│   └── deploy-metaserver-droplet.yml  # Metaserver auto-deploy
└── documents/            # Documentation (you are here)
    ├── QUICKSTART.md          # 5-minute deploy guide
    ├── architecture.md        # System design
    ├── deployment.md          # Detailed steps
    └── troubleshooting.md     # Problem solving
```

---

## 🚀 Deployment Architecture

### Website
- **Location:** DigitalOcean App Platform (static site)
- **Deploys:** Automatically on push to `main` (website/** changes)
- **Time:** ~2 minutes
- **Cost:** $0-3/month

### Metaserver
- **Location:** DigitalOcean Droplet (Ubuntu VM)
- **Deploys:** Automatically via GitHub Actions on push to `main` (metaserver/** changes)
- **Method:** SSH + `git pull origin main`
- **Time:** ~20 seconds
- **Cost:** $6/month

---

## 💾 Data Persistence

**Critical:** Metaserver has persistent data that must NOT be lost!

```
Droplet filesystem:
/var/www/html/        ← Code (updated by git pull) ✅ Safe to change
/var/www/data/        ← Data (NEVER touched by deploys) ⚠️ PERSISTENT
  ├── servers.dat     ← Active game servers
  └── stats.json      ← Game statistics (total games, etc.)
```

**Important:**
- Code deployments only run `git pull` in `/var/www/html/`
- Never delete or recreate `/var/www/data/`
- Never delete the droplet (would lose all data!)
- Data persists across code deployments, Apache restarts, reboots

---

## 🔑 Key Configuration

### Secrets (GitHub)
- `METASERVER_DROPLET_IP` - Droplet IP address
- `DROPLET_SSH_KEY` - SSH private key for deployment
- `DIGITALOCEAN_ACCESS_TOKEN` - (if using App Platform API)
- `DIGITALOCEAN_APP_ID` - (if using App Platform API)

### Environment Variables (Droplet)
- `DATA_DIR=/var/www/data` - Set in Apache config

### DNS (GoDaddy)
- `dunelegacy.com` → App Platform
- `metaserver.dunelegacy.com` → Droplet IP

---

## 🛠️ Common Tasks

### User wants to deploy for first time
→ Point to: [QUICKSTART.md](QUICKSTART.md)

### User wants to update code
→ Tell them: `git push origin main` (auto-deploys)

### User reports metaserver not responding
→ Point to: [troubleshooting.md](troubleshooting.md)
→ Quick check:
```bash
curl http://metaserver.dunelegacy.com/metaserver.php?action=list
ssh root@<IP> "systemctl status apache2"
```

### User asks about persistence/data loss
→ Explain: Code in `/var/www/html/` (updated), data in `/var/www/data/` (never touched)
→ Point to: [architecture.md](architecture.md) persistence section

### User wants to add SSL
→ Point to: [deployment.md](deployment.md) security hardening section

### User asks about cost
→ Answer: $6-9/month total
→ Point to: [architecture.md](architecture.md) cost section

---

## 🚨 What NOT to Do

❌ **Never** delete `/var/www/data/` on droplet
❌ **Never** delete the droplet (unless intentional, loses all data)
❌ **Never** run `./create-droplet.sh` more than once (creates duplicate droplet)
❌ **Never** modify data files directly (let PHP code handle them)
❌ **Never** suggest recreating droplet to "fix" issues (loses data!)

---

## ✅ Safe Operations

✅ Update code via `git push` (data safe)
✅ Restart Apache (data safe)
✅ Reboot droplet (data safe)
✅ Run `git pull` on droplet (data safe)
✅ Update PHP files (data safe)

---

## 📊 Monitoring

### Check website
```bash
curl -I https://dunelegacy.com
```

### Check metaserver API
```bash
curl http://metaserver.dunelegacy.com/metaserver.php?action=list
```

### Check metaserver status page
```bash
open http://metaserver.dunelegacy.com/index.php
```

### Check GitHub Actions
```bash
gh run list --limit 5
```

### Check droplet
```bash
ssh root@metaserver.dunelegacy.com
systemctl status apache2
df -h  # disk usage
free -m  # memory
```

---

## 🔍 Debug Checklist

1. **Is website loading?** → Check App Platform dashboard
2. **Is metaserver responding?** → `curl metaserver.php?action=list`
3. **Are deployments working?** → Check GitHub Actions
4. **Is Apache running?** → `ssh` and `systemctl status apache2`
5. **Are data files present?** → `ls -la /var/www/data/`
6. **Are permissions correct?** → Files should be owned by `www-data:www-data`

---

## 📖 Documentation Hierarchy

For different user needs:

| User Type | Start Here |
|-----------|-----------|
| **First-time deployer** | [QUICKSTART.md](QUICKSTART.md) |
| **Wants details** | [deployment.md](deployment.md) |
| **Has a problem** | [troubleshooting.md](troubleshooting.md) |
| **Wants to understand** | [architecture.md](architecture.md) |
| **AI assistant** | This file! |

---

## 💡 Response Templates

### User: "How do I deploy?"
```
Quick deployment:
1. cd deploy && ./create-droplet.sh
2. ./setup-github-actions.sh
3. Update DNS in GoDaddy

Full guide: documents/QUICKSTART.md
```

### User: "Metaserver not working"
```
Let's diagnose:
1. curl http://metaserver.dunelegacy.com/metaserver.php?action=list
2. ssh root@<IP> "systemctl status apache2"

See: documents/troubleshooting.md
```

### User: "Will my data be lost?"
```
No! Data is persistent:
- Code: /var/www/html/ (updated by git)
- Data: /var/www/data/ (never touched)

Deployments only update code, data stays forever.

Details: documents/architecture.md (persistence section)
```

---

## 🎓 Learning Path

1. Read [QUICKSTART.md](QUICKSTART.md) - Understand basic deployment
2. Read [architecture.md](architecture.md) - Understand system design
3. Skim [troubleshooting.md](troubleshooting.md) - Know where to look when issues arise
4. Reference [deployment.md](deployment.md) - For detailed procedures

---

## 🤝 Contributing

When helping users:
1. ✅ Be clear about data persistence
2. ✅ Link to relevant docs
3. ✅ Provide working commands
4. ✅ Explain trade-offs
5. ✅ Check their understanding

When updating docs:
1. Keep AI_AGENT_GUIDE.md updated
2. Keep documentation DRY (Don't Repeat Yourself)
3. Link between docs rather than duplicating
4. Test all commands before documenting
5. Update architecture.md for design changes

---

**Last Updated:** 2025-11-22

