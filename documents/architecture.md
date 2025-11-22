# 🏗️ Architecture Overview

## System Diagram

```
┌─────────────────────────────────────────────────────┐
│  Internet Users                                      │
└────────────┬────────────────────────────────────────┘
             │
             ├─────────────────┬──────────────────────┐
             │                 │                      │
             ▼                 ▼                      ▼
    ┌────────────────┐  ┌─────────────┐   ┌──────────────┐
    │  dunelegacy    │  │  metaserver │   │ Game Clients │
    │  .com          │  │  .dunelegacy│   │ (Players)    │
    │                │  │  .com       │   │              │
    └────────┬───────┘  └──────┬──────┘   └──────┬───────┘
             │                 │                   │
             ▼                 ▼                   │
    ┌─────────────────┐  ┌──────────────────┐    │
    │ App Platform    │  │  Droplet (VM)    │    │
    │ (Static Site)   │  │  Ubuntu 24.04    │◀───┘
    │                 │  │                  │
    │ • HTML/CSS/JS   │  │ • Apache 2.4     │
    │ • CDN-backed    │  │ • PHP 8.3        │
    │ • Auto-deploy   │  │ • Metaserver.php │
    │                 │  │ • /var/www/data/ │
    │ $0-3/mo         │  │                  │
    └─────────────────┘  │ $6/mo            │
                         └──────────────────┘
```

---

## Components

### 1. Static Website (App Platform)

**URL:** https://dunelegacy.com, https://www.dunelegacy.com

**Purpose:**
- Game information & downloads
- News & updates
- Documentation links

**Tech Stack:**
- Static HTML/CSS/JavaScript
- No server-side processing
- CDN-backed for fast global access

**Deployment:**
- GitHub push → Auto-deploys via App Platform
- Source: `website/` directory
- Cost: $0-3/month (static site tier)

**Why App Platform:**
- Perfect for static content
- No persistent storage needed
- Built-in CDN
- Free SSL certificate

---

### 2. Metaserver (Droplet)

**URL:** http://metaserver.dunelegacy.com

**Purpose:**
- Directory of active multiplayer games
- Game servers register/update their status
- Clients query for available games

**Tech Stack:**
- Ubuntu 24.04 LTS
- Apache 2.4 + mod_php
- PHP 8.3
- Native filesystem (persistent!)

**File Structure:**
```
/var/www/html/          ← Code (updated by git)
├── metaserver.php      ← Main API
├── index.php           ← Status dashboard
└── download.php        ← Game downloads

/var/www/data/          ← Data (persistent forever)
├── servers.dat         ← Active server list
└── stats.json          ← Game statistics
```

**Deployment:**
- GitHub push → GitHub Actions → SSH → git pull
- Only code files updated
- Data files never touched
- Cost: $6/month (1 vCPU, 1GB RAM, 25GB SSD)

**Why Droplet:**
- Needs persistent filesystem
- App Platform doesn't support persistent local storage
- Simple, traditional PHP app
- Direct VM control

---

## Data Flow

### Website Traffic
```
User → dunelegacy.com
  → App Platform CDN
  → Serves static HTML/CSS/JS
  → Done
```

### Game Server Registration
```
Game Server → metaserver.dunelegacy.com/metaserver.php?action=add
  → Droplet (Apache + PHP)
  → metaserver.php processes request
  → Writes to /var/www/data/servers.dat
  → Returns "OK"
```

### Game Client Listing Servers
```
Game Client → metaserver.dunelegacy.com/metaserver.php?action=list
  → Droplet (Apache + PHP)
  → metaserver.php reads /var/www/data/servers.dat
  → Filters expired servers (>60s old)
  → Returns server list
```

---

## Deployment Architecture

### Continuous Deployment Pipeline

