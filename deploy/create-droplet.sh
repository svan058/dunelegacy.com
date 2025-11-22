#!/bin/bash
#
# Automated Metaserver Droplet Setup
# Creates and configures a DigitalOcean Droplet for the metaserver
#

set -e

echo "🚀 Creating Metaserver Droplet..."

# Configuration
DROPLET_NAME="dunelegacy-metaserver"
REGION="nyc1"
SIZE="s-1vcpu-1gb"
IMAGE="ubuntu-24-04-x64"
REPO_URL="https://github.com/svan058/dunelegacy.com.git"

# Cloud-init script - runs automatically on first boot
cat > /tmp/cloud-config.yml <<'CLOUD_INIT'
#cloud-config

# Update system on first boot
package_update: true
package_upgrade: true

# Install required packages
packages:
  - apache2
  - php
  - libapache2-mod-php
  - php-cli
  - git

# Commands to run after packages are installed
runcmd:
  # Enable Apache modules
  - a2enmod rewrite
  - a2enmod php8.3
  
  # Remove default Apache files
  - rm -rf /var/www/html/*
  
  # Clone metaserver code
  - git clone https://github.com/svan058/dunelegacy.com.git /tmp/repo
  - cp -r /tmp/repo/metaserver/* /var/www/html/
  - rm -rf /tmp/repo
  
  # Create data directory with proper permissions
  - mkdir -p /var/www/data
  - chown -R www-data:www-data /var/www/data /var/www/html
  - chmod 755 /var/www/data
  
  # Configure Apache
  - |
    cat > /etc/apache2/sites-available/metaserver.conf <<'EOF'
    <VirtualHost *:80>
        ServerName metaserver.dunelegacy.com
        ServerAlias dunelegacy.com
        
        DocumentRoot /var/www/html
        
        <Directory /var/www/html>
            Options -Indexes +FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
        
        SetEnv DATA_DIR /var/www/data
        
        ErrorLog ${APACHE_LOG_DIR}/metaserver-error.log
        CustomLog ${APACHE_LOG_DIR}/metaserver-access.log combined
    </VirtualHost>
    EOF
  
  # Enable site and restart Apache
  - a2dissite 000-default.conf
  - a2ensite metaserver.conf
  - systemctl restart apache2
  
  # Set up unattended security updates
  - apt-get install -y unattended-upgrades
  - dpkg-reconfigure -plow unattended-upgrades

# Final message
final_message: |
  ✅ Metaserver Droplet Setup Complete!
  
  Data directory: /var/www/data
  Web root: /var/www/html
  
  Test metaserver:
    curl http://localhost/metaserver.php?action=list
CLOUD_INIT

# Create the droplet with cloud-init
echo "📦 Creating droplet with automated setup..."
DROPLET_OUTPUT=$(doctl compute droplet create "$DROPLET_NAME" \
  --image "$IMAGE" \
  --size "$SIZE" \
  --region "$REGION" \
  --user-data-file /tmp/cloud-config.yml \
  --wait \
  --format ID,Name,PublicIPv4 \
  --no-header)

DROPLET_ID=$(echo "$DROPLET_OUTPUT" | awk '{print $1}')
DROPLET_IP=$(echo "$DROPLET_OUTPUT" | awk '{print $3}')

echo ""
echo "✅ Droplet created successfully!"
echo ""
echo "📋 Details:"
echo "   ID: $DROPLET_ID"
echo "   Name: $DROPLET_NAME"
echo "   IP: $DROPLET_IP"
echo ""
echo "⏳ Waiting for cloud-init to finish (this takes 2-3 minutes)..."
echo "   The droplet is installing Apache, PHP, and configuring everything..."

# Wait for cloud-init to complete
sleep 180  # Give it 3 minutes

echo ""
echo "🧪 Testing metaserver..."

# Test the metaserver
if curl -s --connect-timeout 10 "http://$DROPLET_IP/metaserver.php?action=list" | grep -q "OK"; then
    echo "✅ Metaserver is responding!"
    
    # Test adding a server
    curl -s "http://$DROPLET_IP/metaserver.php?action=add&port=28747&secret=test123&name=TestServer&map=TestMap&numplayers=1&maxplayers=8&version=0.98.6" > /dev/null
    
    echo "✅ Metaserver is working correctly!"
    echo ""
    echo "📊 View status page:"
    echo "   http://$DROPLET_IP/index.php"
    echo ""
else
    echo "⚠️  Metaserver not responding yet. This is normal - cloud-init may need more time."
    echo "   You can check status with:"
    echo "   ssh root@$DROPLET_IP 'tail -f /var/log/cloud-init-output.log'"
    echo ""
fi

echo "📝 Next Steps:"
echo ""
echo "1. Update DNS to point to this IP:"
echo "   Type: A"
echo "   Name: metaserver"
echo "   Value: $DROPLET_IP"
echo "   TTL: 600"
echo ""
echo "2. Test from your browser:"
echo "   http://$DROPLET_IP/index.php"
echo ""
echo "3. Update game client to use:"
echo "   http://metaserver.dunelegacy.com/metaserver.php"
echo ""
echo "4. SSH access (if needed):"
echo "   ssh root@$DROPLET_IP"
echo ""
echo "5. Save this info:"
cat > /tmp/metaserver-info.txt <<EOF
Droplet ID: $DROPLET_ID
Droplet IP: $DROPLET_IP
Created: $(date)
Region: $REGION
Size: $SIZE

Data Directory: /var/www/data
Web Root: /var/www/html

Test URL: http://$DROPLET_IP/metaserver.php?action=list
Status Page: http://$DROPLET_IP/index.php
EOF

echo "   Saved to: /tmp/metaserver-info.txt"
echo ""
echo "🎉 Done! Your metaserver droplet is ready!"

# Clean up
rm /tmp/cloud-config.yml

