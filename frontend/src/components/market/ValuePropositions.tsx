'use client';

import { Tag, Truck, ShieldCheck } from 'lucide-react';

const propositions = [
  {
    icon: Tag,
    title: 'Komisyonsuz Satış',
    subtitle: 'Sadece sabit \u20BA50 hizmet bedeli',
    bg: 'bg-[#b8651a]',
  },
  {
    icon: Truck,
    title: 'Ücretsiz Kargo',
    subtitle: 'Tum siparislerde satici karsilar',
    bg: 'bg-[#0369a1]',
  },
  {
    icon: ShieldCheck,
    title: 'Guvenli Tedarik',
    subtitle: 'Onayli kirtasiye ve tedarikcilerden',
    bg: 'bg-[#934f12]',
  },
] as const;

export function ValuePropositions() {
  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-3.5">
      {propositions.map((item) => {
        const Icon = item.icon;
        return (
          <div
            key={item.title}
            className={`${item.bg} rounded-2xl px-6 py-5 flex items-center gap-4 hover:-translate-y-0.5 transition`}
          >
            <div className="w-[44px] h-[44px] bg-white/20 rounded-xl flex items-center justify-center shrink-0">
              <Icon className="w-5 h-5 text-white" />
            </div>
            <div>
              <p className="text-base font-extrabold text-white">{item.title}</p>
              <p className="text-xs text-white/85">{item.subtitle}</p>
            </div>
          </div>
        );
      })}
    </div>
  );
}
