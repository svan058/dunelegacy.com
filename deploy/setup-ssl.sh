#!/bin/bash
#
# Enable SSL/HTTPS for dunelegacy.com
# REQUIRED for game clients to connect!
#

set -e

echo "🔒 Setting up SSL certificates for dunelegacy.com"
echo ""

# Get droplet IP
echo "Enter your droplet IP address:"
read DROPLET_IP

if [[ ! $DROPLET_IP =~ ^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "❌ Invalid IP address format"
    exit 1
fi

echo ""
echo "Enter your email address (for Let's Encrypt notifications):"
read EMAIL

if [[ ! $EMAIL =~ ^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$ ]]; then
    echo "❌ Invalid email format"
    exit 1
fi

echo ""
echo "⚠️  IMPORTANT: DNS must be pointing to this droplet first!"
echo ""
echo "Checking DNS..."

# Check if DNS is pointing to droplet
RESOLVED_IP=$(dig +short dunelegacy.com | tail -1)

if [[ "$RESOLVED_IP" != "$DROPLET_IP" ]]; then
    echo "❌ DNS is not pointing to droplet yet!"
    echo "   Current DNS: $RESOLVED_IP"
    echo "   Droplet IP: $DROPLET_IP"
    echo ""
    echo "Update DNS in GoDaddy first, then wait 10-15 minutes."
    exit 1
fi

echo "✅ DNS is configured correctly!"
echo ""
echo "🔐 Installing SSL certificate..."
echo ""

# SSH and install certificate
ssh root@$DROPLET_IP <<ENDSSH
certbot --apache \
  -d dunelegacy.com \
  -d www.dunelegacy.com \
  --non-interactive \
  --agree-tos \
  -m $EMAIL \
  --redirect

echo ""
echo "✅ SSL certificate installed!"
echo "🔄 Apache reloaded with HTTPS enabled"
ENDSSH

echo ""
echo "🎉 HTTPS is now enabled!"
echo ""
echo "Test it:"
echo "  curl https://dunelegacy.com"
echo "  curl https://dunelegacy.com/metaserver/metaserver.php?action=list"
echo ""
echo "🎮 Game clients can now connect!"
echo ""

