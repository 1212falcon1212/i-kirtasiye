// Shared components for i-kirtasiye pages
// Exposes: Icon, Logo, TopBar, MainNav, CategoryRail, ProductCard, ListingRow, Footer, Stars, formatTL, etc.

const formatTL = (n) =>
  new Intl.NumberFormat("tr-TR", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n) + " ₺";

const Icon = ({ name, size = 16, stroke = 1.6 }) => {
  const paths = {
    search: <><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></>,
    cart: <><path d="M3 4h2l2.5 11.5a2 2 0 0 0 2 1.5h7.5a2 2 0 0 0 2-1.5L21 8H6" /><circle cx="9.5" cy="20" r="1.2" /><circle cx="17.5" cy="20" r="1.2" /></>,
    user: <><circle cx="12" cy="8" r="4" /><path d="M4 21c0-4 4-6 8-6s8 2 8 6" /></>,
    bell: <><path d="M6 8a6 6 0 0 1 12 0v5l1.5 3h-15L6 13z" /><path d="M10 19a2 2 0 0 0 4 0" /></>,
    heart: <path d="M12 20s-7-4.5-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.5-7 10-7 10z" />,
    chevron: <path d="M9 6l6 6-6 6" />,
    down: <path d="M6 9l6 6 6-6" />,
    grid: <><rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /></>,
    list: <><path d="M3 6h18M3 12h18M3 18h18" /></>,
    truck: <><rect x="2" y="7" width="13" height="10" /><path d="M15 10h4l3 3v4h-7" /><circle cx="6" cy="18" r="2" /><circle cx="18" cy="18" r="2" /></>,
    shield: <path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z" />,
    star: <path d="M12 3l2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9z" />,
    plus: <><path d="M12 5v14M5 12h14" /></>,
    minus: <path d="M5 12h14" />,
    close: <path d="M6 6l12 12M18 6L6 18" />,
    filter: <path d="M4 5h16M7 12h10M10 19h4" />,
    pin: <><path d="M12 21s-7-7-7-12a7 7 0 1 1 14 0c0 5-7 12-7 12z" /><circle cx="12" cy="9" r="2.5" /></>,
    check: <path d="M5 12l4 4 10-10" />,
    bolt: <path d="M13 2L4 14h7l-1 8 9-12h-7z" />,
    box: <><path d="M3 7l9-4 9 4v10l-9 4-9-4z" /><path d="M3 7l9 4 9-4M12 11v10" /></>,
    flame: <path d="M12 3c2 4 5 5 5 9a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-4 1-8z" />,
    arrow: <><path d="M5 12h14M13 6l6 6-6 6" /></>,
    menu: <path d="M3 6h18M3 12h18M3 18h18" />,
    sliders: <><path d="M4 6h12M4 12h6M4 18h16" /><circle cx="18" cy="6" r="2" /><circle cx="14" cy="12" r="2" /></>,
  };
  return (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none" stroke="currentColor"
      strokeWidth={stroke} strokeLinecap="round" strokeLinejoin="round">
      {paths[name] || null}
    </svg>
  );
};

const Stars = ({ value, size = 12 }) => {
  // simple star rating - filled vs muted
  return (
    <span style={{ display: "inline-flex", gap: 1, color: "var(--warning)" }}>
      {[1, 2, 3, 4, 5].map((i) => (
        <span key={i} style={{ opacity: i <= Math.round(value) ? 1 : 0.25 }}>
          <Icon name="star" size={size} />
        </span>
      ))}
    </span>
  );
};

const Logo = () => (
  <a href="#" style={{ display: "inline-flex", alignItems: "center", gap: 10 }}>
    <span style={{
      display: "inline-flex", alignItems: "center", justifyContent: "center",
      width: 32, height: 32, borderRadius: 8,
      background: "var(--accent)", color: "var(--accent-fg)",
      fontFamily: "var(--font-mono)", fontWeight: 700, fontSize: 16,
    }}>i</span>
    <span style={{ fontSize: 18, fontWeight: 700, letterSpacing: "-0.02em" }}>
      kirtasiye<span style={{ color: "var(--fg-soft)", fontWeight: 500 }}>.b2b</span>
    </span>
  </a>
);

