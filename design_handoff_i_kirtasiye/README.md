# Handoff: i-kirtasiye B2B Pazaryeri (Market, Kategori, Ürün Detay, Arama)

## Overview
i-kirtasiye, mevcut **i-depo** B2B eczane pazaryerinin altyapısı üzerine kurulan yeni bir **B2B kırtasiye pazaryeridir**. Hedef kitle: bayi/toptan alıcı kırtasiyeciler. Ürünler tek bir kataloğa bağlıdır; her satıcı aynı ürün için kendi **ilanını** açar (kdv dahil fiyat, stok, teslimat süresi). İlanlar ürün detayında **en düşük fiyattan en yükseğe sıralı** listelenir.

Bu paket içinde 4 ana sayfanın HTML/JSX prototipi ve tasarım sistemi var:
1. **Market (anasayfa)**
2. **Kategori sayfası** (sol filtre + grid)
3. **Ürün detay sayfası** (ilan listesi vurgulu)
4. **Arama sonuçları sayfası**

## About the Design Files
Bu klasördeki dosyalar, niyetlenen görsel tasarım ve davranışı gösteren **HTML tabanlı tasarım referanslarıdır** — production kodu olarak doğrudan kopyalanmak için tasarlanmamıştır. Ekibin görevi, bu tasarımları **mevcut i-depo codebase'inin (frontend framework'ünün) konvansiyonlarıyla** yeniden uygulamaktır. Eğer hedef codebase'de bir component kütüphanesi varsa, oradaki Button/Input/Card primitif'leri kullanılmalıdır; aşağıdaki design token'ları yeni renk paleti / spacing / type scale olarak adapte edilmelidir.

Backend modeli (Product → Listing → Seller) i-depo ile aynı kalacak; sadece kategori taksonomisi ve frontend kabuğu değişiyor.

## Fidelity
**High-fidelity (hifi).** Renkler, tipografi, spacing, ilan kartı ve liste komponentleri pixel-perfect şekilde tanımlanmıştır. Geliştirici `tokens` bölümündeki CSS değişkenlerini birebir kullanmalı ve layout'ları HTML referanslarıyla aynı düzene oturtmalıdır.

Tasarımda 5 renk paleti seçilebilir (Tweaks panel ile değiştirilebilir): **ocean / forest / amber / violet / graphite**. Varsayılan brand color **amber (turuncu)**'dur — `--accent: #b8651a`. Geliştirici bu paleti tek bir CSS değişken katmanı üzerinden değiştirebilir hale getirmelidir.

---

## Screens / Views

### 1. Market (Anasayfa)

**Purpose:** Bayi giriş ekranı — kampanyalar, hızlı sipariş, kategori navigasyonu, öne çıkan ilanlar.

**Layout (1440px max-width, 24px padding):**
1. **UtilityBar** (üst dar şerit, 28px) — kargo bilgisi, güvenli ödeme, yardım/satıcı ol/dil linkleri
2. **TopBar** (72px) — Logo + Kategoriler dropdown + büyük search input + Bildirim/Hesap/Sepet butonları
3. **MainNav** (44px) — yatay kategori sekmeleri, sağda "24 saatte teslim" rozeti
4. **Hero (split banner, 360px min-height)**
   - Sol (1fr): solid accent gradient blok, dekoratif daireler, "Hafta sonuna özel" chip, başlık `OKULA DÖNÜŞ %25 İNDİRİM`, açıklama, "Alışverişe başla" CTA
   - Sağ (2fr): pastel arkaplan, 3 absolute-positioned ürün placeholder'ı (defter dikey, kalem seti yuvarlak, boya seti rotate), sağ alt köşede yeşil pulse'lu canlı rozet, sol altta carousel dot'ları
5. **Info chip row** — 4 kolonlu grid, her kart: ikon kutusu (40×40, tinted bg) + başlık + alt metin. Kategoriler: Komisyonsuz Satış (accent), Ücretsiz Kargo (success), Güvenli Tedarik (warning), Vadeli Alışveriş (accent)
6. **Günün Fırsatı + Hızlı Sipariş** — 2:1 split row
   - Fırsat kartı: 200px ürün görseli + içerik + alt CTA. Stok progress bar (0-100%), "%25 indirim" chip, PSF üstü çizili
   - Hızlı sipariş kartı: SKU/barkod input, "Excel ile toplu yükle" butonu, alt 2 metrik kutusu (142 bayi / 3.8s)
