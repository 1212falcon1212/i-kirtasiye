---
name: deploy
description: VPS deployment for Laravel + Next.js via SSH, PM2, and manual deploy steps
user-invocable: true
---

# Deploy Module

> Production deployment for i-kirtasiye.com (VPS + PM2 + SSH)

**Activates on:** deploy, push, canli, production, sunucu, server, pm2, ssh

---

## Server Info

| Key | Value |
|-----|-------|
| SSH | `ssh vps-root` |
| Domain | `i-kirtasiye.com` |
| Backend Path | `/home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend` |
| Frontend Path | `/home/i-kirtasiye/htdocs/i-kirtasiye.com/public/frontend` |
| PM2 Process | `i-kirtasiye-frontend` |
| GitHub Repo | `1212falcon1212/b2b-kirtasiye` (main branch) |
| Admin Panel | `https://i-kirtasiye.com/admin` |

---

## Deploy Flow

### Otomatik Deploy (Webhook)
```
git push → GitHub webhook → sunucuda otomatik:
  git pull → composer install → php artisan migrate
  → npm install && npm run build → pm2 restart
```

Deploy suresi: ~30-60 saniye

### Manuel Deploy (Migration/Seeder Gerektiginde)

```bash
# 1. Lokal: Commit & Push
git add <dosyalar>
git commit -m "Feat: aciklama"
git push

# 2. SSH ile sunucuya baglan
ssh vps-root

# 3. Kodu cek
cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public
git pull origin main

# 4. Backend islemleri
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan filament:optimize-clear

# 5. Frontend build
cd ../frontend
npm install
npm run build

# 6. PM2 restart
pm2 restart i-kirtasiye-frontend
```

---

## Sik Kullanilan Komutlar

### Cache Temizleme
```bash
ssh vps-root "cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend && php artisan optimize:clear && php artisan filament:optimize-clear"
```

### Frontend Restart
```bash
ssh vps-root "pm2 restart i-kirtasiye-frontend"
```

### Log Kontrol
```bash
# Deploy log
ssh vps-root "tail -50 /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/deploy/deploy.log"

# Laravel log
ssh vps-root "tail -100 /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend/storage/logs/laravel.log"

# PM2 log
ssh vps-root "pm2 logs i-kirtasiye-frontend --lines 50"
```

### Tinker ile Seed/Fix
```bash
ssh vps-root "cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend && php artisan tinker"
```

### Migration Status
```bash
ssh vps-root "cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend && php artisan migrate:status"
```

---

## Commit Mesaj Kurallari

| Tip | Ornek |
|-----|-------|
| Yeni ozellik | `Feat: Bildirim sistemi eklendi` |
| Bug duzeltme | `Fix: Footer layout sorunu duzeltildi` |
| Stil | `Style: Buton renkleri guncellendi` |
| Refactor | `Refactor: OrderService optimize edildi` |

---

## Sorun Giderme

### Route/Filament Hatasi
```bash
ssh vps-root "cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend && php artisan optimize:clear && php artisan filament:optimize-clear"
```

### Frontend Beyaz Ekran
```bash
ssh vps-root "cd /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/frontend && npm run build && pm2 restart i-kirtasiye-frontend"
```

### Permission Hatasi
```bash
ssh vps-root "chown -R www-data:www-data /home/i-kirtasiye/htdocs/i-kirtasiye.com/public/backend/storage"
```

---

## Deploy Checklist

```
[ ] Lokal'de test edildi
[ ] TypeScript hata yok (cd frontend && npx tsc --noEmit)
[ ] git add + commit + push yapildi
[ ] SSH ile sunucuya baglanildi
[ ] git pull basarili
[ ] Migration varsa: php artisan migrate --force
[ ] Seeder varsa: php artisan tinker ile calistirildi
[ ] Cache temizlendi: php artisan optimize:clear
[ ] Frontend build: npm run build
[ ] PM2 restart: pm2 restart i-kirtasiye-frontend
[ ] Canli site test edildi
```