```
Developer → Git Push
     ↓
GitHub Repository
     ↓
     ├─────────────────────┬──────────────────────┐
     ▼                     ▼                      ▼
App Platform          GitHub Actions         Human Check
(website/)            (metaserver/)          (GitHub UI)
     ↓                     ↓                      
Auto-deploys          SSH to Droplet              
in ~2 min             git pull                    
     ↓                     ↓                      
  Website              Metaserver                 
  Updated              Updated                    
```

### GitHub Actions Workflow
```yaml
Trigger: Push to main with metaserver/** changes
Steps:
  1. Checkout code
  2. Setup SSH credentials
  3. SSH into droplet
  4. Run: cd /var/www/html && git pull
  5. Fix permissions
  6. Done (20 seconds)
```

---

## Persistence Strategy

### ❌ Old Approach (App Platform)
```
Container filesystem (ephemeral)
  → Deploy → Container recreated
  → Data lost ❌
```

### ✅ New Approach (Droplet)
```
Native filesystem (persistent)
  → Deploy → Only code updated (git pull)
  → Data preserved ✅
```

**Key Separation:**
- `/var/www/html/` = Code (git-managed, updated frequently)
- `/var/www/data/` = Data (never touched by deployments)

---

## Security

### SSL/HTTPS
- **Website:** ✅ Automatic (App Platform managed)
- **Metaserver:** ⚠️ HTTP only (can add Let's Encrypt if needed)

### Firewall
- **Website:** ✅ Managed by App Platform
- **Metaserver:** Default open (HTTP port 80, SSH port 22)

### Updates
- **Website:** ✅ Managed by App Platform
- **Metaserver:** ✅ Unattended-upgrades enabled (auto security patches)

### Backups
- **Website:** N/A (code in git, no data)
- **Metaserver:** Can enable DigitalOcean backups (+$1.20/mo)

---

## Scalability

### Current Scale
- **Website:** Unlimited (CDN-backed)
- **Metaserver:** Single droplet (~100 concurrent games)

### Future Growth
- **Website:** Already scalable
- **Metaserver:** 
  - Can upgrade droplet size
  - Can add load balancer + multiple droplets
  - Can migrate to PostgreSQL for better multi-server support

---

## Monitoring

### Health Checks
- **Website:** App Platform built-in
- **Metaserver:** None currently (can add UptimeRobot)

### Logs
- **Website:** App Platform dashboard
- **Metaserver:** `/var/log/apache2/metaserver-*.log`

### Status Pages
- **Website:** N/A
- **Metaserver:** http://metaserver.dunelegacy.com/index.php

---

## Cost Breakdown

| Component | Service | Monthly Cost |
|-----------|---------|--------------|
| Website | App Platform (static) | $0-3 |
| Metaserver | Droplet (1GB) | $6 |
| Domain | GoDaddy (existing) | $0* |
| GitHub | Free tier | $0 |
| **Total** | | **$6-9** |

*Assuming domain already owned

---

## Design Decisions

### Why Not All on App Platform?
- App Platform doesn't support persistent local storage
- Would require PostgreSQL ($15/mo extra)
- Overkill for simple flat-file storage needs

### Why Not All on Droplet?
- Website benefits from CDN (faster globally)
- App Platform handles SSL automatically
- Static sites are free/cheap on App Platform

### Why Not Database?
- Only 2 small files (servers.dat, stats.json)
- Low complexity
- Fast local file I/O
- Can migrate to DB later if needed for advanced features

### Why Flat Files for Metaserver?
- Simple, proven approach
- No database overhead
- PHP serialize() is fast
- Easy to debug (SSH + cat file)
- Sufficient for current scale

---

## Future Enhancements

### Potential Improvements
1. **SSL for metaserver** - Let's Encrypt (free)
2. **Monitoring** - UptimeRobot alerts
3. **Backups** - Automated droplet snapshots
4. **Metrics** - Better analytics on game stats
5. **Database migration** - If needing advanced queries/leaderboards

### Not Needed Now
- Load balancing (single droplet handles traffic fine)
- Caching layer (filesystem is fast enough)
- Complex deployment (current auto-deploy works great)

