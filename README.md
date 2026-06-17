# Hồng Trần Các - Web Truyện Chữ

Nền tảng đọc truyện chữ online xây dựng trên **WordPress** + custom theme + plugin.

## Tech Stack

- WordPress 6.x+ (PHP 8.0+)
- MySQL 8.0+ / MariaDB 10.6+
- Custom Theme: `hongtrancac`
- Custom Plugin: `hdk-core`

## Tính năng

### Đọc truyện
- 📚 Đọc truyện theo chương (`/ten-truyen?chuong=1`)
- 🎛 Tùy chỉnh trải nghiệm đọc: cỡ chữ, font, giãn dòng, theme (sáng/tối/sepia), chiều rộng
- 📋 Danh sách chương nổi (floating TOC) ngay trong reader
- 🎧 Mini audio player cho truyện có audio
- 🚫 Anti-scraping (mã hóa nội dung, chống copy/click phải/in)

### Khám phá
- 🔍 Tìm kiếm truyện, tác giả, thể loại (REST API)
- 🏆 Bảng xếp hạng (lượt xem / yêu thích / đánh giá × ngày / tuần / tháng / năm)
- 📂 Danh sách truyện với filter (thể loại, trạng thái, sắp xếp)
- ⭐ Đề cử biên tập + 🔥 Hot tuần trên trang chủ

### Người dùng
- 👤 Trang tài khoản độc giả (`/tai-khoan`): hồ sơ, tủ truyện, đang đọc, đã mua, lịch sử đọc/nghe, bình luận, cài đặt tài khoản
- 🔐 Trang đăng nhập có theme (`/dang-nhap`) thay vì `wp-login.php` gốc
- 🧾 Trang đăng ký (`/dang-ky`) bằng tên đăng nhập, tên hiển thị và mật khẩu
- ⚙️ Đổi tên hiển thị, email, avatar URL và mật khẩu từ trang cá nhân
- ❤️ Yêu thích, đánh giá sao, bình luận
- 🔔 Thông báo (chương mới, trả lời bình luận, mua thành công)
- 📖 Auto-save tiến độ đọc

### Ví Linh Thạch & Thanh toán
- 💎 Hệ thống mua chương bằng "Linh Thạch" (coin)
- 💳 Ví Linh Thạch: số dư, lịch sử giao dịch, nạp Linh Thạch
- 📅 Điểm danh hàng ngày nhận Linh Thạch
- 🔒 Paywall cho chương trả phí (giá từng chương + giá full truyện)

### Admin CMS
- 📝 CRUD truyện, chương, tác giả, thể loại, nhân vật
- 🎧 Nhập audio URL, tiêu đề audio và thời lượng cho từng truyện
- 📤 Đăng nhiều chương (paste text hoặc upload file .txt)
- 📥 Import CSV/JSON (truyện, chương, tác giả, thể loại) + preview + chống trùng
- 💎 Quản lý Linh Thạch (cộng/trừ thủ công, gói nạp, lịch sử giao dịch)
- 💬 Quản lý bình luận (duyệt/xóa)
- 🚩 Xử lý báo lỗi chương từ người dùng
- 📊 Thống kê vận hành (views, top truyện, biểu đồ 7 ngày)
- 🎨 Cấu hình banner trang chủ
- 👁️ Cờ "Ẩn khỏi trang chủ / đề cử" cho từng truyện

### SEO & Bảo mật
- 🔍 SEO: title, canonical URL, Open Graph, Twitter Cards, JSON-LD (BreadcrumbList, Book, Article), XML Sitemap
- 🛡 Nonce cho mọi REST API write endpoint (favorite, rating, comment, purchase, daily-claim, reader prefs, v.v.)
- 🌙 Dark mode (theme built-in + darkify plugin)
- 📱 Responsive (375px - 1440px)

### Hiệu năng & Vận hành
- ⚡ CSS design tokens nằm trong `main.css` thay vì inline `<head>`
- 🗄️ Cache transient cho các section trang chủ (banner fallback, mới cập nhật, hot, đề cử, hot tuần)
- ✅ Smoke checks trong `docs/review/website-smoke-checks.md`

---

## Chạy local nhanh nhất bằng 127.0.0.1

Đây là cách khuyến nghị hiện tại. Không cần domain `.test`; site chạy trực tiếp ở `http://127.0.0.1:8000`.

### Cần có

- MySQL local
- WP-CLI
- PHP 8.0+

Nếu thiếu MySQL:

```bash
brew install mysql@8.0
brew services start mysql@8.0
```

### Một lệnh setup

Chạy từ thư mục repo:

```bash
bash setup.sh
```

Script sẽ:

- Dùng chính thư mục repo làm WordPress root
- Tải WordPress tiếng Việt
- Tạo database `hongtrancac`
- Tạo `wp-config.php`
- Cài WordPress với URL `http://127.0.0.1:8000`
- Active theme/plugin
- Tạo các page cần thiết: `/dang-nhap`, `/dang-ky`, `/tai-khoan`, `/danh-sach-truyen`, `/bang-xep-hang`, `/the-loai`, `/hoan-thanh`, `/truyen-free`
- Flush permalink

Start web:

```bash
bash start-local.sh
```

Sau khi server chạy:

```text
Site:  http://127.0.0.1:8000
Admin: http://127.0.0.1:8000/wp-admin
User:  admin
Pass:  admin123
```

Seed dữ liệu mẫu:

```bash
wp --path="$(pwd)" hdk seed
```

Nếu đổi code trong repo sau khi đã setup, chạy lại:

```bash
bash setup.sh
```

Script sẽ giữ nguyên theme/plugin trong repo và cập nhật lại cấu hình cần thiết.

### Lỗi thường gặp

