// Product detail page — ilan listesi vurgulu
const ProductDetailPage = () => {
  const { listings, products } = window.IKData;
  const product = products[0]; // Faber-Castell 9000

  return (
    <div className="page" style={{ minHeight: "100%" }}>
      <UtilityBar />
      <TopBar />
      <MainNav active="Kalem & Yazı" />

      {/* Breadcrumb */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px 0" }}>
        <div style={{ fontSize: 12, color: "var(--fg-muted)" }}>
          <a href="#">Anasayfa</a> · <a href="#">Kalem & Yazı</a> · <a href="#">Kurşun Kalem</a> · <span style={{ color: "var(--fg)" }}>Faber-Castell 9000</span>
        </div>
      </div>

      {/* Top: image + summary */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "16px 24px 0", display: "grid", gridTemplateColumns: "1fr 1fr", gap: 32 }}>
        {/* Gallery */}
        <div>
          <div className="ph-image" style={{ aspectRatio: "1 / 1", borderRadius: "var(--radius-lg)", fontSize: 14 }}>
            FABER-CASTELL · ÜRÜN GÖRSELİ
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(5, 1fr)", gap: 8, marginTop: 8 }}>
            {[1, 2, 3, 4, 5].map((i) => (
              <div key={i} className="ph-image" style={{
                aspectRatio: "1 / 1",
                border: i === 1 ? "2px solid var(--accent)" : "1px solid var(--border)",
                fontSize: 9,
              }}>{i === 5 ? "+3" : ""}</div>
            ))}
          </div>
        </div>

        {/* Summary */}
        <div style={{ display: "flex", flexDirection: "column", gap: 14 }}>
          <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
            <span className="chip chip-accent">{product.tag}</span>
            <span className="mono" style={{ fontSize: 11, color: "var(--fg-soft)" }}>SKU {product.sku}</span>
          </div>
          <h1 style={{ fontSize: 26, lineHeight: 1.2, textWrap: "balance" }}>{product.name}</h1>
          <div style={{ display: "flex", gap: 12, alignItems: "center", fontSize: 13, color: "var(--fg-muted)" }}>
            <span>Marka: <a href="#" style={{ color: "var(--accent)", fontWeight: 600 }}>{product.brand}</a></span>
            <span style={{ color: "var(--border-strong)" }}>|</span>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
              <Stars value={4.7} /> 4.7 · <span className="mono">1.842</span> değerlendirme
            </span>
          </div>

          {/* Price hero */}
          <div style={{
            background: "var(--bg-elevated)",
            border: "1px solid var(--border)",
            borderRadius: "var(--radius-lg)",
            padding: 20,
            display: "flex", flexDirection: "column", gap: 12,
          }}>
            <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline" }}>
              <div>
                <div style={{ fontSize: 11, color: "var(--fg-soft)", textTransform: "uppercase", letterSpacing: 0.06 }}>PSF (Önerilen Satış)</div>
                <div className="mono" style={{ fontSize: 16, color: "var(--fg-muted)", textDecoration: "line-through" }}>{formatTL(product.psf)}</div>
              </div>
              <div style={{ textAlign: "right" }}>
                <div style={{ fontSize: 11, color: "var(--success)", fontWeight: 600, textTransform: "uppercase", letterSpacing: 0.06 }}>En düşük ilan fiyatı</div>
                <div className="mono" style={{ fontSize: 32, fontWeight: 700 }}>{formatTL(product.lowest)}</div>
                <div style={{ fontSize: 11, color: "var(--fg-soft)", textAlign: "right" }}>KDV dahil · adet başı</div>
              </div>
            </div>

            <div style={{ display: "flex", gap: 12, alignItems: "center", padding: "10px 12px", background: "var(--accent-soft)", borderRadius: "var(--radius)", color: "var(--accent)", fontSize: 12 }}>
              <Icon name="bolt" size={14} />
              <span>
                <strong>{product.listingsCount} satıcı</strong> bu ürünü listeliyor — fiyat aralığı{" "}
                <span className="mono">{formatTL(product.lowest)}</span> –{" "}
                <span className="mono">{formatTL(product.lowest * 1.42)}</span>
              </span>
            </div>

            {/* Quantity + cart */}
            <div style={{ display: "flex", gap: 10, alignItems: "stretch" }}>
              <div style={{ display: "flex", alignItems: "center", border: "1px solid var(--border)", borderRadius: "var(--radius)", overflow: "hidden" }}>
                <button style={{ padding: "0 12px", height: 44 }}><Icon name="minus" size={14} /></button>
                <input className="mono" defaultValue="1" style={{ width: 56, textAlign: "center", border: "none", height: 44, fontSize: 14, background: "transparent", outline: "none" }} />
                <button style={{ padding: "0 12px", height: 44 }}><Icon name="plus" size={14} /></button>
              </div>
              <button className="btn btn-primary btn-lg" style={{ flex: 1 }}>
                <Icon name="cart" size={16} /> En ucuz ilanı sepete ekle
              </button>
              <button className="btn btn-ghost btn-lg" title="Favorilere ekle">
                <Icon name="heart" size={16} />
              </button>
            </div>

            <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 10, fontSize: 12, color: "var(--fg-muted)", marginTop: 4 }}>
              <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                <Icon name="truck" size={13} /> 1-2 günde teslim
              </span>
              <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                <Icon name="shield" size={13} /> Onaylı satıcı garantisi
              </span>
              <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
                <Icon name="bolt" size={13} /> Vadeli ödeme
              </span>
            </div>
          </div>

          {/* Specs */}
          <div className="card" style={{ padding: 16 }}>
            <div className="eyebrow" style={{ marginBottom: 10 }}>Ürün özellikleri</div>
            <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: "10px 24px", fontSize: 13 }}>
              {[
                ["Marka", "Faber-Castell"],
                ["Seri", "9000 Graphite"],
                ["Sertlik", "HB / 2B / 4B karışık"],
                ["Adet", "12'li set"],
                ["Menşei", "Almanya"],
                ["Barkod", "4005401190127"],
              ].map(([k, v]) => (
                <div key={k} style={{ display: "flex", justifyContent: "space-between", paddingBottom: 6, borderBottom: "1px dashed var(--border)" }}>
                  <span style={{ color: "var(--fg-soft)" }}>{k}</span>
                  <span style={{ fontWeight: 500 }}>{v}</span>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* LISTINGS — focus area */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "40px 24px 0" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", marginBottom: 16, gap: 16 }}>
          <div>
            <div className="eyebrow">Tüm satıcı ilanları</div>
            <h2 style={{ fontSize: 22, marginTop: 4 }}>
              {product.listingsCount} satıcının ilanı — en düşük fiyattan sıralı
            </h2>
          </div>
          <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
            <span style={{ fontSize: 12, color: "var(--fg-soft)" }}>Filtrele:</span>
            <button className="btn btn-soft btn-sm">Tümü</button>
            <button className="btn btn-ghost btn-sm"><Icon name="check" size={11} /> Onaylı</button>
            <button className="btn btn-ghost btn-sm"><Icon name="bolt" size={11} /> Hızlı kargo</button>
            <button className="btn btn-ghost btn-sm"><Icon name="pin" size={11} /> Şehrim</button>
          </div>
        </div>

        {/* Header row */}
        <div style={{
          display: "grid",
          gridTemplateColumns: "44px 1fr 140px 140px 140px 140px",
          gap: 12,
          padding: "8px 16px",
          fontSize: 11,
          color: "var(--fg-soft)",
          textTransform: "uppercase",
          letterSpacing: 0.05,
          fontWeight: 600,
        }}>
          <span>#</span>
          <span>Satıcı</span>
          <span>Stok</span>
          <span>Teslimat</span>
          <span style={{ textAlign: "right" }}>Fiyat</span>
          <span style={{ textAlign: "right" }}>İşlem</span>
        </div>

        <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
          {listings.map((l, i) => (
            <ListingRow key={i} listing={l} idx={i} isBest={i === 0} />
          ))}
        </div>

        <div style={{ display: "flex", justifyContent: "center", marginTop: 20 }}>
          <button className="btn btn-ghost">
            Daha fazla ilan göster <Icon name="down" size={13} />
          </button>
        </div>
      </div>

      {/* Similar products */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "48px 24px 0" }}>
        <h2 style={{ fontSize: 20, marginBottom: 16 }}>Benzer ürünler</h2>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 16 }}>
          {products.slice(1, 5).map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      </div>

      <Footer />
    </div>
  );
};

window.ProductDetailPage = ProductDetailPage;
