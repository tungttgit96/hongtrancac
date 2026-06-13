# Hồng Trần Các - Web Truyện Chữ

Nền tảng đọc truyện chữ online xây dựng trên **WordPress** + custom theme + plugin.

## Tech Stack

- WordPress 6.x+ (PHP 8.0+)
- MySQL 8.0+ / MariaDB 10.6+
- Custom Theme: `hatdaukhaai`
- Custom Plugin: `hdk-core`

## Tính năng

- 📚 Đọc truyện theo chương (`/ten-truyen?chuong=1`)
- 🔍 Tìm kiếm truyện, tác giả, thể loại (REST API)
- 🏆 Bảng xếp hạng (lượt xem / yêu thích / đánh giá × ngày / tuần / tháng / năm)
- 📂 Danh sách truyện với filter (thể loại, trạng thái, sắp xếp)
- ❤️ Yêu thích, đánh giá sao, bình luận
- 📖 Tủ truyện, lịch sử đọc, auto-save tiến độ đọc
- 💎 Hệ thống mua chương bằng "hạt" (coin)
- 🔒 Paywall cho chương trả phí
- 📝 Admin CMS: CRUD truyện, chương, tác giả, thể loại, nhân vật
- 📤 Đăng nhiều chương (paste text hoặc upload file .txt)
- 🌙 Dark mode (via darkify plugin)
- 🔍 SEO: canonical URL, Open Graph, Twitter Cards, JSON-LD, XML Sitemap
- 📱 Responsive (375px - 1440px)

---

## Cài đặt trên macOS (Laravel Herd)

### Prerequisites

