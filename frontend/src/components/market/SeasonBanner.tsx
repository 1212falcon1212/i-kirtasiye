'use client';

import Link from 'next/link';

export function SeasonBanner() {
  return (
    <Link href="/market/kampanyalar" className="block">
      <div className="w-full rounded-3xl h-[180px] relative overflow-hidden hover:scale-[1.005] transition-transform cursor-pointer bg-gradient-to-r from-[#c2410c] to-[#d99248]">
        {/* Decorative circles */}
        <div className="absolute -top-10 -left-10 w-40 h-40 bg-white/[0.04] rounded-full" />
        <div className="absolute -bottom-8 -right-8 w-32 h-32 bg-white/[0.06] rounded-full" />

        {/* Content */}
        <div className="relative z-10 flex items-center justify-center flex-col text-center h-full px-4">
          <p className="text-[13px] font-bold tracking-[4px] text-white/75">
            SEZON KAMPANYASI
          </p>
          <p className="text-[38px] font-black text-white tracking-tight leading-tight">
            Yaz Kırtasiye Fırsatları!
          </p>
          <p className="text-sm text-white/85 mt-2">
            Defter, kalem ve sanat hobi malzemelerinde sezon indirimi
          </p>
        </div>
      </div>
    </Link>
  );
}