// Tiny utility bar above the main header
const UtilityBar = () => (
  <div style={{
    background: "var(--bg-muted)",
    borderBottom: "1px solid var(--border)",
    fontSize: 12,
    color: "var(--fg-muted)",
  }}>
    <div style={{
      maxWidth: 1440, margin: "0 auto", padding: "6px 24px",
      display: "flex", justifyContent: "space-between", alignItems: "center", gap: 16,
    }}>
      <div style={{ display: "flex", gap: 18, alignItems: "center" }}>
        <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
          <Icon name="truck" size={13} /> 1.500₺ üzeri kargo bedava
        </span>
        <span style={{ display: "inline-flex", alignItems: "center", gap: 6 }}>
          <Icon name="shield" size={13} /> Güvenli ödeme & vadeli alışveriş
        </span>
      </div>
      <div style={{ display: "flex", gap: 16 }}>
        <a href="#">Yardım</a>
        <a href="#">Satıcı ol</a>
        <a href="#">Kurumsal</a>
        <span style={{ color: "var(--fg-soft)" }}>TR ▾</span>
      </div>
    </div>
  </div>
);

const TopBar = ({ search = "", onSearch }) => (
  <div style={{
    background: "var(--bg-elevated)",
    borderBottom: "1px solid var(--border)",
  }}>
    <div style={{
      maxWidth: 1440, margin: "0 auto", padding: "16px 24px",
      display: "flex", alignItems: "center", gap: 24,
    }}>
      <Logo />
      {/* Category dropdown */}
      <button className="btn btn-ghost" style={{ flexShrink: 0 }}>
        <Icon name="grid" size={14} /> Kategoriler <Icon name="down" size={14} />
      </button>
      {/* Search */}
      <div style={{ flex: 1, position: "relative" }}>
        <span style={{ position: "absolute", left: 12, top: "50%", transform: "translateY(-50%)", color: "var(--fg-soft)" }}>
          <Icon name="search" />
        </span>
        <input
          className="input"
          placeholder="Ürün, marka veya SKU ile ara — örn. Faber-Castell 9000"
          defaultValue={search}
          style={{ paddingLeft: 38, height: 44, fontSize: 14 }}
        />
        <button className="btn btn-primary" style={{ position: "absolute", right: 4, top: 4, height: 36 }}>
          Ara
        </button>
      </div>
      {/* Right tools */}
      <div style={{ display: "flex", gap: 6, alignItems: "center", flexShrink: 0 }}>
        <button className="btn btn-ghost" title="Bildirimler">
          <Icon name="bell" size={16} />
        </button>
        <button className="btn btn-ghost">
          <Icon name="user" size={16} /> Hesabım
        </button>
        <button className="btn btn-primary" style={{ position: "relative" }}>
          <Icon name="cart" size={16} /> Sepet
          <span style={{
            background: "var(--accent-fg)", color: "var(--accent)",
            borderRadius: 999, padding: "1px 6px", fontSize: 11, fontWeight: 700,
          }}>4</span>
        </button>
      </div>
    </div>
  </div>
);

const MainNav = ({ active }) => {
  const items = [
    "Tüm kategoriler", "Defter & Bloknot", "Kalem & Yazı", "Ofis", "Okul",
    "Sanat & Hobi", "Kağıt", "Çizim", "Kampanyalar"
  ];
  return (
    <div style={{
      background: "var(--bg-elevated)",
      borderBottom: "1px solid var(--border)",
    }}>
      <div style={{
        maxWidth: 1440, margin: "0 auto", padding: "0 24px",
        display: "flex", alignItems: "center", gap: 4, overflowX: "auto",
      }}>
        {items.map((it) => (
          <a key={it} href="#" style={{
            padding: "12px 14px", fontSize: 13, fontWeight: 500,
            color: active === it ? "var(--accent)" : "var(--fg-muted)",
            borderBottom: active === it ? "2px solid var(--accent)" : "2px solid transparent",
            whiteSpace: "nowrap",
          }}>{it}</a>
        ))}
        <div style={{ flex: 1 }} />
        <a href="#" style={{
          padding: "12px 14px", fontSize: 13, fontWeight: 500,
          color: "var(--success)", display: "inline-flex", alignItems: "center", gap: 6,
        }}>
          <Icon name="bolt" size={14} /> 24 saatte teslim
        </a>
      </div>
    </div>
  );
};

// PSF + lowest price block — used in product cards and detail page
const PriceBlock = ({ psf, lowest, layout = "stack", listingsCount }) => {
  const savings = Math.round(((psf - lowest) / psf) * 100);
  if (layout === "row") {
    return (
      <div style={{ display: "flex", alignItems: "baseline", gap: 12 }}>
        <span style={{ fontSize: 11, color: "var(--fg-soft)", textDecoration: "line-through" }}>PSF {formatTL(psf)}</span>
        <span className="mono" style={{ fontSize: 22, fontWeight: 700 }}>{formatTL(lowest)}</span>
        {savings > 0 && <span className="chip chip-success">%{savings} daha ucuz</span>}
      </div>
    );
  }
  return (
    <div>
      <div style={{ display: "flex", justifyContent: "space-between", fontSize: 11, color: "var(--fg-soft)" }}>
        <span>PSF</span>
        <span className="mono" style={{ textDecoration: "line-through" }}>{formatTL(psf)}</span>
      </div>
      <div style={{ display: "flex", alignItems: "baseline", justifyContent: "space-between", marginTop: 2 }}>
        <span style={{ fontSize: 11, color: "var(--success)", fontWeight: 600 }}>En düşük</span>
        <span className="mono" style={{ fontSize: 18, fontWeight: 700 }}>{formatTL(lowest)}</span>
      </div>
      {listingsCount != null && (
        <div style={{ marginTop: 6, fontSize: 11, color: "var(--fg-muted)" }}>
          <span className="mono">{listingsCount}</span> satıcı ilanı
        </div>
      )}
    </div>
  );
};

