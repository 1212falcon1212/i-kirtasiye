---
name: data
description: Analytics & data queries for MySQL, Eloquent, and Laravel Boost
user-invocable: true
---

# Data Module

> Analytics, metrics, data queries with MySQL + Eloquent

**Activates on:** analytics, metrics, query, data, dashboard, report, KPI, SQL

**Collaborates with:** `laravel-api` for endpoints, `ui` for dashboards

---

## Query Tools

This project has **Laravel Boost MCP** with direct DB access:

```
# Read-only SQL queries
mcp__laravel-boost__database-query

# Schema inspection
mcp__laravel-boost__database-schema

# PHP execution in Laravel context
mcp__laravel-boost__tinker
```

---

## Key Metrics (MySQL)

### Siparis Metrikleri
```sql
-- Gunluk siparis ozeti
SELECT
  DATE(created_at) AS tarih,
  COUNT(*) AS siparis_sayisi,
  SUM(total_amount) AS toplam_ciro,
  AVG(total_amount) AS ortalama_siparis
FROM orders
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY tarih DESC;

-- Status dagilimi
SELECT
  status,
  COUNT(*) AS sayi,
  SUM(total_amount) AS toplam
FROM orders
GROUP BY status;

-- En cok siparis veren kirtasiyeciler
SELECT
  u.business_name,
  COUNT(o.id) AS siparis_sayisi,
  SUM(o.total_amount) AS toplam_harcama
FROM orders o
JOIN users u ON o.user_id = u.id
GROUP BY o.user_id, u.business_name
ORDER BY toplam_harcama DESC
LIMIT 20;
```

### Urun & Satis Metrikleri
```sql
-- En cok satan urunler
SELECT
  p.name AS urun,
  SUM(oi.quantity) AS toplam_adet,
  SUM(oi.total_price) AS toplam_satis
FROM order_items oi
JOIN products p ON oi.product_id = p.id
GROUP BY oi.product_id, p.name
ORDER BY toplam_satis DESC
LIMIT 20;

-- Satici bazinda performans
SELECT
  u.business_name AS satici,
  COUNT(DISTINCT oi.order_id) AS siparis_sayisi,
  SUM(oi.total_price) AS toplam_satis,
  SUM(oi.commission_amount) AS toplam_komisyon,
  SUM(oi.seller_payout_amount) AS toplam_hakedis
FROM order_items oi
JOIN users u ON oi.seller_id = u.id
GROUP BY oi.seller_id, u.business_name
ORDER BY toplam_satis DESC;
```

### Kullanici Metrikleri
```sql
-- Yeni kayitlar (haftalik)
SELECT
  YEARWEEK(created_at) AS hafta,
  COUNT(*) AS yeni_kullanici
FROM users
WHERE role = 'retailer'
GROUP BY YEARWEEK(created_at)
ORDER BY hafta DESC
LIMIT 12;

-- Aktif vs Pasif kullanicilar
SELECT
  CASE
    WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 'Aktif (7 gun)'
    WHEN last_login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 'Aktif (30 gun)'
    ELSE 'Pasif'
  END AS durum,
  COUNT(*) AS sayi
FROM users
WHERE role = 'retailer'
GROUP BY durum;
```

---

## Eloquent Query Patterns

```php
// Aggregation
Order::where('status', 'delivered')
    ->whereBetween('created_at', [$start, $end])
    ->selectRaw('DATE(created_at) as tarih, COUNT(*) as sayi, SUM(total_amount) as toplam')
    ->groupBy('tarih')
    ->orderBy('tarih', 'desc')
    ->get();

// Subquery
User::withCount(['orders' => fn($q) => $q->where('status', 'delivered')])
    ->having('orders_count', '>', 0)
    ->orderByDesc('orders_count')
    ->limit(20)
    ->get();

// Join for complex reports
OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
    ->join('orders', 'order_items.order_id', '=', 'orders.id')
    ->where('orders.status', 'delivered')
    ->selectRaw('products.name, SUM(order_items.quantity) as toplam_adet')
    ->groupBy('products.id', 'products.name')
    ->orderByDesc('toplam_adet')
    ->limit(20)
    ->get();
```

---

## Tinker ile Hizli Analiz

```php
// Tinker'da calistir:
Order::where('status', 'delivered')->count();
Order::sum('total_amount');
User::where('role', 'retailer')->where('is_verified', true)->count();
```

---

## Index Optimization

```php
// Migration'da index ekle
Schema::table('orders', function (Blueprint $table) {
    $table->index(['user_id', 'status', 'created_at']);
    $table->index(['status', 'payment_status']);
});

// Yavas sorgu tespit
DB::listen(function ($query) {
    if ($query->time > 100) {
        Log::warning('Slow query', ['sql' => $query->sql, 'time' => $query->time]);
    }
});
```

---

## Checklist

```
[ ] Query dogru sonuc veriyor (EXPLAIN ile kontrol)
[ ] Index eklendi (sik kullanilan WHERE/JOIN kolonlarina)
[ ] Pagination kullanildi (buyuk veri setlerinde)
[ ] N+1 sorunu yok (eager loading)
[ ] Cache eklendi (sik degismeyen raporlarda)
```
