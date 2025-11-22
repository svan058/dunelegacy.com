# Dune Legacy Website & Metaserver

Official website and multiplayer metaserver for Dune Legacy.

## 🚀 Quick Start

**For detailed instructions, see [documents/](documents/)**

### Deploy Everything

```bash
# 1. Create metaserver droplet (3 min)
cd deploy && ./create-droplet.sh

# 2. Enable auto-deploy (2 min)
./setup-github-actions.sh

# 3. Update DNS in GoDaddy with droplet IP
```

**Full guide:** [documents/QUICKSTART.md](documents/QUICKSTART.md)

---

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| [**QUICKSTART**](documents/QUICKSTART.md) | Deploy in 5 minutes |
| [Architecture](documents/architecture.md) | System design & tech stack |
| [Deployment](documents/deployment.md) | Step-by-step deployment |
| [Troubleshooting](documents/troubleshooting.md) | Fix common issues |

---

## 🏗️ Structure

```
dunelegacy.com/
├── website/          # Static website (dunelegacy.com)
├── metaserver/       # PHP metaserver (metaserver.dunelegacy.com)
├── deploy/           # Deployment scripts
├── documents/        # 📖 Documentation
└── .github/          # CI/CD workflows
```

---

## 🌐 Live URLs

- **Website:** https://dunelegacy.com
- **Metaserver:** http://metaserver.dunelegacy.com
- **Status:** http://metaserver.dunelegacy.com/index.php

---

## 🛠️ Tech Stack

**Website:**
- Static HTML/CSS/JavaScript
- DigitalOcean App Platform (CDN-backed)

**Metaserver:**
- PHP 8.3 + Apache 2.4
- Ubuntu 24.04 on DigitalOcean Droplet
- Flat file storage (servers.dat, stats.json)

---

## 🔄 Deployment

**Automatic deployment on push to `main` branch:**
- Website → App Platform (2 min)
- Metaserver → Droplet via GitHub Actions (20 sec)

**Make changes:**
```bash
# Edit code
vim metaserver/metaserver.php

# Deploy
git commit -am "Update"
git push origin main

# ✅ Auto-deploys!
```

---

## 📡 Metaserver API

**Endpoints:**
- `GET /metaserver.php?action=list` - List active games
- `GET /metaserver.php?action=add&...` - Register game server
- `GET /metaserver.php?action=update&...` - Update server status
- `GET /metaserver.php?action=remove&...` - Unregister server

**Details:** See [documents/architecture.md](documents/architecture.md)

---

## 💻 Local Development

```bash
# Website
cd website && python3 -m http.server 8000
open http://localhost:8000

# Metaserver
cd metaserver && php -S localhost:8080
curl "http://localhost:8080/metaserver.php?action=list"
```

---

## 📝 License

GPL-2.0+ (same as main game)

---

**Need help?** See [documents/troubleshooting.md](documents/troubleshooting.md)