// Product card — supports two visual styles ("solid" / "outline") and two layouts ("grid" / "list")
const ProductCard = ({ product, layout = "grid", style = "solid" }) => {
  const isList = layout === "list";
  const baseStyle = style === "outline"
    ? { background: "var(--bg-elevated)", border: "1px solid var(--border)" }
    : { background: "var(--bg-elevated)", border: "1px solid var(--border)", boxShadow: "var(--shadow-sm)" };

  return (
    <a href="#" style={{
      ...baseStyle,
      borderRadius: "var(--radius-lg)",
      padding: isList ? "var(--space-3)" : "var(--space-3)",
      display: isList ? "grid" : "flex",
      gridTemplateColumns: isList ? "120px 1fr 220px" : undefined,
      flexDirection: isList ? undefined : "column",
      gap: isList ? 16 : 10,
      transition: "border-color 120ms, transform 120ms",
      position: "relative",
    }}
      onMouseEnter={(e) => { e.currentTarget.style.borderColor = "var(--border-strong)"; }}
      onMouseLeave={(e) => { e.currentTarget.style.borderColor = "var(--border)"; }}
    >
      {/* Image */}
      <div className="ph-image" style={{
        aspectRatio: isList ? "1 / 1" : "1 / 1",
        width: isList ? 120 : "100%",
      }}>
        {product.brand}
      </div>

      {/* Body */}
      <div style={{ display: "flex", flexDirection: "column", gap: 6, flex: 1, minWidth: 0 }}>
        <div style={{ display: "flex", gap: 6, alignItems: "center" }}>
          <span className="mono" style={{ fontSize: 10, color: "var(--fg-soft)" }}>{product.sku}</span>
          {product.tag && <span className={
            product.tag === "Az stok" ? "chip chip-warning" :
            product.tag === "Çok satan" ? "chip chip-accent" :
            "chip"
          }>{product.tag}</span>}
        </div>
        <div style={{
          fontSize: 13, fontWeight: 500, lineHeight: 1.35,
          display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical",
          overflow: "hidden", textWrap: "pretty",
        }}>{product.name}</div>
        <div style={{ fontSize: 11, color: "var(--fg-soft)" }}>{product.brand} · {product.category}</div>
      </div>

      {/* Price */}
      <div style={{
        display: "flex", flexDirection: "column", gap: 8,
        paddingTop: isList ? 0 : 10,
        borderTop: isList ? "none" : "1px solid var(--border)",
        marginTop: isList ? 0 : "auto",
      }}>
        <PriceBlock psf={product.psf} lowest={product.lowest} listingsCount={product.listingsCount} />
        <button className="btn btn-soft btn-sm" style={{ width: "100%" }}>
          <Icon name="cart" size={13} /> Sepete ekle
        </button>
      </div>
    </a>
  );
};

