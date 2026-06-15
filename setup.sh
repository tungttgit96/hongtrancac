#!/bin/bash
# ============================================
# Hồng Trần Các - WordPress Setup Script
# ============================================
# Usage: bash setup.sh [domain]
# Example: bash setup.sh hongtrancac.test
#
# Prerequisites:
#   - Herd installed and running
#   - MySQL 8+ available
#   - WP-CLI installed (comes with Herd)
#   - PHP 8.3+ (comes with Herd)

set -e

SITE_DOMAIN="${1:-hongtrancac.test}"
SITE_DIR="$HOME/Herd/$SITE_DOMAIN"
DB_NAME="hongtrancac"
DB_USER="root"
DB_PASS=""
ADMIN_USER="admin"
ADMIN_PASS="admin123"
ADMIN_EMAIL="admin@hongtrancac.com"
SITE_TITLE="Hồng Trần Các"

echo "====================================="
echo "  Hồng Trần Các - WordPress Setup"
echo "====================================="
echo ""
echo "Site: $SITE_DOMAIN"
echo "Dir:  $SITE_DIR"
echo ""

# Step 1: Create site directory
if [ ! -d "$SITE_DIR" ]; then
    echo "[1/8] Creating site directory..."
    mkdir -p "$SITE_DIR"
else
    echo "[1/8] Site directory exists: $SITE_DIR"
fi

# Step 2: Download WordPress
if [ ! -f "$SITE_DIR/wp-config.php" ]; then
    echo "[2/8] Downloading WordPress..."
    cd "$SITE_DIR"
    if ! wp core download --locale=vi --path="$SITE_DIR" 2>/dev/null; then
        curl -O https://wordpress.org/latest-vi.zip
        unzip -o latest-vi.zip -d "$SITE_DIR/.."
        rm latest-vi.zip
    fi
else
    echo "[2/8] WordPress already installed"
fi

# Step 3: Create database
echo "[3/8] Creating database..."
mysql -u"$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || echo "Database may already exist (or try creating it manually)"

# Step 4: Create wp-config.php
if [ ! -f "$SITE_DIR/wp-config.php" ]; then
    echo "[4/8] Creating wp-config.php..."
    cd "$SITE_DIR"
    wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" ${DB_PASS:+--dbpass="$DB_PASS"} --dbhost=127.0.0.1 --extra-php <<'PHP'
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_MEMORY_LIMIT', '256M');
define('HDK_LOG_VIEWS', true);
PHP
else
    echo "[4/8] wp-config.php exists"
fi

# Step 5: Install WordPress
echo "[5/8] Installing WordPress..."
cd "$SITE_DIR"
wp core install \
    --url="https://$SITE_DOMAIN" \
    --title="$SITE_TITLE" \
    --admin_user="$ADMIN_USER" \
    --admin_password="$ADMIN_PASS" \
    --admin_email="$ADMIN_EMAIL" \
    --skip-email 2>/dev/null || echo "WordPress already installed"

# Step 6: Copy theme and plugin
echo "[6/8] Copying theme and plugin..."
THEME_SRC="$(dirname "$0")/wp-content/themes/hongtrancac"
PLUGIN_SRC="$(dirname "$0")/wp-content/plugins/hdk-core"

if [ -d "$THEME_SRC" ]; then
    mkdir -p "$SITE_DIR/wp-content/themes/hongtrancac"
    cp -r "$THEME_SRC"/* "$SITE_DIR/wp-content/themes/hongtrancac/"
    echo "  Theme copied"
fi

if [ -d "$PLUGIN_SRC" ]; then
    mkdir -p "$SITE_DIR/wp-content/plugins/hdk-core"
    cp -r "$PLUGIN_SRC"/* "$SITE_DIR/wp-content/plugins/hdk-core/"
    echo "  Plugin copied"
fi

# Step 7: Activate theme and plugin
echo "[7/8] Activating theme and plugin..."
cd "$SITE_DIR"
wp theme activate hongtrancac 2>/dev/null || echo "  Theme activation skipped"
wp plugin activate hdk-core 2>/dev/null || echo "  Plugin activation skipped"

# Step 8: Create required pages
echo "[8/8] Creating required pages..."

# Create pages if not exist
create_page() {
    local slug="$1"
    local title="$2"
    if ! wp post list --post_type=page --name="$slug" --field=ID 2>/dev/null | grep -q .; then
        wp post create --post_type=page --post_title="$title" --post_name="$slug" --post_status=publish --post_content="" 2>/dev/null
        echo "  Created page: $title ($slug)"
    else
        echo "  Page exists: $title ($slug)"
    fi
}

create_page "danh-sach-truyen" "Danh Sách Truyện"
create_page "bang-xep-hang" "Bảng Xếp Hạng"
create_page "hoan-thanh" "Hoàn Thành"
create_page "truyen-free" "Truyện Free"
create_page "the-loai" "Thể Loại"
create_page "tin-tuc" "Tin Tức"
create_page "dang-nhap" "Đăng Nhập"
create_page "dang-ky" "Đăng Ký"
create_page "tai-khoan" "Tài Khoản"

# Assign page templates where needed
LOGIN_ID="$(wp post list --post_type=page --name=dang-nhap --field=ID 2>/dev/null | head -n1)"
REGISTER_ID="$(wp post list --post_type=page --name=dang-ky --field=ID 2>/dev/null | head -n1)"
ACCOUNT_ID="$(wp post list --post_type=page --name=tai-khoan --field=ID 2>/dev/null | head -n1)"
if [ -n "$LOGIN_ID" ]; then wp post update "$LOGIN_ID" --page_template='page-dang-nhap.php' >/dev/null; fi
if [ -n "$REGISTER_ID" ]; then wp post update "$REGISTER_ID" --page_template='page-dang-ky.php' >/dev/null; fi
if [ -n "$ACCOUNT_ID" ]; then wp post update "$ACCOUNT_ID" --page_template='page-tai-khoan.php' >/dev/null; fi

# Use theme index.php as homepage
wp option update show_on_front posts 2>/dev/null || true

# Flush rewrite rules
wp rewrite flush 2>/dev/null

# Update permalink structure
wp rewrite structure '/%postname%/' 2>/dev/null

# Link with Herd when available
if command -v herd >/dev/null 2>&1; then
    herd link "$SITE_DIR" >/dev/null 2>&1 || true
    herd secure "$SITE_DOMAIN" >/dev/null 2>&1 || true
fi

echo ""
echo "====================================="
echo "  Setup Complete!"
echo "====================================="
echo ""
echo "Site URL:     https://$SITE_DOMAIN"
echo "Admin URL:    https://$SITE_DOMAIN/wp-admin"
echo "Username:     $ADMIN_USER"
echo "Password:     $ADMIN_PASS"
echo ""
echo "To seed demo data:"
echo "  1. Go to https://$SITE_DOMAIN/wp-admin"
echo "  2. Navigate to HDK Truyện > Seed Demo"
echo "  3. Or run: wp --path=$SITE_DIR hdk seed"
echo ""
echo "Enjoy! 📚"
