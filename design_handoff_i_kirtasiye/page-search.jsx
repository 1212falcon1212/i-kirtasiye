// Search results page
const SearchPage = () => {
  const { products } = window.IKData;
  const items = [...products, ...products].slice(0, 10);
  const query = "kurşun kalem";

  return (
    <div className="page" style={{ minHeight: "100%" }}>
      <UtilityBar />
      <TopBar search={query} />
      <MainNav />

      {/* Search header */}
      <div style={{ background: "var(--bg-elevated)", borderBottom: "1px solid var(--border)" }}>
        <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px" }}>
          <div style={{ fontSize: 12, color: "var(--fg-muted)", marginBottom: 6 }}>Arama sonuçları</div>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", flexWrap: "wrap", gap: 12 }}>
            <h1 style={{ fontSize: 24 }}>
              "<span style={{ color: "var(--accent)" }}>{query}</span>" için sonuçlar
            </h1>
            <div style={{ fontSize: 13, color: "var(--fg-muted)" }}>
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>847</span> ürün ·{" "}
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>6.124</span> ilan ·{" "}
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>0.18</span> sn
            </div>
          </div>

          {/* Did you mean / suggestions */}
          <div style={{ display: "flex", gap: 8, marginTop: 14, flexWrap: "wrap" }}>
            <span style={{ fontSize: 12, color: "var(--fg-soft)", padding: "6px 0" }}>İlgili aramalar:</span>
            {["kurşun kalem 12'li", "Faber-Castell kurşun", "kurşun kalem HB", "kurşun kalem set", "kurşun kalem yazılı", "kurşun kalem ucuz"].map((s) => (
              <button key={s} className="chip" style={{ padding: "6px 12px", fontSize: 12, cursor: "pointer" }}>
                <Icon name="search" size={11} /> {s}
              </button>
            ))}
          </div>
        </div>
      </div>

      {/* Categories pivot */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px 0" }}>
        <div className="eyebrow" style={{ marginBottom: 10 }}>Kategorilere göre filtrele</div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(5, 1fr)", gap: 10 }}>
          {[
            { name: "Kurşun Kalem", count: 412, accent: true },
            { name: "Set & Kutu", count: 184 },
            { name: "Mekanik Kalem", count: 142 },
            { name: "Kalemtraş", count: 89 },
            { name: "Silgi", count: 78 },
          ].map((c) => (
            <a key={c.name} href="#" style={{
              padding: "12px 14px",
              background: c.accent ? "var(--accent-soft)" : "var(--bg-elevated)",
              border: c.accent ? "1px solid var(--accent)" : "1px solid var(--border)",
              borderRadius: "var(--radius)",
              display: "flex", justifyContent: "space-between", alignItems: "center",
            }}>
              <span style={{ fontSize: 13, fontWeight: 500, color: c.accent ? "var(--accent)" : "var(--fg)" }}>
                {c.name}
              </span>
              <span className="mono" style={{ fontSize: 11, color: c.accent ? "var(--accent)" : "var(--fg-soft)" }}>
                {c.count}
              </span>
            </a>
          ))}
        </div>
      </div>

      {/* Main grid w/ compact left filters */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px 0", display: "grid", gridTemplateColumns: "240px 1fr", gap: 24 }}>
        <aside style={{
          background: "var(--bg-elevated)",
          border: "1px solid var(--border)",
          borderRadius: "var(--radius-lg)",
          padding: "16px",
          alignSelf: "start",
        }}>
          <div style={{ fontSize: 14, fontWeight: 600, marginBottom: 12, display: "inline-flex", alignItems: "center", gap: 6 }}>
            <Icon name="sliders" size={14} /> Daralt
          </div>

          {[
            { title: "Marka", items: [["Faber-Castell", 124], ["Pilot", 92], ["Stabilo", 64], ["Bic", 88]] },
            { title: "Sertlik", items: [["HB", 312], ["2B", 184], ["4B", 92], ["6B", 48]] },
            { title: "Adet", items: [["Tekli", 240], ["6'lı", 124], ["12'li", 318], ["24'lü", 86]] },
          ].map((sec) => (
            <div key={sec.title} style={{ borderTop: "1px solid var(--border)", padding: "12px 0" }}>
              <div style={{ fontSize: 12, fontWeight: 600, marginBottom: 8, color: "var(--fg-muted)" }}>{sec.title}</div>
              <div style={{ display: "flex", flexDirection: "column", gap: 6 }}>
                {sec.items.map(([n, c], i) => (
                  <label key={n} style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13 }}>
                    <input type="checkbox" defaultChecked={sec.title === "Marka" && i === 0} />
                    <span style={{ flex: 1 }}>{n}</span>
                    <span className="mono" style={{ fontSize: 11, color: "var(--fg-soft)" }}>{c}</span>
                  </label>
                ))}
              </div>
            </div>
          ))}
        </aside>

        <div>
          {/* Toolbar */}
          <div style={{
            display: "flex", justifyContent: "space-between", alignItems: "center",
            padding: "10px 14px",
            background: "var(--bg-elevated)",
            border: "1px solid var(--border)",
            borderRadius: "var(--radius-lg)",
            marginBottom: 14,
          }}>
            <div style={{ fontSize: 13, color: "var(--fg-muted)" }}>
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>10</span> /{" "}
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>847</span> sonuç gösteriliyor
            </div>
            <div style={{ display: "flex", gap: 8 }}>
              <button className="btn btn-ghost btn-sm">İlgililik <Icon name="down" size={11} /></button>
              <button className="btn btn-ghost btn-sm">En düşük fiyat</button>
            </div>
          </div>

          {/* Banner result — sponsored / promoted */}
          <div style={{
            background: "var(--bg-elevated)",
            border: "1px dashed var(--accent)",
            borderRadius: "var(--radius-lg)",
            padding: 16,
            marginBottom: 14,
            display: "grid",
            gridTemplateColumns: "120px 1fr auto",
            gap: 16, alignItems: "center",
          }}>
            <div className="ph-image" style={{ aspectRatio: "1 / 1", width: 120 }}>FC</div>
            <div>
              <div style={{ display: "flex", gap: 6, marginBottom: 4 }}>
                <span className="chip chip-warning">Sponsorlu</span>
                <span className="chip chip-accent">Mağaza ilanı</span>
              </div>
              <div style={{ fontSize: 15, fontWeight: 600, marginBottom: 4 }}>
                Faber-Castell Toptan Mağaza — Tüm kurşun kalem serisi
              </div>
              <div style={{ fontSize: 12, color: "var(--fg-muted)" }}>
                12 farklı modelde toptan fiyat · 3.000+ adet stok · İstanbul depodan 24 saatte teslim
              </div>
            </div>
            <button className="btn btn-primary btn-sm">Mağazayı gör</button>
          </div>

          {/* Results — list layout (search context) */}
          <div style={{ display: "flex", flexDirection: "column", gap: 10 }}>
            {items.map((p, i) => (
              <ProductCard key={i} product={p} layout="list" />
            ))}
          </div>

          {/* Pagination */}
          <div style={{ display: "flex", justifyContent: "center", gap: 4, marginTop: 28 }}>
            <button className="btn btn-ghost btn-sm">Önceki</button>
            {[1, 2, 3, 4, 5].map((p) => (
              <button key={p} className={p === 1 ? "btn btn-primary btn-sm" : "btn btn-ghost btn-sm"} style={{ minWidth: 32 }}>{p}</button>
            ))}
            <button className="btn btn-ghost btn-sm">Sonraki</button>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
};

window.SearchPage = SearchPage;