7. **Sezonun Öne Çıkanları & Haftanın Ürünleri** — 2 kolonlu grid, her kart tinted-header (warning-soft / accent-soft) + 3 ürünlük mini grid
8. **Son Satılanlar** — yeşil pulse rozetli "Canlı güncellemeler" başlığı, 4 kolonlu mini ürün satırı (60px görsel + içerik)
9. **Kategoriler grid** — 6 kolon × 2 satır (12 kategori), her kart: 40px emoji icon kutusu + isim + ürün sayısı
10. **Öne çıkan ilanlar** — başlık + 4 sekme (Çok satan / Yeni eklenen / Fiyat düşen / Hızlı kargo) + 4 kolonlu ProductCard grid (8 ürün)
11. **Markalar şeridi** — 8 kolonlu, padding 8 olan tek container içinde marka isimleri
12. **Secondary banners** — 3 kolonlu pastel gradient promo şeritleri (Yeni Tedarikçi / 24 saatte teslim / Toplu sipariş)
13. **Footer** — 5 kolon (logo+açıklama, Pazaryeri, Satıcılar, Bayiler, Destek) + alt bar

### 2. Kategori Sayfası

**Purpose:** Bir kategoriye drill-down sonrası filtreleme + ürün gezme.

**Layout:**
1. UtilityBar + TopBar + MainNav (Market ile aynı)
2. **Breadcrumb** (12px, fg-muted): Anasayfa · Tüm kategoriler · **Kalem & Yazı**
3. **Page heading** (28px h1) + meta satırı: `2.341 ürün · 18.520 aktif ilan · 412 satıcı`
4. **Main grid: 260px filter sidebar + 1fr results**
   - **Filter sidebar (sticky, top 12)**:
     - Filtreler başlığı + Temizle linki
     - Aktif filtreler chip'leri (Faber-Castell, Stokta var, 100₺-500₺) — her birinde × butonu
     - Filter sections (collapsible, default açık):
       - Alt kategori (5 checkbox + "+ 8 alt kategori" linki)
       - Marka (5 checkbox + "+ 22 marka")
       - Fiyat aralığı (Min/Max input + dual-handle range slider)
       - İlan durumu (Stokta var / Hızlı kargo / Onaylı satıcı / Vadeli alışveriş)
       - Satıcı şehri (default kapalı)
       - Min. satıcı puanı (radio + 5/4/3 yıldız)
   - **Results panel:**
     - Toolbar: sonuç sayısı + sayfalama + Sırala (En düşük fiyat dropdown) + Grid/List toggle
     - 4 kolonlu ProductCard grid (18 ürün)
     - Pagination: Önceki / 1-5 / … / 47 / Sonraki

### 3. Ürün Detay Sayfası

**Purpose:** Bayi'nin tek ürün için en uygun satıcıyı seçmesi. **Ana odak: ilan listesi.**

