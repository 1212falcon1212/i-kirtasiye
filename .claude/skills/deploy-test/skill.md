---
name: deploy-test
description: Test ortamina deploy - develop branch'ini GitHub'a push eder
user-invocable: true
---

# Deploy Test Module

> Test ortami deploy'u - test.i-kirtasiye.com (develop branch)

**Activates on:** deploy-test, test deploy, test sunucu, test ortami, develop push

---

## Server Info

| Key | Value |
|-----|-------|
| Domain | `test.i-kirtasiye.com` |
| GitHub Repo | `1212falcon1212/b2b-kirtasiye` |
| Branch | `develop` |

---

## Deploy Flow

### Adimlar

```bash
# 1. Degisiklikleri kontrol et
git status
git diff --stat

# 2. TypeScript kontrolu (frontend degisikligi varsa)
cd frontend && npx tsc --noEmit

# 3. Stage & Commit
git add <dosyalar>
git commit -m "Feat/Fix: aciklama"

# 4. Push to develop
git push origin develop
```

---

## Deploy Checklist

```
[ ] Lokal'de test edildi
[ ] Frontend degisikligi varsa: npx tsc --noEmit hatasiz
[ ] Backend degisikligi varsa: php artisan test --compact (ilgili testler)
[ ] git add + commit yapildi
[ ] git push origin develop basarili
[ ] test.i-kirtasiye.com uzerinden kontrol edildi
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

## Onemli Notlar

- **Sadece `develop` branch'ina push yapilir**, `main` branch'a push YAPMA
- Production deploy icin `/deploy` skill'ini kullan
- Test ortaminda SSL henuz aktif degil, HTTP uzerinden test et
- Commit oncesi `git diff` ile degisiklikleri gozden gecir
