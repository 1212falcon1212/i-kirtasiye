// Mock data for i-kirtasiye B2B pages
window.IKData = (function () {
  const categories = [
    { id: "defter", name: "Defter & Bloknot", count: 1248, icon: "📓" },
    { id: "kalem", name: "Kalem & Yazı", count: 2341, icon: "✏️" },
    { id: "ofis", name: "Ofis Malzemeleri", count: 1876, icon: "📎" },
    { id: "okul", name: "Okul Malzemeleri", count: 942, icon: "🎒" },
    { id: "sanat", name: "Sanat & Hobi", count: 1124, icon: "🎨" },
    { id: "kagit", name: "Kağıt Ürünleri", count: 689, icon: "📄" },
    { id: "dosya", name: "Dosyalama & Arşiv", count: 514, icon: "🗂️" },
    { id: "hesap", name: "Hesap Makineleri", count: 187, icon: "🔢" },
    { id: "boya", name: "Boya & Marker", count: 832, icon: "🖍️" },
    { id: "yapistirici", name: "Yapıştırıcı & Bant", count: 421, icon: "🩹" },
    { id: "cizim", name: "Çizim Aletleri", count: 396, icon: "📐" },
    { id: "elektronik", name: "Ofis Elektroniği", count: 274, icon: "🖨️" },
  ];

  const brands = [
    "Faber-Castell", "Pilot", "Stabilo", "Bic", "Pelikan",
    "Mas", "Adel", "Pentel", "Maped", "Staedtler",
    "Uni", "Tombow", "Edding", "Schneider", "Rotring",
  ];

  const products = [
    {
      id: "p1", sku: "FC-9000-12",
      name: "Faber-Castell 9000 Kurşun Kalem 12'li Set",
      brand: "Faber-Castell",
      category: "Kalem & Yazı",
      psf: 248.00,
      lowest: 184.50,
      listingsCount: 14,
      tag: "Çok satan",
    },
    {
      id: "p2", sku: "PIL-G2-07-BLK",
      name: "Pilot G2 0.7mm Jel Kalem Siyah 12'li Kutu",
      brand: "Pilot",
      category: "Kalem & Yazı",
      psf: 612.00,
      lowest: 484.00,
      listingsCount: 22,
      tag: "Stokta",
    },
    {
      id: "p3", sku: "MAS-A4-80-100",
      name: "Mas A4 80gr Fotokopi Kağıdı 500'lü 5 Top",
      brand: "Mas",
      category: "Kağıt Ürünleri",
      psf: 1284.00,
      lowest: 1098.50,
      listingsCount: 31,
      tag: "Yeni fiyat",
    },
    {
      id: "p4", sku: "STB-BOSS-08",
      name: "Stabilo Boss Original Fosforlu Kalem 8'li Set",
      brand: "Stabilo",
      category: "Boya & Marker",
      psf: 386.00,
      lowest: 312.00,
      listingsCount: 18,
      tag: "Toptan",
    },
    {
      id: "p5", sku: "ADL-DFT-A4-200",
      name: "Adel Spiralli Defter A4 200 Yaprak Kareli",
      brand: "Adel",
      category: "Defter & Bloknot",
      psf: 178.00,
      lowest: 142.00,
      listingsCount: 9,
      tag: "Stokta",
    },
    {
      id: "p6", sku: "MPD-OK-SET-24",
      name: "Maped Color Peps Kuru Boya 24 Renk",
      brand: "Maped",
      category: "Sanat & Hobi",
      psf: 312.00,
      lowest: 248.00,
      listingsCount: 12,
      tag: "Stokta",
    },
    {
      id: "p7", sku: "BIC-CRY-50",
      name: "Bic Cristal Tükenmez Kalem Mavi 50'li Kutu",
      brand: "Bic",
      category: "Kalem & Yazı",
      psf: 824.00,
      lowest: 678.00,
      listingsCount: 27,
      tag: "Çok satan",
    },
    {
      id: "p8", sku: "PEL-CIZ-SET",
      name: "Pelikan Çizim Pergeli Profesyonel Set",
      brand: "Pelikan",
      category: "Çizim Aletleri",
      psf: 524.00,
      lowest: 442.00,
      listingsCount: 6,
      tag: "Az stok",
    },
  ];

  // Listings (offers) for the focused product on detail page
  const listings = [
    { seller: "Anadolu Kırtasiye Toptan", city: "İstanbul", rating: 4.8, reviews: 2341, price: 184.50, stock: 1240, ship: "1-2 gün", verified: true, fastShip: true },
    { seller: "Mavi Defter Dağıtım", city: "Ankara", rating: 4.7, reviews: 1502, price: 187.00, stock: 860, ship: "2-3 gün", verified: true, fastShip: false },
    { seller: "Akademi Ofis Ltd.", city: "İzmir", rating: 4.9, reviews: 3120, price: 189.50, stock: 2100, ship: "1 gün", verified: true, fastShip: true },
    { seller: "Star Kırtasiye Bayi", city: "Bursa", rating: 4.5, reviews: 624, price: 192.00, stock: 320, ship: "2-3 gün", verified: false, fastShip: false },
    { seller: "Ege Kağıtçılık", city: "İzmir", rating: 4.6, reviews: 982, price: 194.00, stock: 540, ship: "2 gün", verified: true, fastShip: false },
    { seller: "Karadeniz Toptan", city: "Trabzon", rating: 4.4, reviews: 410, price: 198.50, stock: 180, ship: "3-4 gün", verified: false, fastShip: false },
    { seller: "Pratik Ofis Çözümleri", city: "İstanbul", rating: 4.7, reviews: 1822, price: 201.00, stock: 920, ship: "1-2 gün", verified: true, fastShip: true },
    { seller: "Merkez Kırtasiye A.Ş.", city: "Ankara", rating: 4.8, reviews: 2104, price: 204.50, stock: 1450, ship: "2 gün", verified: true, fastShip: false },
  ];

  const campaigns = [
    { title: "Okula Dönüş Kampanyası", subtitle: "Defter ve yazı grubunda %25'e varan toptan indirim", cta: "Kampanyaya git", tone: "accent" },
    { title: "Hızlı teslimat ilanları", subtitle: "İstanbul, Ankara, İzmir → 24 saat içinde", cta: "İlanları gör", tone: "neutral" },
    { title: "Yeni satıcılar", subtitle: "Bu hafta 38 yeni toptancı katıldı", cta: "Keşfet", tone: "muted" },
  ];

  return { categories, brands, products, listings, campaigns };
})();
