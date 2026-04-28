// Category page — Sol panel filtre + grid
const CategoryPage = () => {
  const { products } = window.IKData;
  // duplicate products to make a full grid feel
  const items = [...products, ...products, ...products].slice(0, 18);

  const FilterSection = ({ title, children, defaultOpen = true }) => {
    const [open, setOpen] = React.useState(defaultOpen);
    return (
      <div style={{ borderBottom: "1px solid var(--border)", padding: "14px 0" }}>
        <button onClick={() => setOpen(!open)} style={{
          width: "100%", display: "flex", justifyContent: "space-between",
          alignItems: "center", fontSize: 13, fontWeight: 600,
        }}>
          {title}
          <span style={{ transform: open ? "rotate(180deg)" : "none", color: "var(--fg-soft)" }}>
            <Icon name="down" size={14} />
          </span>
        </button>
        {open && <div style={{ marginTop: 12, display: "flex", flexDirection: "column", gap: 8 }}>{children}</div>}
      </div>
    );
  };

  const Check = ({ label, count, checked }) => (
    <label style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13, cursor: "pointer" }}>
      <span style={{
        width: 16, height: 16, borderRadius: 4,
        border: `1.5px solid ${checked ? "var(--accent)" : "var(--border-strong)"}`,
        background: checked ? "var(--accent)" : "transparent",
        display: "flex", alignItems: "center", justifyContent: "center",
        color: "var(--accent-fg)",
      }}>
        {checked && <Icon name="check" size={11} stroke={2.4} />}
      </span>
      <span style={{ flex: 1, color: "var(--fg)" }}>{label}</span>
      <span className="mono" style={{ fontSize: 11, color: "var(--fg-soft)" }}>{count}</span>
    </label>
  );

  return (
    <div className="page" style={{ minHeight: "100%" }}>
      <UtilityBar />
      <TopBar />
      <MainNav active="Kalem & Yazı" />

      {/* Breadcrumb + heading */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px 0" }}>
        <div style={{ fontSize: 12, color: "var(--fg-muted)", marginBottom: 8 }}>
          <a href="#">Anasayfa</a> · <a href="#">Tüm kategoriler</a> · <span style={{ color: "var(--fg)" }}>Kalem & Yazı</span>
        </div>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", gap: 16 }}>
          <div>
            <h1 style={{ fontSize: 28 }}>Kalem & Yazı</h1>
            <p style={{ marginTop: 4, fontSize: 13, color: "var(--fg-muted)" }}>
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>2.341</span> ürün ·
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}> 18.520</span> aktif ilan ·
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}> 412</span> satıcı
            </p>
          </div>
        </div>
      </div>

      {/* Main layout */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "16px 24px 0", display: "grid", gridTemplateColumns: "260px 1fr", gap: 24 }}>
        {/* Filters */}
        <aside style={{
          background: "var(--bg-elevated)",
          border: "1px solid var(--border)",
          borderRadius: "var(--radius-lg)",
          padding: "0 16px",
          alignSelf: "start",
          position: "sticky", top: 12,
        }}>
          <div style={{ padding: "16px 0 8px", borderBottom: "1px solid var(--border)", display: "flex", justifyContent: "space-between", alignItems: "center" }}>
            <span style={{ fontSize: 14, fontWeight: 600, display: "inline-flex", alignItems: "center", gap: 8 }}>
              <Icon name="sliders" size={14} /> Filtreler
            </span>
            <button style={{ fontSize: 11, color: "var(--accent)", fontWeight: 500 }}>Temizle</button>
          </div>

          {/* Active filters */}
          <div style={{ padding: "12px 0", borderBottom: "1px solid var(--border)", display: "flex", flexWrap: "wrap", gap: 6 }}>
            {["Faber-Castell", "Stokta var", "100₺ - 500₺"].map((f) => (
              <span key={f} className="chip chip-accent" style={{ paddingRight: 4 }}>
                {f}
                <button style={{ display: "inline-flex", padding: 0, color: "var(--accent)" }}>
                  <Icon name="close" size={10} />
                </button>
              </span>
            ))}
          </div>

          <FilterSection title="Alt kategori">
            <Check label="Kurşun kalem" count={284} checked />
            <Check label="Tükenmez kalem" count={412} />
            <Check label="Jel kalem" count={326} />
            <Check label="Roller kalem" count={198} />
            <Check label="Fosforlu kalem" count={156} />
            <button style={{ fontSize: 12, color: "var(--accent)", textAlign: "left", marginTop: 4 }}>
              + 8 alt kategori
            </button>
          </FilterSection>

          <FilterSection title="Marka">
            <Check label="Faber-Castell" count={386} checked />
            <Check label="Pilot" count={241} />
            <Check label="Stabilo" count={198} />
            <Check label="Bic" count={324} />
            <Check label="Pelikan" count={142} />
            <button style={{ fontSize: 12, color: "var(--accent)", textAlign: "left", marginTop: 4 }}>
              + 22 marka
            </button>
          </FilterSection>

          <FilterSection title="Fiyat aralığı (₺)">
            <div style={{ display: "flex", gap: 6 }}>
              <input className="input" placeholder="Min" defaultValue="100" style={{ height: 32, fontSize: 12 }} />
              <input className="input" placeholder="Max" defaultValue="500" style={{ height: 32, fontSize: 12 }} />
            </div>
            <div style={{ position: "relative", height: 4, background: "var(--bg-muted)", borderRadius: 2, marginTop: 8 }}>
              <div style={{ position: "absolute", left: "10%", right: "40%", height: "100%", background: "var(--accent)", borderRadius: 2 }} />
              <div style={{ position: "absolute", left: "10%", top: -4, width: 12, height: 12, background: "var(--accent)", borderRadius: 999 }} />
              <div style={{ position: "absolute", left: "60%", top: -4, width: 12, height: 12, background: "var(--accent)", borderRadius: 999 }} />
            </div>
          </FilterSection>

          <FilterSection title="İlan durumu">
            <Check label="Stokta var" count={1842} checked />
            <Check label="Hızlı kargo" count={624} />
            <Check label="Onaylı satıcı" count={1102} />
            <Check label="Vadeli alışveriş" count={428} />
          </FilterSection>

          <FilterSection title="Satıcı şehri" defaultOpen={false}>
            <Check label="İstanbul" count={842} />
            <Check label="Ankara" count={412} />
            <Check label="İzmir" count={324} />
          </FilterSection>

          <FilterSection title="Min. satıcı puanı" defaultOpen={false}>
            {[5, 4, 3].map((r) => (
              <label key={r} style={{ display: "flex", alignItems: "center", gap: 8, fontSize: 13 }}>
                <input type="radio" name="rating" defaultChecked={r === 4} />
                <Stars value={r} /> ve üzeri
              </label>
            ))}
          </FilterSection>
        </aside>

        {/* Results */}
        <div>
          {/* Toolbar */}
          <div style={{
            display: "flex", justifyContent: "space-between", alignItems: "center", gap: 12,
            padding: "10px 14px",
            background: "var(--bg-elevated)",
            border: "1px solid var(--border)",
            borderRadius: "var(--radius-lg)",
            marginBottom: 16,
          }}>
            <div style={{ fontSize: 13, color: "var(--fg-muted)" }}>
              <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>2.341</span> sonuç ·
              sayfa <span className="mono" style={{ color: "var(--fg)" }}>1 / 47</span>
            </div>
            <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
              <span style={{ fontSize: 12, color: "var(--fg-soft)" }}>Sırala:</span>
              <button className="btn btn-ghost btn-sm">
                En düşük fiyat <Icon name="down" size={12} />
              </button>
              <div style={{ width: 1, height: 20, background: "var(--border)" }} />
              <div style={{ display: "flex", border: "1px solid var(--border)", borderRadius: "var(--radius)" }}>
                <button style={{ padding: "6px 10px", color: "var(--accent)", background: "var(--accent-soft)", borderRadius: "var(--radius) 0 0 var(--radius)" }}>
                  <Icon name="grid" size={14} />
                </button>
                <button style={{ padding: "6px 10px", color: "var(--fg-muted)" }}>
                  <Icon name="list" size={14} />
                </button>
              </div>
            </div>
          </div>

          {/* Grid */}
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 14 }}>
            {items.map((p, i) => (
              <ProductCard key={i} product={p} />
            ))}
          </div>

          {/* Pagination */}
          <div style={{ display: "flex", justifyContent: "center", gap: 4, marginTop: 32 }}>
            <button className="btn btn-ghost btn-sm">Önceki</button>
            {[1, 2, 3, 4, 5].map((p) => (
              <button key={p} className={p === 1 ? "btn btn-primary btn-sm" : "btn btn-ghost btn-sm"} style={{ minWidth: 32 }}>{p}</button>
            ))}
            <span style={{ padding: "6px 8px", color: "var(--fg-soft)" }}>…</span>
            <button className="btn btn-ghost btn-sm" style={{ minWidth: 32 }}>47</button>
            <button className="btn btn-ghost btn-sm">Sonraki</button>
          </div>
        </div>
      </div>

      <Footer />
    </div>
  );
};

window.CategoryPage = CategoryPage;
