// Market (homepage)
const MarketPage = () => {
  const { categories, products, campaigns, brands } = window.IKData;
  return (
    <div className="page" style={{ minHeight: "100%" }}>
      <UtilityBar />
      <TopBar />
      <MainNav active="Tüm kategoriler" />

      {/* HERO — full-width split banner with product imagery */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "20px 24px 0" }}>
        <div style={{
          borderRadius: "var(--radius-xl)",
          overflow: "hidden",
          background: "var(--bg-elevated)",
          border: "1px solid var(--border)",
          display: "grid",
          gridTemplateColumns: "minmax(380px, 1fr) 2fr",
          minHeight: 360,
        }}>
          {/* Left — bold solid block */}
          <div style={{
            background: "linear-gradient(150deg, var(--accent) 0%, var(--accent-hover) 100%)",
            color: "var(--accent-fg)",
            padding: "40px 36px",
            position: "relative",
            display: "flex", flexDirection: "column", justifyContent: "space-between",
            overflow: "hidden",
          }}>
            {/* Decorative ring */}
            <div style={{ position: "absolute", right: -80, top: -80, width: 240, height: 240, borderRadius: 999, border: "32px solid rgba(255,255,255,0.08)" }} />
            <div style={{ position: "absolute", left: -40, bottom: -40, width: 140, height: 140, borderRadius: 999, background: "rgba(255,255,255,0.06)" }} />
            <div style={{ position: "relative" }}>
              <span className="chip" style={{ background: "rgba(255,255,255,0.18)", color: "white", border: "none", fontWeight: 600 }}>
                <Icon name="flame" size={11} /> Hafta sonuna özel
              </span>
              <div style={{ marginTop: 16, fontSize: 14, fontWeight: 600, opacity: 0.85, letterSpacing: 0.04, textTransform: "uppercase" }}>
                25 farklı kategoride
              </div>
              <h1 style={{ fontSize: 40, fontWeight: 800, marginTop: 6, lineHeight: 1.05, letterSpacing: "-0.02em" }}>
                OKULA<br />DÖNÜŞ <span style={{ background: "rgba(255,255,255,0.92)", color: "var(--accent)", padding: "0 10px", borderRadius: 6, display: "inline-block", marginTop: 4 }}>%25 İNDİRİM</span>
              </h1>
              <p style={{ marginTop: 12, opacity: 0.9, fontSize: 14, maxWidth: 360, lineHeight: 1.5 }}>
                Defter, kalem, çanta ve daha fazlası — bayilere özel toptan fiyatlarla.
              </p>
            </div>
            <div style={{ display: "flex", gap: 10, position: "relative" }}>
              <button className="btn btn-lg" style={{ background: "white", color: "var(--accent)", fontWeight: 700, paddingLeft: 20, paddingRight: 20 }}>
                Alışverişe başla <Icon name="arrow" size={14} />
              </button>
            </div>
          </div>

          {/* Right — visual stage */}
          <div style={{
            position: "relative",
            background: "var(--accent-soft)",
            display: "flex", alignItems: "center", justifyContent: "center",
            overflow: "hidden",
          }}>
            {/* Floating product placeholders */}
            <div className="ph-image" style={{
              position: "absolute", top: 36, left: 60, width: 130, height: 220,
              transform: "rotate(-8deg)", borderRadius: 12,
              boxShadow: "0 12px 32px rgba(0,0,0,0.12)", fontSize: 11,
            }}>DEFTER</div>
            <div className="ph-image" style={{
              position: "absolute", top: 80, right: 110, width: 200, height: 200,
              borderRadius: 999, boxShadow: "0 12px 32px rgba(0,0,0,0.12)", fontSize: 11,
            }}>KALEM SETİ</div>
            <div className="ph-image" style={{
              position: "absolute", bottom: 40, left: 200, width: 160, height: 130,
              transform: "rotate(6deg)", borderRadius: 12,
              boxShadow: "0 12px 32px rgba(0,0,0,0.12)", fontSize: 11,
            }}>BOYA SETİ</div>
            <div style={{
              position: "absolute", bottom: 32, right: 36,
              padding: "8px 14px",
              background: "var(--bg-elevated)", borderRadius: 999,
              border: "1px solid var(--border)",
              fontSize: 12, fontWeight: 600,
              boxShadow: "var(--shadow-lg)",
              display: "inline-flex", alignItems: "center", gap: 8,
            }}>
              <span style={{ width: 8, height: 8, borderRadius: 999, background: "var(--success)" }} />
              <span>3.200+ ilan güncellendi</span>
            </div>
            {/* Side dots */}
            <div style={{ position: "absolute", bottom: 16, left: 24, display: "flex", gap: 6 }}>
              <span style={{ width: 22, height: 6, borderRadius: 3, background: "var(--accent)" }} />
              <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--border-strong)" }} />
              <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--border-strong)" }} />
              <span style={{ width: 6, height: 6, borderRadius: 999, background: "var(--border-strong)" }} />
            </div>
          </div>
        </div>
      </div>

      {/* INFO CHIP ROW — komisyonsuz satış / ücretsiz kargo / güvenli tedarik / vadeli */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "16px 24px 0" }}>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 12 }}>
          {[
            { icon: "bolt", h: "Komisyonsuz Satış", p: "Sadece sabit aylık üyelik bedeli", tone: "accent" },
            { icon: "truck", h: "Ücretsiz Kargo", p: "1.500₺ üzeri tüm siparişlerde", tone: "success" },
            { icon: "shield", h: "Güvenli Tedarik", p: "Onaylı bayi ve tedarikçilerden", tone: "warning" },
            { icon: "box", h: "Vadeli Alışveriş", p: "30-60-90 gün vadeli ödeme", tone: "accent" },
          ].map((v) => {
            const toneBg = v.tone === "success" ? "var(--success-soft)" : v.tone === "warning" ? "var(--warning-soft)" : "var(--accent-soft)";
            const toneFg = v.tone === "success" ? "var(--success)" : v.tone === "warning" ? "var(--warning)" : "var(--accent)";
            return (
              <div key={v.h} className="card" style={{ padding: "14px 16px", display: "flex", gap: 12, alignItems: "center" }}>
                <div style={{
                  width: 40, height: 40, borderRadius: 10,
                  background: toneBg, color: toneFg,
                  display: "flex", alignItems: "center", justifyContent: "center",
                  flexShrink: 0,
                }}>
                  <Icon name={v.icon} size={18} />
                </div>
                <div style={{ minWidth: 0 }}>
                  <div style={{ fontSize: 14, fontWeight: 600 }}>{v.h}</div>
                  <div style={{ fontSize: 12, color: "var(--fg-muted)", lineHeight: 1.4 }}>{v.p}</div>
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* DEAL OF THE DAY + QUICK ORDER — split row */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "24px 24px 0" }}>
        <div style={{ display: "grid", gridTemplateColumns: "2fr 1fr", gap: 16 }}>
          {/* Günün fırsatı */}
          <div style={{
            background: "var(--bg-elevated)",
            border: "1px solid var(--border)",
            borderRadius: "var(--radius-lg)",
            padding: 20,
            display: "grid",
            gridTemplateColumns: "200px 1fr auto",
            gap: 20,
            alignItems: "center",
            position: "relative",
            overflow: "hidden",
          }}>
            <div className="ph-image" style={{ aspectRatio: "1 / 1", width: 200, borderRadius: 10 }}>
              FABER-CASTELL
            </div>
            <div>
              <span className="chip chip-warning" style={{ fontWeight: 700 }}>
                <Icon name="flame" size={11} /> Günün Fırsatı
              </span>
              <h3 style={{ fontSize: 20, marginTop: 8, lineHeight: 1.25 }}>
                Faber-Castell 9000 Kurşun Kalem 12'li Set
              </h3>
              <div style={{ marginTop: 6, fontSize: 13, color: "var(--fg-muted)" }}>
                <span className="mono" style={{ color: "var(--fg)", fontWeight: 600 }}>14</span> satıcı ilanı ·{" "}
                <span style={{ color: "var(--success)", fontWeight: 600 }}>Stokta</span>
              </div>
              <div style={{ display: "flex", alignItems: "baseline", gap: 12, marginTop: 12 }}>
                <span className="mono" style={{ fontSize: 13, color: "var(--fg-soft)", textDecoration: "line-through" }}>{formatTL(248)}</span>
                <span className="mono" style={{ fontSize: 28, fontWeight: 700, color: "var(--accent)" }}>{formatTL(184.5)}</span>
                <span className="chip chip-success">%25 indirim</span>
              </div>
              {/* Stock countdown */}
              <div style={{ marginTop: 10, fontSize: 11, color: "var(--fg-muted)" }}>
                <div style={{ display: "flex", justifyContent: "space-between", marginBottom: 4 }}>
                  <span>Bu fırsat için kalan stok</span>
                  <span className="mono" style={{ fontWeight: 600 }}>184 / 600</span>
                </div>
                <div style={{ height: 6, background: "var(--bg-muted)", borderRadius: 999, overflow: "hidden" }}>
                  <div style={{ width: "30%", height: "100%", background: "linear-gradient(90deg, var(--warning), var(--accent))" }} />
                </div>
              </div>
            </div>
            <button className="btn btn-primary btn-lg" style={{ alignSelf: "stretch", paddingLeft: 24, paddingRight: 24 }}>
              Fırsatı Yakala <Icon name="arrow" size={14} />
            </button>
          </div>

          {/* Hızlı sipariş */}
          <div style={{
            background: "var(--bg-elevated)",
            border: "1px solid var(--border)",
            borderRadius: "var(--radius-lg)",
            padding: 20,
            display: "flex", flexDirection: "column", gap: 12,
          }}>
            <div style={{ display: "flex", alignItems: "center", gap: 8 }}>
              <div style={{
                width: 32, height: 32, borderRadius: 8,
                background: "var(--accent-soft)", color: "var(--accent)",
                display: "flex", alignItems: "center", justifyContent: "center",
              }}>
                <Icon name="bolt" size={16} />
              </div>
              <div>
                <div className="eyebrow" style={{ fontSize: 10 }}>Hızlı sipariş</div>
                <div style={{ fontSize: 14, fontWeight: 600 }}>SKU veya barkod ile</div>
              </div>
            </div>
            <div style={{ position: "relative" }}>
              <span style={{ position: "absolute", left: 10, top: "50%", transform: "translateY(-50%)", color: "var(--fg-soft)" }}>
                <Icon name="search" size={14} />
              </span>
              <input className="input" placeholder="FC-9000-12, BIC-CRY-50…" style={{ paddingLeft: 32 }} />
            </div>
            <button className="btn btn-soft btn-sm">
              Excel ile toplu yükle <Icon name="arrow" size={12} />
            </button>
            <div style={{ display: "flex", gap: 6, marginTop: "auto", paddingTop: 8, borderTop: "1px dashed var(--border)" }}>
              <div style={{ flex: 1, textAlign: "center" }}>
                <div className="mono" style={{ fontSize: 18, fontWeight: 700, color: "var(--accent)" }}>142</div>
                <div style={{ fontSize: 10, color: "var(--fg-soft)" }}>bayi bu hafta</div>
              </div>
              <div style={{ width: 1, background: "var(--border)" }} />
              <div style={{ flex: 1, textAlign: "center" }}>
                <div className="mono" style={{ fontSize: 18, fontWeight: 700, color: "var(--accent)" }}>3.8s</div>
                <div style={{ fontSize: 10, color: "var(--fg-soft)" }}>ort. süre</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Categories rail */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "32px 24px 0" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 16 }}>
          <h2 style={{ fontSize: 20 }}>Kategoriler</h2>
          <a href="#" style={{ fontSize: 13, color: "var(--accent)", fontWeight: 500 }}>Tümünü gör →</a>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(6, 1fr)", gap: 12 }}>
          {categories.slice(0, 12).map((c) => (
            <a key={c.id} href="#" className="card" style={{
              padding: "16px 14px",
              display: "flex", flexDirection: "column", gap: 8,
              transition: "border-color 120ms",
            }}>
              <div style={{
                width: 40, height: 40, borderRadius: 8,
                background: "var(--accent-soft)", color: "var(--accent)",
                display: "flex", alignItems: "center", justifyContent: "center",
                fontSize: 20,
              }}>{c.icon}</div>
              <div style={{ fontSize: 13, fontWeight: 500, lineHeight: 1.3 }}>{c.name}</div>
              <div className="mono" style={{ fontSize: 11, color: "var(--fg-soft)" }}>
                {c.count.toLocaleString("tr-TR")} ürün
              </div>
            </a>
          ))}
        </div>
      </div>

      {/* SEZONUN / HAFTANIN — themed section cards (i-depo style: tinted-bg cards with icon + title) */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "32px 24px 0" }}>
        <div style={{ display: "grid", gridTemplateColumns: "1fr 1fr", gap: 16 }}>
          {[
            { icon: "flame", title: "Sezonun Öne Çıkanları", subtitle: "Bahar dönemi en çok satan toptan ürünler", tone: "warning", items: products.slice(0, 3) },
            { icon: "bolt", title: "Haftanın Ürünleri", subtitle: "Bu hafta editör seçimi ilanlar", tone: "accent", items: products.slice(3, 6) },
          ].map((sec) => {
            const toneBg = sec.tone === "warning" ? "var(--warning-soft)" : "var(--accent-soft)";
            const toneFg = sec.tone === "warning" ? "var(--warning)" : "var(--accent)";
            return (
              <div key={sec.title} style={{
                background: "var(--bg-elevated)",
                border: "1px solid var(--border)",
                borderRadius: "var(--radius-lg)",
                overflow: "hidden",
              }}>
                <div style={{
                  padding: "14px 18px",
                  background: toneBg,
                  borderBottom: `1px solid ${toneBg}`,
                  display: "flex", justifyContent: "space-between", alignItems: "center",
                }}>
                  <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
                    <div style={{
                      width: 30, height: 30, borderRadius: 8,
                      background: "var(--bg-elevated)", color: toneFg,
                      display: "flex", alignItems: "center", justifyContent: "center",
                    }}>
                      <Icon name={sec.icon} size={15} />
                    </div>
                    <div>
                      <div style={{ fontSize: 15, fontWeight: 700, color: toneFg }}>{sec.title}</div>
                      <div style={{ fontSize: 11, color: "var(--fg-muted)" }}>{sec.subtitle}</div>
                    </div>
                  </div>
                  <a href="#" style={{ fontSize: 12, color: toneFg, fontWeight: 600 }}>Tümü →</a>
                </div>
                <div style={{ padding: 12, display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 10 }}>
                  {sec.items.map((p) => (
                    <a key={p.id} href="#" style={{
                      display: "flex", flexDirection: "column", gap: 6,
                      padding: 8, borderRadius: "var(--radius)",
                      transition: "background 120ms",
                    }}
                    onMouseEnter={(e) => { e.currentTarget.style.background = "var(--bg-muted)"; }}
                    onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
                    >
                      <div className="ph-image" style={{ aspectRatio: "1 / 1", fontSize: 9 }}>{p.brand}</div>
                      <div style={{ fontSize: 12, fontWeight: 500, lineHeight: 1.3,
                        display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", overflow: "hidden",
                      }}>{p.name}</div>
                      <div className="mono" style={{ fontSize: 14, fontWeight: 700, color: toneFg }}>
                        {formatTL(p.lowest)}
                      </div>
                    </a>
                  ))}
                </div>
              </div>
            );
          })}
        </div>
      </div>

      {/* SON SATILANLAR — live feed with green pulse */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "24px 24px 0" }}>
        <div style={{
          background: "var(--bg-elevated)",
          border: "1px solid var(--border)",
          borderRadius: "var(--radius-lg)",
          padding: "18px 20px",
        }}>
          <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginBottom: 14 }}>
            <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
              <div style={{
                width: 30, height: 30, borderRadius: 8,
                background: "var(--success-soft)", color: "var(--success)",
                display: "flex", alignItems: "center", justifyContent: "center",
              }}>
                <Icon name="arrow" size={15} />
              </div>
              <h3 style={{ fontSize: 16 }}>Son Satılanlar</h3>
            </div>
            <span style={{ display: "inline-flex", alignItems: "center", gap: 6, fontSize: 11, color: "var(--success)", fontWeight: 600 }}>
              <span style={{ width: 8, height: 8, borderRadius: 999, background: "var(--success)", boxShadow: "0 0 0 4px var(--success-soft)" }} />
              Canlı güncellemeler
            </span>
          </div>
          <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 10 }}>
            {products.slice(0, 4).map((p, i) => (
              <a key={p.id} href="#" style={{
                display: "grid", gridTemplateColumns: "60px 1fr",
                gap: 10, padding: 10,
                border: "1px solid var(--border)",
                borderRadius: "var(--radius)",
                background: i === 1 ? "var(--accent-soft)" : "var(--bg)",
              }}>
                <div className="ph-image" style={{ aspectRatio: "1 / 1", width: 60, fontSize: 8 }}>{p.brand.slice(0, 4)}</div>
                <div style={{ minWidth: 0 }}>
                  <div style={{ fontSize: 12, fontWeight: 500, lineHeight: 1.25,
                    display: "-webkit-box", WebkitLineClamp: 2, WebkitBoxOrient: "vertical", overflow: "hidden",
                  }}>{p.name}</div>
                  <div className="mono" style={{ fontSize: 13, fontWeight: 700, color: i === 1 ? "var(--accent)" : "var(--fg)", marginTop: 2 }}>
                    {formatTL(p.lowest)}
                  </div>
                </div>
              </a>
            ))}
          </div>
        </div>
      </div>

      {/* Featured listings */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "32px 24px 0" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 16 }}>
          <div style={{ display: "flex", gap: 24, alignItems: "baseline" }}>
            <h2 style={{ fontSize: 20 }}>Öne çıkan ilanlar</h2>
            <div style={{ display: "flex", gap: 4 }}>
              {["Çok satan", "Yeni eklenen", "Fiyat düşen", "Hızlı kargo"].map((t, i) => (
                <button key={t} className={i === 0 ? "btn btn-soft btn-sm" : "btn btn-ghost btn-sm"}>{t}</button>
              ))}
            </div>
          </div>
          <a href="#" style={{ fontSize: 13, color: "var(--accent)", fontWeight: 500 }}>Tümünü gör →</a>
        </div>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4, 1fr)", gap: 16 }}>
          {products.slice(0, 8).map((p) => (
            <ProductCard key={p.id} product={p} />
          ))}
        </div>
      </div>

      {/* SECONDARY BANNERS — 3 themed promo strips */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "32px 24px 0" }}>
        <div style={{ display: "grid", gridTemplateColumns: "repeat(3, 1fr)", gap: 14 }}>
          {[
            { eyebrow: "Yeni Tedarikçi", title: "Bu hafta 38 toptancı katıldı", cta: "Keşfet", bg: "linear-gradient(135deg, oklch(0.92 0.04 250), oklch(0.86 0.06 240))", color: "#1a2456" },
            { eyebrow: "24 Saatte Teslim", title: "Hızlı kargo ilanları", cta: "İlanları gör", bg: "linear-gradient(135deg, oklch(0.93 0.05 150), oklch(0.86 0.08 145))", color: "#1a4a2e" },
            { eyebrow: "Toplu Sipariş", title: "Excel ile binlerce SKU", cta: "Yüklemeye başla", bg: "linear-gradient(135deg, oklch(0.93 0.05 60), oklch(0.86 0.09 55))", color: "#5a3812" },
          ].map((b) => (
            <a key={b.title} href="#" style={{
              background: b.bg,
              borderRadius: "var(--radius-lg)",
              padding: "20px 22px",
              minHeight: 130,
              color: b.color,
              display: "flex", flexDirection: "column", justifyContent: "space-between",
              position: "relative",
              overflow: "hidden",
            }}>
              <div style={{ position: "absolute", right: -20, top: -20, width: 100, height: 100, borderRadius: 999, background: "rgba(255,255,255,0.3)" }} />
              <div style={{ position: "relative" }}>
                <div className="eyebrow" style={{ color: b.color, opacity: 0.7 }}>{b.eyebrow}</div>
                <div style={{ fontSize: 18, fontWeight: 700, marginTop: 6, lineHeight: 1.25, maxWidth: 220 }}>{b.title}</div>
              </div>
              <div style={{ display: "inline-flex", alignItems: "center", gap: 6, fontSize: 13, fontWeight: 600, position: "relative" }}>
                {b.cta} <Icon name="arrow" size={13} />
              </div>
            </a>
          ))}
        </div>
      </div>

      {/* Brand strip */}
      <div style={{ maxWidth: 1440, margin: "0 auto", padding: "40px 24px 0" }}>
        <div style={{ display: "flex", justifyContent: "space-between", alignItems: "baseline", marginBottom: 16 }}>
          <h2 style={{ fontSize: 20 }}>Markalar</h2>
          <a href="#" style={{ fontSize: 13, color: "var(--accent)", fontWeight: 500 }}>Tüm markalar →</a>
        </div>
        <div style={{
          display: "grid", gridTemplateColumns: "repeat(8, 1fr)", gap: 8,
          background: "var(--bg-elevated)", border: "1px solid var(--border)",
          borderRadius: "var(--radius-lg)", padding: 8,
        }}>
          {brands.slice(0, 8).map((b) => (
            <a key={b} href="#" style={{
              padding: "20px 12px", borderRadius: "var(--radius)",
              display: "flex", alignItems: "center", justifyContent: "center",
              fontWeight: 600, fontSize: 13, color: "var(--fg-muted)",
              border: "1px solid transparent",
            }}
            onMouseEnter={(e) => { e.currentTarget.style.background = "var(--bg-muted)"; }}
            onMouseLeave={(e) => { e.currentTarget.style.background = "transparent"; }}
            >{b}</a>
          ))}
        </div>
      </div>

      <div style={{ height: 24 }} />
      <Footer />
    </div>
  );
};

window.MarketPage = MarketPage;
