'use client';

import Link from 'next/link';

export function DualBanner() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
      {/* Banner 1 - Cilt Bakımı */}
      <Link
        href="/market/category/cilt-bakimi"
        className="rounded-[20px] h-[170px] sm:h-[200px] overflow-hidden relative cursor-pointer group hover:scale-[1.01] transition-transform bg-gradient-to-r from-[#ea580c] via-[#ea580c]/80 to-[#d99248]/70 block"
      >
        <div className="absolute left-5 right-5 sm:left-8 sm:right-8 top-1/2 -translate-y-1/2">
          <p className="text-[10px] sm:text-[11px] font-bold tracking-[2px] sm:tracking-[3px] text-white/70">
            DERMOKOZMETİK
          </p>
          <p className="text-lg sm:text-2xl lg:text-[30px] font-black text-white leading-tight mt-1">
            Cilt Bakım Markalarında
          </p>
          <p className="text-2xl sm:text-3xl lg:text-[44px] font-black text-[#fef08a] leading-tight mt-0.5">
            %30&apos;a Varan İndirim
          </p>
        </div>
      </Link>

      {/* Banner 2 - Vitaminler */}
      <Link
        href="/market/category/vitaminler"
        className="rounded-[20px] h-[170px] sm:h-[200px] overflow-hidden relative cursor-pointer group hover:scale-[1.01] transition-transform bg-gradient-to-br from-slate-900 via-slate-800/95 to-slate-700/85 block"
      >
        <div className="absolute left-5 right-5 sm:left-8 sm:right-8 top-1/2 -translate-y-1/2">
          <p className="text-[10px] sm:text-[11px] font-bold tracking-widest text-[#d99248]">
            BU HAFTA
          </p>
          <p className="text-lg sm:text-2xl lg:text-[30px] font-black text-white leading-tight mt-1">
            Vitamin &amp; Takviye Ürünlerinde
          </p>
          <span className="inline-block bg-[#1e3a8a] text-white px-4 sm:px-6 py-2 sm:py-2.5 rounded-[10px] font-bold text-[12px] sm:text-[13px] mt-2 sm:mt-3 group-hover:bg-[#1e40af] transition-colors">
            Hemen Keşfet
          </span>
        </div>
      </Link>
    </div>
  );
}