**Layout:**
1. UtilityBar + TopBar + MainNav
2. Breadcrumb
3. **Üst alan: 2 kolon eşit grid (1fr 1fr)**
   - **Sol (Galeri):** ana görsel (aspect 1:1, radius-lg), altında 5 kolonlu thumbnail grid (1. seçili — 2px accent border, 5. "+3" göstergesi)
   - **Sağ (Özet):**
     - Tag chip + SKU mono yazı
     - Başlık (26px h1)
     - Marka linki + Stars + 4.7 + "1.842 değerlendirme"
     - **Price hero card** (border'lı): PSF üstü çizili (sol) / **EN DÜŞÜK İLAN FİYATI** (sağ, 32px mono bold, KDV dahil notu)
     - Accent-soft uyarı: "14 satıcı bu ürünü listeliyor — fiyat aralığı ₺184,50 – ₺262,00"
     - Adet stepper (44px) + **EN UCUZ İLANI SEPETE EKLE** (büyük accent btn) + favori btn
     - 3 kolonlu mini info: 1-2 günde teslim / Onaylı satıcı garantisi / Vadeli ödeme
     - **Specs tablosu** (2 kolonlu, dashed bottom border): Marka, Seri, Sertlik, Adet, Menşei, Barkod
4. **LISTINGS SECTION (focus area)**
   - Header: eyebrow "Tüm satıcı ilanları" + h2 "14 satıcının ilanı — en düşük fiyattan sıralı" + sağda filtre chip'leri (Tümü / Onaylı / Hızlı kargo / Şehrim)
   - **Sütun başlığı satırı** (uppercase 11px): # / Satıcı / Stok / Teslimat / Fiyat / İşlem
   - **ListingRow** (her satır):
     - Grid: `44px 1fr 140px 140px 140px 140px`
     - 1. satır (en ucuz) **isBest=true** → background `accent-soft`, border `1px solid accent`
     - Sıra numarası (mono `#01`, accent rengi)
     - Satıcı bloğu: isim (14px bold) + "Onaylı" chip (success) + "Hızlı" chip (accent) + altta Stars + puan + yorum sayısı + şehir
     - Stok kolonu: küçük başlık + mono adet
     - Teslimat: Truck icon + süre
     - Fiyat: KDV dahil notu + 18px mono bold
     - İşlem: Heart icon btn + Sepet btn (en üstte primary, diğerlerinde ghost)
   - "Daha fazla ilan göster" alt linki
5. **Benzer ürünler** — 4 kolon ProductCard grid

### 4. Arama Sonuçları

**Purpose:** Search query'sine göre ürünleri ve filtreleme pivot'unu sunar.

**Layout:**
1. UtilityBar + TopBar (search input "kurşun kalem" ile dolu) + MainNav
2. **Search header strip** (bg-elevated, alt border):
   - Eyebrow "Arama sonuçları"
   - h1: `"kurşun kalem" için sonuçlar` (sorgu accent renkte)
   - Sağda: 847 ürün · 6.124 ilan · 0.18 sn meta
   - **İlgili aramalar** chip satırı (6 öneri)
3. **Kategoriye göre filtrele pivot** — 5 kolonlu ince kart şeridi (1. seçili — accent-soft bg)
4. **Main grid: 240px filter + 1fr results**
   - Compact filter sidebar (Marka / Sertlik / Adet)
   - **Results:**
     - Toolbar: sonuç meta + Sırala
     - **Sponsorlu mağaza ilanı** (dashed accent border) — 120px görsel + içerik (Sponsorlu chip + Mağaza ilanı chip) + "Mağazayı gör" CTA
     - **List görünüm** ProductCard'lar (10 ürün) — `120px 1fr 220px` grid
     - Pagination

---

## Interactions & Behavior

- **Header search** → Enter veya Ara butonu → `/search?q=...`
- **Kategoriler dropdown** → mega-menu ile tüm kategori ağacı (bu mock'ta yok, prod'da tasarlanmalı)
- **MainNav** kategori linkleri → ilgili kategori sayfası
- **ProductCard click** → ürün detay
- **ProductCard "Sepete ekle"** → en ucuz ilanı sepete ekler (UX: in-card spinner → success state, 2sn sonra geri normale)
- **Ürün detay → ListingRow Sepet** → o ilanı sepete ekler
- **Sırala dropdown** → query string `?sort=lowest_price` (default: `lowest_price`)
- **Filter checkbox** → query string sync, toolbar'daki "847 sonuç" canlı güncellenir
- **Aktif filtre chip × tıklaması** → o filtreyi kaldırır
- **Pagination** → `?page=N`
- **Grid/List toggle** → kullanıcı tercihini localStorage'a kaydet (`ik:cardLayout`)
- **Stock progress bar** — Günün Fırsatı'nda %30 dolu, gradient (warning → accent)
- **Live "Canlı güncellemeler" pulse** — pure CSS animation: `@keyframes pulse { 0%,100% { box-shadow: 0 0 0 0 success; } 50% { box-shadow: 0 0 0 4px transparent; } }`
- **Hover states:**
  - Buttons: `--accent` → `--accent-hover`, ghost → `bg-muted`
  - Cards: `border-color: var(--border)` → `var(--border-strong)`
  - Brand strip: cell bg → `--bg-muted`
- **Focus ring:** `outline: 2px solid var(--accent); outline-offset: 2px;`
- **Transitions:** 120ms ease for color/border/background

## State Management

- `searchQuery` (URL query string)
- `activeFilters` ({ categories: [], brands: [], priceMin, priceMax, stockOnly, fastShip, verified, vadeli, city, minRating })
- `sortBy` ("lowest_price" | "relevance" | "newest" | "price_drop" | "fast_ship")
- `cardLayout` ("grid" | "list") — localStorage
- `cartCount` (header badge — global state / store)
- `selectedListingId` (ürün detayda en ucuz default; ListingRow sepet butonu o ilanı seçer)
- `productListings` (ürün başına server-side: en düşükten yükseğe sıralı array)
- Ürün detay query'si: `Product.bySlug + Listings.byProductId(orderBy: priceAsc)`

## Responsive Behavior

Tasarım 1440px desktop için optimize edildi. Geliştirici aşağıdaki breakpoint'leri uygulamalı:

- **≥1280px**: 4-col grid, 260px sidebar
- **1024-1279px**: 3-col grid, 240px sidebar
- **768-1023px**: 2-col grid, sidebar drawer (filter butonu açar)
- **<768px (mobile)**:
  - TopBar logo + arama ikon + sepet ikon (search expand on tap)
  - MainNav horizontal-scroll
  - Hero: tek kolon (sol blok üstte, sağ visual alt veya gizli)
  - Filter: bottom sheet
  - ProductCard list layout zorla
  - ListingRow: stacked (her satır kart formatında, sütunlar wrap)

---

## Design Tokens

**Colors (light mode, default):**
```
--bg: #fafaf7
--bg-elevated: #ffffff
--bg-muted: #f3f3ee
--bg-subtle: #ebebe4
--border: #e3e3dc
--border-strong: #c9c9bf
--fg: #1a1a17
--fg-muted: #5a5a52
--fg-soft: #8a8a80
--success: #1f7a3f / soft #e6f4ec
--warning: #b76e00 / soft #fbf1e2
--danger:  #b42525 / soft #fbe9e9
```

**Accent palettes (5 seçenek, kullanıcı seçer):**
```
ocean    --accent: #2b4cb8  hover: #1f3a96  soft: #e8edfb
forest   --accent: #2f7d4a  hover: #226039  soft: #e6f3eb
amber    --accent: #b8651a  hover: #934f12  soft: #fbeede     ← DEFAULT
violet   --accent: #6647c4  hover: #4f35a1  soft: #ede8fa
graphite --accent: #1a1a17  hover: #000000  soft: #ebebe4
```

**Dark mode** (data-theme="dark"):
```
--bg: #131311  --bg-elevated: #1c1c19  --bg-muted: #232320
--border: #2f2f2b  --fg: #f1f1ec  --fg-muted: #a8a89e
```

**Typography:**
- Sans: **Inter** 400/500/600/700 (default) — alternatifler: Manrope, IBM Plex Sans
- Mono: **JetBrains Mono** 400/500/600/700 (fiyat, SKU, sayısal değerler için)
- Body: 14px / line-height 1.5
- Letter-spacing on h1-h4: -0.01em (büyük başlıklarda -0.02em)

**Spacing scale** (medium density — default):
```
--space-1: 4px   --space-2: 8px   --space-3: 12px
--space-4: 16px  --space-5: 20px  --space-6: 24px
--space-8: 32px  --space-10: 40px --space-12: 48px
--row-h: 40px (button/input height)
```

Compact mode space-3=8 / space-4=12 / row-h=32.
Airy mode space-3=14 / space-4=20 / row-h=48.

**Radius:**
```
--radius-sm: 4px
--radius:    6px   (button/input)
--radius-lg: 10px  (card)
--radius-xl: 14px  (hero)
```

**Shadows:**
```
--shadow-sm: 0 1px 2px rgba(20,20,15,0.04)
--shadow:    0 1px 3px rgba(20,20,15,0.06), 0 1px 2px rgba(20,20,15,0.04)
--shadow-lg: 0 8px 24px rgba(20,20,15,0.08), 0 2px 6px rgba(20,20,15,0.04)
```

---

## Components (reusable, geliştirici codebase'inde primitif olarak ayrı dosyalar)

| Component | Sorumluluğu | Props |
|---|---|---|
| `<Logo />` | Marka logo (text + icon kutusu) | — |
| `<UtilityBar />` | Üst utility şeridi | — |
| `<TopBar />` | Search + nav butonları | `search`, `onSearch` |
| `<MainNav />` | Kategori sekme barı | `active` (string) |
| `<ProductCard />` | Ürün kartı | `product`, `layout="grid"\|"list"`, `style="solid"\|"outline"` |
| `<ListingRow />` | Ürün detayda satıcı ilanı | `listing`, `idx`, `isBest` |
| `<PriceBlock />` | PSF + en düşük fiyat | `psf`, `lowest`, `layout`, `listingsCount` |
| `<Stars />` | Yıldızlı rating | `value`, `size` |
| `<Icon name size stroke />` | SVG ikon seti | 25 ikon (search, cart, user, bell, heart, truck, shield, star, bolt, box, flame, vb.) |
| `<Footer />` | 5-kolonlu alt footer | — |

### Utility chips
`.chip`, `.chip-accent`, `.chip-success`, `.chip-warning` — pill-shape, 11px, 3px 8px padding.

### Buttons
`.btn-primary` (accent solid), `.btn-soft` (accent-soft bg + accent fg), `.btn-ghost` (border + bg-elevated), `.btn-icon` (square), `.btn-sm` (28px), `.btn-lg` (44px).

### Form
`.input` — 40px height (medium density), focus → accent border + 3px accent-soft glow.

### Placeholder image
`.ph-image` — 45° striped repeat (`bg-muted` & `bg-subtle`), monospace upper-case label. Gerçek ürün görselleri yerleştirilince kaldırılacak.

---

## Assets

- **Fonts:** Google Fonts → Inter, JetBrains Mono (zorunlu); Manrope, IBM Plex Sans (opsiyonel tipografi tweak'i için)
- **Icons:** Hand-rolled inline SVG (24×24 viewBox, stroke-based, `currentColor`). Lucide ikonlarına çok yakın bir set — geliştirici hedef codebase'de Lucide kuruluysa direkt değiştirebilir.
- **Product images:** Yok — `.ph-image` placeholder'ları kullanılıyor. Gerçek ürün CDN'i bağlanmalı.
- **Logo:** Text-based placeholder (turuncu kare içinde "i" + "kirtasiye.b2b"). Müşteri kendi logo dosyasını sağlayacak.
- **Banner imagery:** Hero'daki büyük banner görseli ve product imagery yok — geliştirici/marketer banner setlerini Banner Yönetimi paneli üzerinden yönetilecek (i-depo'da var olan modül).

---

## Files

Bu klasör içindeki dosyalar:

| Dosya | Amacı |
|---|---|
| `i-kirtasiye.html` | Ana entry, design canvas + tweaks panel |
| `styles.css` | Tüm design token'lar (CSS variables), dark mode, density, palette anahtarları, button/chip/input |
| `data.js` | Mock data (kategoriler, markalar, ürünler, ilanlar, kampanyalar) — backend'den gelecek |
| `components.jsx` | Paylaşılan komponentler (Icon, TopBar, MainNav, ProductCard, ListingRow, Footer, vb.) |
| `page-market.jsx` | Market (anasayfa) sayfası |
| `page-category.jsx` | Kategori sayfası |
| `page-detail.jsx` | Ürün detay sayfası |
| `page-search.jsx` | Arama sonuçları sayfası |
| `design-canvas.jsx` | 4 sayfayı yan yana sunan canvas (sadece prototip için, prod'a gitmeyecek) |
| `tweaks-panel.jsx` | Live tweak panel (sadece prototip için) |

---

## Backend Integration Notes

i-depo'nun backend yapısı korunacak. Frontend'in beklediği endpoint'ler:

- `GET /api/categories` → `[{ id, name, slug, count, parentId, icon }]`
- `GET /api/categories/:slug/products` (filtre + sort + page) → `{ products: [...], total, facets: { brands, prices, ... } }`
- `GET /api/products/:slug` → `{ product, listings: [{seller, city, rating, reviews, price, stock, ship, verified, fastShip}] }` (ilanlar fiyat artan)
- `GET /api/search?q=&filters=&sort=&page=` → arama sonuçları + ilgili aramalar + facet'ler
- `GET /api/banners?placement=hero|secondary` → hero ve promo banner'ları (i-depo'da var olan Banner Yönetimi modülünden)
- `POST /api/cart/items` → ilan ID + adet
- `GET /api/products/:slug/recent-sales` → "Son Satılanlar" feed (websocket veya 30sn polling)

## Implementation Checklist (developer)

- [ ] Design token'ları codebase'in mevcut theme sistemine adapte et (CSS variables veya design system tokens)
- [ ] Inter + JetBrains Mono font yüklemesini globale ekle
- [ ] 5 palette switcher'ı ayarlar sayfasına bağla (kullanıcı tercihi DB'de saklanır)
- [ ] Dark mode sistem ayarına göre veya kullanıcı seçimine göre toggle
- [ ] Component'leri primitif → composite hiyerarşisinde yaz (Icon → Chip/Button → ProductCard/ListingRow → Page)
- [ ] Banner Yönetimi modülünü kırtasiye için yeniden konfigüre et (placement: hero, secondary-1/2/3, sponsored-search)
- [ ] Kategori taksonomisini DB'ye yükle (eczane → kırtasiye geçiş migration)
- [ ] Search index'i ürün adı + SKU + barkod + marka üzerinden kur
- [ ] Listings query'sinde fiyat artan default sıralama, pagination 20'şer
- [ ] Responsive breakpoint'leri uygula (≥1280 / 1024-1279 / 768-1023 / <768)
- [ ] Accessibility: focus ring, aria-label, semantic landmark roles (`<header>`, `<nav>`, `<main>`, `<aside>`, `<footer>`)
