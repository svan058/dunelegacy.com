# Dune Legacy Website & Metaserver

This repository contains the website and multiplayer metaserver for Dune Legacy.

## Structure

```
dunelegacy.com/
├── website/          # Public website (HTML/CSS/JS)
├── metaserver/       # PHP multiplayer metaserver
└── deploy/           # DigitalOcean deployment configs
```

## Website

Static website for https://dunelegacy.com

- Game information
- Downloads
- Manual & FAQ
- News

## Metaserver

PHP-based multiplayer game server directory.

**API Endpoints:**
- `GET /metaserver/metaserver.php?action=list` - List active game servers
- `GET /metaserver/metaserver.php?action=add&...` - Register new server
- `GET /metaserver/metaserver.php?action=update&...` - Update server status
- `GET /metaserver/metaserver.php?action=remove&...` - Unregister server

**Server Data:**
- Stored in PostgreSQL database
- Servers expire after 60 seconds without updates
- Game clients poll every 30 seconds

## Deployment

### Local Development

```bash
# Website
cd website
python3 -m http.server 8000

# Metaserver (requires PHP 8+)
cd metaserver
php -S localhost:8080
```

### Production (DigitalOcean)

Deployed via DigitalOcean App Platform:
- Website: Static site
- Metaserver: PHP service
- Database: PostgreSQL (managed)

See `deploy/` for configuration.

## Domain Configuration

**GoDaddy DNS:**
```
dunelegacy.com              A      → DigitalOcean IP
www.dunelegacy.com          CNAME  → dunelegacy.com
```

**DigitalOcean Routes:**
```
dunelegacy.com              → website/
dunelegacy.com/metaserver/  → metaserver/
```

## Development

```bash
# Clone repo
git clone https://github.com/dunelegacy/dunelegacy.com.git
cd dunelegacy.com

# Test website locally
cd website && open index.html

# Test metaserver locally
cd metaserver && php -S localhost:8080
curl "http://localhost:8080/metaserver.php?action=list"
```

## License

GPL-2.0+ (same as main game)

