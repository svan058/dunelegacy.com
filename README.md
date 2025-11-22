# Dune Legacy Website & Metaserver

Official website and multiplayer metaserver for Dune Legacy.

## 🚀 Quick Deploy

```bash
cd deploy && ./create-droplet.sh      # Creates metaserver VM
./setup-github-actions.sh              # Enables auto-deploy
# Then update DNS in GoDaddy with the droplet IP
```

**Full guide:** [DEPLOYMENT.md](DEPLOYMENT.md)

---

## 📡 Live URLs

- Website: https://dunelegacy.com
- Metaserver API: http://metaserver.dunelegacy.com/metaserver.php
- Status Page: http://metaserver.dunelegacy.com/index.php

---

## 🔄 Daily Usage

```bash
# Edit code
vim metaserver/metaserver.php

# Deploy (automatic!)
git push origin main
```

Both website and metaserver auto-deploy on push.

---

## 📂 Structure

```
dunelegacy.com/
├── website/          # Static site (dunelegacy.com)
├── metaserver/       # PHP API (metaserver.dunelegacy.com)
├── deploy/           # Deployment scripts
└── .github/          # Auto-deploy workflows
```

---

## 📖 Documentation

**Everything you need:** [DEPLOYMENT.md](DEPLOYMENT.md)

Covers:
- Quick setup (5 min)
- Detailed deployment
- Troubleshooting
- Architecture
- Daily usage

---

## 💰 Cost

$6-9/month (App Platform + Droplet)

---

## 📝 License

GPL-2.0+ (same as main game)