- `mysql: command not found`: cài MySQL bằng Homebrew hoặc dùng MySQL đi kèm app khác.
- `wp: command not found`: cài WP-CLI.
- `http://127.0.0.1:8000` không mở được: kiểm tra terminal đang chạy `bash start-local.sh`.
- Nếu dùng `localhost`, truy cập `http://localhost:8000`. Asset sẽ tự dùng đúng host bạn mở.
- Link vẫn nhảy về domain cũ: plugin `hdk-core` đã override local URL theo request host. Nếu vẫn gặp, flush cache trình duyệt và chạy `wp option update home http://127.0.0.1:8000 && wp option update siteurl http://127.0.0.1:8000`.
- CSS không đổi sau khi sửa: theme đã dùng `filemtime()` để cache-bust CSS/JS, chỉ cần refresh browser.
- Trang danh sách truyện dùng `keyword` để tìm kiếm, ví dụ `http://localhost:8000/danh-sach-truyen/?keyword=mat`. Không dùng `?s=` vì đó là query search mặc định của WordPress.

---

## Cài đặt thủ công trên macOS (Laravel Herd)

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
cp -r /path/to/repo/wp-content/themes/hongtrancac wp-content/themes/
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
  --url="http://127.0.0.1:8000" \
  --title="Hồng Trần Các" \
  --admin_user="admin" \
  --admin_password="your-password" \
  --admin_email="admin@hongtrancac.com" \
  --skip-email

# 8. Activate theme + plugin
wp theme activate hongtrancac
wp plugin activate hdk-core

# 9. Tạo pages
wp post create --post_type=page --post_title="Danh Sách Truyện" --post_name="danh-sach-truyen" --post_status=publish
wp post create --post_type=page --post_title="Bảng Xếp Hạng" --post_name="bang-xep-hang" --post_status=publish
wp post create --post_type=page --post_title="Hoàn Thành" --post_name="hoan-thanh" --post_status=publish
wp post create --post_type=page --post_title="Truyện Free" --post_name="truyen-free" --post_status=publish
wp post create --post_type=page --post_title="Thể Loại" --post_name="the-loai" --post_status=publish
wp post create --post_type=page --post_title="Tin Tức" --post_name="tin-tuc" --post_status=publish
wp post create --post_type=page --post_title="Đăng Nhập" --post_name="dang-nhap" --post_status=publish
wp post create --post_type=page --post_title="Đăng Ký" --post_name="dang-ky" --post_status=publish
wp post create --post_type=page --post_title="Tài Khoản" --post_name="tai-khoan" --post_status=publish

# 10. Set page templates
wp post update $(wp post list --post_type=page --post_name="dang-nhap" --field=ID) --page_template='page-dang-nhap.php'
wp post update $(wp post list --post_type=page --post_name="dang-ky" --field=ID) --page_template='page-dang-ky.php'
wp post update $(wp post list --post_type=page --post_name="tai-khoan" --field=ID) --page_template='page-tai-khoan.php'

# 11. Permalink + rewrite
wp rewrite structure '/%postname%/'
wp rewrite flush

# 12. Link với Herd
herd link ~/Herd/hongtrancac
herd secure hongtrancac

# 13. Seed demo data (optional)
wp hdk seed
```

Truy cập: `http://127.0.0.1:8000` | Admin: `http://127.0.0.1:8000/wp-admin`

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
cp -r wp-content/themes/hongtrancac C:\laragon\www\hongtrancac\wp-content\themes\
cp -r wp-content/plugins/hdk-core C:\laragon\www\hongtrancac\wp-content\plugins\

# 4. Tạo database
# Mở Laragon > Menu > MySQL > phpMyAdmin
# Tạo database mới tên "hongtrancac" với utf8mb4_unicode_ci

# 5. Tạo wp-config.php
wp config create --dbname=hongtrancac --dbuser=root --dbpass= --dbhost=localhost

# 6-12. Chạy tương tự như macOS (bước 6-12 ở trên)
# URL local khuyến nghị: http://127.0.0.1:8000
```

Truy cập: `http://127.0.0.1:8000` | Admin: `http://127.0.0.1:8000/wp-admin`

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
│   │   └── hongtrancac/             # Custom theme
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
wp hdk seed                   # Tạo dữ liệu mẫu (5 tác giả, 10 thể loại, 30 truyện)
wp hdk import --source=file   # Import truyện từ file CSV/JSON
wp hdk create_account_page    # Tạo trang /tai-khoan nếu chưa có
```

---

## URLs chính

| URL | Mô tả |
|-----|-------|
| `/` | Trang chủ (hero, đề cử, hot tuần, danh mục) |
| `/danh-sach-truyen` | Danh sách truyện + filter |
| `/danh-sach-truyen?audio=1` | Danh sách truyện có audio |
| `/bang-xep-hang` | Bảng xếp hạng |
| `/the-loai` | Tất cả thể loại |
| `/the-loai/{slug}` | Truyện theo thể loại |
| `/tac-gia/{slug}` | Truyện theo tác giả |
| `/nhan-vat/{slug}` | Truyện theo nhân vật |
| `/hoan-thanh` | Truyện hoàn thành |
| `/truyen-free` | Truyện miễn phí |
| `/tin-tuc` | Tin tức (WP posts) |
| `/tai-khoan` | Trang tài khoản độc giả (hồ sơ, lịch sử đọc/nghe, ví Linh Thạch, thông báo, cài đặt...) |
| `/dang-nhap` | Trang đăng nhập có theme |
| `/dang-ky` | Trang đăng ký tài khoản |
| `/{story-slug}` | Chi tiết truyện + danh sách chương |
| `/{story-slug}?chuong={n}` | Đọc chương |
| `/sitemap.xml` | XML Sitemap |