1. Cài [Laravel Herd](https://herd.laravel.com) (free)
2. Cài MySQL 8.0+:
   ```bash
   # Cài Homebrew trước nếu chưa có:
   /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
   
   # Cài MySQL
   brew install mysql@8.0
   brew services start mysql@8.0
   ```

### Deploy

```bash
# 1. Clone repo
git clone <repo-url> hongtrancac
cd hongtrancac

# 2. Tạo site directory cho Herd
mkdir -p ~/Herd/hongtrancac

# 3. Download WordPress
cd ~/Herd/hongtrancac
wp core download --locale=vi

# 4. Copy theme + plugin từ repo vào WordPress
cp -r /path/to/repo/wp-content/themes/hatdaukhaai wp-content/themes/
cp -r /path/to/repo/wp-content/plugins/hdk-core wp-content/plugins/

# 5. Tạo database
mysql -u root -e "CREATE DATABASE hongtrancac CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 6. Tạo wp-config.php
wp config create \
  --dbname=hongtrancac \
  --dbuser=root \
  --dbhost=127.0.0.1 \
  --extra-php="define('WP_DEBUG', true); define('WP_DEBUG_LOG', true); define('WP_DEBUG_DISPLAY', false); define('WP_MEMORY_LIMIT', '256M'); define('HDK_LOG_VIEWS', true);"

# 7. Cài WordPress
wp core install \
  --url="https://hongtrancac.test" \
  --title="Hồng Trần Các" \
  --admin_user="admin" \
  --admin_password="your-password" \
  --admin_email="admin@hongtrancac.com" \
  --skip-email

# 8. Activate theme + plugin
wp theme activate hatdaukhaai
wp plugin activate hdk-core

# 9. Tạo pages
wp post create --post_type=page --post_title="Danh Sách Truyện" --post_name="danh-sach-truyen" --post_status=publish
wp post create --post_type=page --post_title="Bảng Xếp Hạng" --post_name="bang-xep-hang" --post_status=publish
wp post create --post_type=page --post_title="Hoàn Thành" --post_name="hoan-thanh" --post_status=publish
wp post create --post_type=page --post_title="Truyện Free" --post_name="truyen-free" --post_status=publish
wp post create --post_type=page --post_title="Thể Loại" --post_name="the-loai" --post_status=publish
wp post create --post_type=page --post_title="Tin Tức" --post_name="tin-tuc" --post_status=publish

# 10. Permalink + rewrite
wp rewrite structure '/%postname%/'
wp rewrite flush

# 11. Link với Herd
herd link ~/Herd/hongtrancac
herd secure hongtrancac

# 12. Seed demo data (optional)
wp hdk seed
```

Truy cập: `https://hongtrancac.test` | Admin: `https://hongtrancac.test/wp-admin`

---

## Cài đặt trên Windows (Laragon)

### Prerequisites

1. Cài [Laragon](https://laragon.org) (full) - đã bao gồm PHP, MySQL, Apache/Nginx
2. Cài [WP-CLI](https://wp-cli.org):
   ```powershell
   # Tải wp-cli.phar vào thư mục Laragon
   cd C:\laragon\bin
   curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
   # Tạo file wp.bat với nội dung:
   # @ECHO OFF
   # php "C:\laragon\bin\wp-cli.phar" %*
   ```

### Deploy

```powershell
# 1. Clone repo
git clone <repo-url> C:\laragon\www\hongtrancac
cd C:\laragon\www\hongtrancac

# 2. Download WordPress
wp core download --locale=vi --path=C:\laragon\www\hongtrancac

# 3. Copy theme + plugin
cp -r wp-content/themes/hatdaukhaai C:\laragon\www\hongtrancac\wp-content\themes\
cp -r wp-content/plugins/hdk-core C:\laragon\www\hongtrancac\wp-content\plugins\

# 4. Tạo database
# Mở Laragon > Menu > MySQL > phpMyAdmin
# Tạo database mới tên "hongtrancac" với utf8mb4_unicode_ci

# 5. Tạo wp-config.php
wp config create --dbname=hongtrancac --dbuser=root --dbpass= --dbhost=localhost

# 6-12. Chạy tương tự như macOS (bước 6-12 ở trên)
# Lưu ý: URL sẽ là http://hongtrancac.test (Laragon dùng .test mặc định)
```

Truy cập: `http://hongtrancac.test` | Admin: `http://hongtrancac.test/wp-admin`

---

## Cài đặt trên Linux (LAMP / Docker)

### Docker (Recommended)

```bash
# docker-compose.yml
# (sử dụng image wordpress:latest + mysql:8.0)
# Mount thư mục theme và plugin vào volumes

git clone <repo-url> hongtrancac
cd hongtrancac
docker-compose up -d
```

### LAMP thủ công

```bash
# 1. Cài Apache/Nginx + PHP 8.0+ + MySQL 8.0+
sudo apt install apache2 php8.1 php8.1-mysql mysql-server
# hoặc trên CentOS: yum install httpd php php-mysqlnd mysql-server

# 2. Download WordPress vào /var/www/html/hongtrancac
# 3. Copy theme + plugin như bước 4-5 ở trên
# 4. Tạo database, wp-config.php, install WordPress như bước 6-12
```

---

## Cấu trúc thư mục

```
hongtrancac/
├── .gitignore
├── .htaccess
├── README.md
├── robots.txt
├── plan.md                          # Implementation plan
├── wp-content/
│   ├── themes/
│   │   └── hatdaukhaai/             # Custom theme
│   │       ├── style.css
│   │       ├── functions.php
│   │       ├── header.php / footer.php
│   │       ├── index.php (home)
│   │       ├── single.php (news)
│   │       ├── 404.php
│   │       ├── page-*.php           # Page templates
│   │       ├── templates/           # Story/chapter templates
│   │       ├── inc/                 # Design tokens, helpers
│   │       └── assets/              # CSS + JS
│   └── plugins/
│       └── hdk-core/                # Main plugin
│           ├── hdk-core.php
│           └── includes/
│               ├── class-schema.php  # DB tables
│               ├── class-db.php      # Query helpers
│               ├── class-rewrite.php # URL rewrite
│               ├── class-template-loader.php
│               ├── class-rest-api.php
│               ├── class-seo.php
│               ├── class-sitemap.php
│               ├── class-admin.php   # Admin CMS
│               ├── class-cache.php
│               └── class-cli.php     # WP-CLI commands
```

## Plugin bổ sung (không bao gồm trong repo, mua riêng)

Các plugin này cần mua từ Envato Elements và đặt vào `wp-content/plugins/`:

- **Darkify** - Dark mode toggle
- **WP Membership** - Hệ thống membership trả phí

## WP-CLI Commands

```bash
wp hdk seed    # Tạo dữ liệu mẫu (5 tác giả, 10 thể loại, 30 truyện)
wp hdk import  # Import truyện từ file CSV/JSON (đang phát triển)
```

---

## URLs chính

| URL | Mô tả |
|-----|-------|
| `/` | Trang chủ |
| `/danh-sach-truyen` | Danh sách truyện + filter |
| `/bang-xep-hang` | Bảng xếp hạng |
| `/the-loai` | Tất cả thể loại |
| `/the-loai/{slug}` | Truyện theo thể loại |
| `/tac-gia/{slug}` | Truyện theo tác giả |
| `/hoan-thanh` | Truyện hoàn thành |
| `/truyen-free` | Truyện miễn phí |
| `/tin-tuc` | Tin tức (WP posts) |
| `/{story-slug}` | Chi tiết truyện |
| `/{story-slug}?chuong={n}` | Đọc chương |
| `/sitemap.xml` | XML Sitemap |
