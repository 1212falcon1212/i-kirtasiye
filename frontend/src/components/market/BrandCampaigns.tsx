'use client';

import Link from 'next/link';

interface BrandData {
  name: string;
  color: string;
  tagline: string;
  slug: string;
}

const brands: BrandData[] = [
  { name: 'BIODERMA', color: '#e11d48', tagline: 'Misel su & cilt bakım serisinde %20', slug: 'bioderma' },
  { name: 'LA ROCHE POSAY', color: '#0369a1', tagline: 'SPF & Effaclar serisinde özel fiyat', slug: 'la-roche-posay' },
  { name: 'AVENE', color: '#0f766e', tagline: 'Hassas cilt ürünlerinde toptan avantaj', slug: 'avene' },
  { name: 'SOLGAR', color: '#7c3aed', tagline: 'Vitamin takviyelerinde B2B kampanya', slug: 'solgar' },
  { name: 'VICHY', color: '#b45309', tagline: 'Anti-aging serisinde stok fırsatı', slug: 'vichy' },
  { name: 'NUXE', color: '#ea580c', tagline: 'Vücut & yüz bakım serisinde indirim', slug: 'nuxe' },
];

export function BrandCampaigns() {
  return (
    <div className="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-3.5">
      {brands.map((brand) => (
        <Link
          key={brand.name}
          href={`/market/marka/${brand.slug}`}
          className="relative overflow-hidden rounded-[18px] px-4 py-5 sm:px-6 sm:py-7 hover:-translate-y-1 hover:shadow-xl transition-all cursor-pointer block flex flex-col group"
          style={{
            background: `linear-gradient(135deg, ${brand.color} 0%, ${brand.color}dd 100%)`,
          }}
        >
          {/* Decorative glow */}
          <div
            className="absolute -top-12 -right-12 w-32 h-32 rounded-full opacity-20 blur-2xl group-hover:opacity-30 transition-opacity"
            style={{ background: 'white' }}
          />
          <div
            className="absolute -bottom-10 -left-10 w-24 h-24 rounded-full opacity-10 blur-xl"
            style={{ background: 'white' }}
          />

          <p className="relative text-base sm:text-xl font-black tracking-tight truncate text-white drop-shadow-sm">
            {brand.name}
          </p>
          <p className="relative text-[12px] sm:text-[13px] text-white/90 mt-1 line-clamp-2">
            {brand.tagline}
          </p>
          <p className="relative text-[12px] sm:text-[13px] font-bold mt-3 sm:mt-4 text-white/95 group-hover:text-white transition-colors">
            Ürünleri İncele &rarr;
          </p>
        </Link>
      ))}
    </div>
  );
}
