# Dune Legacy Website & Metaserver Documentation

**Quick Links for AI Agents:**
- 🚀 [**QUICKSTART.md**](QUICKSTART.md) - Deploy everything in 5 minutes
- 🏗️ [architecture.md](architecture.md) - System overview
- 📦 [deployment.md](deployment.md) - Detailed deployment guide
- 🔧 [troubleshooting.md](troubleshooting.md) - Common issues & fixes

---

## 📁 Repository Structure

```
dunelegacy.com/
├── website/          # Static website (dunelegacy.com)
├── metaserver/       # PHP metaserver for multiplayer
├── deploy/           # Deployment scripts & configs
├── documents/        # Documentation (you are here)
└── .github/          # GitHub Actions workflows
```

---

## 🎯 What This Repo Does

1. **Website** - Static HTML/CSS/JS site at https://dunelegacy.com
2. **Metaserver** - PHP server that lists active multiplayer games

**Deployed to:**
- Website: DigitalOcean App Platform (static site)
- Metaserver: DigitalOcean Droplet (Ubuntu VM)

---

## 🤖 For AI Agents

### Common Tasks

**Deploy metaserver for the first time:**
```bash
cd deploy && ./create-droplet.sh
```

**Update metaserver code:**
```bash
git push origin main  # Auto-deploys via GitHub Actions
```

**Troubleshoot deployment:**
See [troubleshooting.md](troubleshooting.md)

### Key Facts
- Metaserver data persists at `/var/www/data/` on Droplet
- Code deployments only update `/var/www/html/` (data safe!)
- Static website auto-deploys via App Platform on push
- Metaserver auto-deploys via GitHub Actions on push

---

## 📚 Documentation Index

| Document | Purpose | Audience |
|----------|---------|----------|
| [QUICKSTART.md](QUICKSTART.md) | Deploy everything fast | Humans & AI |
| [architecture.md](architecture.md) | System design overview | Technical |
| [deployment.md](deployment.md) | Step-by-step deployment | Detailed guide |
| [troubleshooting.md](troubleshooting.md) | Fix common issues | Support |

---

## 🔄 Updates

**Last Updated:** 2025-11-22  
**Current Version:** Droplet-based metaserver with auto-deploy

**Recent Changes:**
- Migrated metaserver from App Platform to Droplet for persistent storage
- Enabled GitHub Actions auto-deployment
- Consolidated documentation into documents/ folder