// Listing row (seller offer) — used on product detail
const ListingRow = ({ listing, idx, isBest }) => (
  <div style={{
    display: "grid",
    gridTemplateColumns: "44px 1fr 140px 140px 140px 140px",
    alignItems: "center",
    gap: 12,
    padding: "14px 16px",
    background: isBest ? "var(--accent-soft)" : "var(--bg-elevated)",
    borderRadius: "var(--radius)",
    border: isBest ? "1px solid var(--accent)" : "1px solid var(--border)",
    position: "relative",
  }}>
    <div className="mono" style={{
      fontSize: 11, color: isBest ? "var(--accent)" : "var(--fg-soft)",
      fontWeight: 600,
    }}>#{(idx + 1).toString().padStart(2, "0")}</div>

    <div style={{ minWidth: 0 }}>
      <div style={{ display: "flex", alignItems: "center", gap: 8, marginBottom: 2 }}>
        <span style={{ fontSize: 14, fontWeight: 600 }}>{listing.seller}</span>
        {listing.verified && (
          <span className="chip chip-success" title="Doğrulanmış satıcı">
            <Icon name="check" size={10} /> Onaylı
          </span>
        )}
        {listing.fastShip && (
          <span className="chip chip-accent" title="Hızlı kargo">
            <Icon name="bolt" size={10} /> Hızlı
          </span>
        )}
      </div>
      <div style={{ display: "flex", gap: 10, fontSize: 12, color: "var(--fg-muted)" }}>
        <Stars value={listing.rating} />
        <span><span style={{ color: "var(--fg)", fontWeight: 500 }}>{listing.rating}</span> · <span className="mono">{listing.reviews.toLocaleString("tr-TR")}</span> yorum</span>
        <span style={{ display: "inline-flex", gap: 4, alignItems: "center" }}>
          <Icon name="pin" size={12} /> {listing.city}
        </span>
      </div>
    </div>

    <div style={{ fontSize: 12, color: "var(--fg-muted)" }}>
      <div style={{ color: "var(--fg-soft)", fontSize: 10, textTransform: "uppercase", letterSpacing: 0.04 }}>Stok</div>
      <div className="mono" style={{ fontSize: 13, color: "var(--fg)", fontWeight: 500 }}>{listing.stock.toLocaleString("tr-TR")} adet</div>
    </div>

    <div style={{ fontSize: 12, color: "var(--fg-muted)" }}>
      <div style={{ color: "var(--fg-soft)", fontSize: 10, textTransform: "uppercase", letterSpacing: 0.04 }}>Teslimat</div>
      <div style={{ fontSize: 13, color: "var(--fg)", fontWeight: 500, display: "inline-flex", alignItems: "center", gap: 6 }}>
        <Icon name="truck" size={13} /> {listing.ship}
      </div>
    </div>

    <div style={{ textAlign: "right" }}>
      <div style={{ color: "var(--fg-soft)", fontSize: 10, textTransform: "uppercase", letterSpacing: 0.04 }}>KDV dahil</div>
      <div className="mono" style={{ fontSize: 18, fontWeight: 700 }}>{formatTL(listing.price)}</div>
    </div>

    <div style={{ display: "flex", gap: 6, justifyContent: "flex-end" }}>
      <button className="btn btn-ghost btn-sm" title="Favorilere ekle"><Icon name="heart" size={13} /></button>
      <button className={isBest ? "btn btn-primary btn-sm" : "btn btn-ghost btn-sm"}>
        <Icon name="cart" size={13} /> Sepet
      </button>
    </div>
  </div>
);

const Footer = () => (
  <footer style={{
    background: "var(--bg-muted)",
    borderTop: "1px solid var(--border)",
    marginTop: 48,
  }}>
    <div style={{ maxWidth: 1440, margin: "0 auto", padding: "40px 24px 24px" }}>
      <div style={{ display: "grid", gridTemplateColumns: "1.5fr 1fr 1fr 1fr 1fr", gap: 32 }}>
        <div>
          <Logo />
          <p style={{ marginTop: 12, fontSize: 13, color: "var(--fg-muted)", maxWidth: 280 }}>
            Türkiye'nin B2B kırtasiye ve ofis malzemeleri toptan pazaryeri. Bayiler ve toptancılar için.
          </p>
        </div>
        {[
          { h: "Pazaryeri", links: ["Tüm kategoriler", "Markalar", "Kampanyalar", "Yeni ilanlar"] },
          { h: "Satıcılar", links: ["Satıcı paneli", "Satıcı olmak", "Komisyon oranları", "Satıcı eğitimi"] },
          { h: "Bayiler", links: ["Vadeli alışveriş", "Toplu sipariş", "Kurumsal hesap", "Faturalar"] },
          { h: "Destek", links: ["Yardım merkezi", "İletişim", "KVKK", "Sözleşmeler"] },
        ].map((col) => (
          <div key={col.h}>
            <div className="eyebrow" style={{ marginBottom: 12 }}>{col.h}</div>
            <ul style={{ listStyle: "none", padding: 0, margin: 0, display: "grid", gap: 8 }}>
              {col.links.map((l) => (
                <li key={l}><a href="#" style={{ fontSize: 13, color: "var(--fg-muted)" }}>{l}</a></li>
              ))}
            </ul>
          </div>
        ))}
      </div>
      <div style={{
        marginTop: 32, paddingTop: 20, borderTop: "1px solid var(--border)",
        display: "flex", justifyContent: "space-between", fontSize: 12, color: "var(--fg-soft)",
      }}>
        <span>© 2026 i-kirtasiye B2B · v2.4.1</span>
        <span className="mono">Tüm fiyatlar KDV dahil</span>
      </div>
    </div>
  </footer>
);

Object.assign(window, {
  Icon, Stars, Logo, UtilityBar, TopBar, MainNav,
  PriceBlock, ProductCard, ListingRow, Footer, formatTL,
});
