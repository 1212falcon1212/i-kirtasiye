'use client';

interface CreditCard3DProps {
  cardNumber: string;
  cardName: string;
  expiry: string;
  cvv: string;
  brand: string;
  bank: string;
  isFlipped: boolean;
}

const BANK_GRADIENTS: Record<string, string> = {
  ziraat: 'from-green-700 to-green-900',
  'ziraat bankasi': 'from-green-700 to-green-900',
  'is bankasi': 'from-blue-600 to-blue-900',
  isbank: 'from-blue-600 to-blue-900',
  garanti: 'from-green-600 to-green-800',
  'garanti bbva': 'from-green-600 to-green-800',
  'yapi kredi': 'from-blue-600 to-purple-800',
  yapikredi: 'from-blue-600 to-purple-800',
  akbank: 'from-red-600 to-red-900',
  halkbank: 'from-teal-600 to-teal-900',
  'halk bankasi': 'from-teal-600 to-teal-900',
  vakifbank: 'from-blue-700 to-blue-950',
  'vakif bankasi': 'from-blue-700 to-blue-950',
  finansbank: 'from-indigo-600 to-indigo-900',
  qnb: 'from-indigo-600 to-indigo-900',
  denizbank: 'from-sky-600 to-sky-900',
  ing: 'from-orange-500 to-orange-800',
  hsbc: 'from-red-700 to-red-950',
  teb: 'from-blue-500 to-blue-800',
  kuveytturk: 'from-[#fbeede] to-[#fbeede]',
};

function getBankGradient(bank: string): string {
  if (!bank) return 'from-slate-600 to-slate-900';
  const normalized = bank.toLowerCase().trim();
  for (const [key, gradient] of Object.entries(BANK_GRADIENTS)) {
    if (normalized.includes(key)) return gradient;
  }
  return 'from-slate-600 to-slate-900';
}

function formatDisplayNumber(cardNumber: string): string {
  const digits = cardNumber.replace(/\D/g, '');
  if (digits.length === 0) return '\u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022 \u2022\u2022\u2022\u2022';

  const placeholder = '\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022\u2022';
  const filled = digits + placeholder.slice(digits.length);
  return `${filled.slice(0, 4)} ${filled.slice(4, 8)} ${filled.slice(8, 12)} ${filled.slice(12, 16)}`;
}

function BrandLogo({ brand }: { brand: string }) {
  const normalized = brand.toLowerCase();

  if (normalized === 'visa') {
    return (
      <span className="text-xl font-bold italic tracking-tight text-white drop-shadow-md">
        VISA
      </span>
    );
  }

  if (normalized === 'mastercard') {
    return (
      <div className="flex items-center gap-0">
        <div className="h-7 w-7 rounded-full bg-red-500 opacity-80" />
        <div className="-ml-3 h-7 w-7 rounded-full bg-yellow-400 opacity-80" />
      </div>
    );
  }

  if (normalized === 'troy') {
    return (
      <span className="text-lg font-bold tracking-wide text-white drop-shadow-md">
        TROY
      </span>
    );
  }

  if (normalized === 'amex') {
    return (
      <span className="text-sm font-bold tracking-tight text-white drop-shadow-md leading-none">
        AMERICAN<br />EXPRESS
      </span>
    );
  }

  return null;
}

export function CreditCard3D({
  cardNumber,
  cardName,
  expiry,
  cvv,
  brand,
  bank,
  isFlipped,
}: CreditCard3DProps) {
  const gradient = getBankGradient(bank);
  const displayNumber = formatDisplayNumber(cardNumber);
  const displayName = cardName || 'AD SOYAD';
  const displayExpiry = expiry || 'AA/YY';
  const displayCvv = cvv ? cvv.replace(/./g, '\u2022') : '\u2022\u2022\u2022';

  return (
    <div
      className="mx-auto w-full max-w-[340px]"
      style={{ perspective: '1000px' }}
    >
      <div
        className="relative w-full transition-transform duration-700 ease-in-out"
        style={{
          aspectRatio: '1.586',
          transformStyle: 'preserve-3d',
          transform: isFlipped ? 'rotateY(180deg)' : 'rotateY(0deg)',
        }}
      >
        {/* Front Face */}
        <div
          className={`absolute inset-0 rounded-xl bg-gradient-to-br ${gradient} p-5 shadow-2xl`}
          style={{ backfaceVisibility: 'hidden' }}
        >
          {/* Chip + Brand */}
          <div className="flex items-start justify-between">
            {/* Chip */}
            <div className="flex flex-col gap-0.5">
              <div className="h-9 w-12 rounded-md bg-gradient-to-br from-yellow-200 to-yellow-400 shadow-inner">
                <div className="mt-2 ml-1 grid grid-cols-3 gap-px">
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/30" />
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/20" />
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/30" />
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/20" />
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/30" />
                  <div className="h-1.5 w-2.5 rounded-sm bg-yellow-600/20" />
                </div>
              </div>
              {bank && (
                <span className="mt-1 text-[10px] font-medium uppercase tracking-wider text-white/70">
                  {bank}
                </span>
              )}
            </div>

            {/* Brand Logo */}
            <div className="flex items-center">
              <BrandLogo brand={brand} />
            </div>
          </div>

          {/* Card Number */}
          <div className="mt-4">
            <p
              className="font-mono text-lg tracking-[0.15em] text-white sm:text-xl"
              aria-label="Kart numarasi"
            >
              {displayNumber}
            </p>
          </div>

          {/* Name + Expiry */}
          <div className="mt-auto flex items-end justify-between pt-3">
            <div className="min-w-0 flex-1">
              <p className="text-[10px] uppercase tracking-wider text-white/60">
                Kart Sahibi
              </p>
              <p className="truncate text-sm font-medium uppercase tracking-wide text-white">
                {displayName}
              </p>
            </div>
            <div className="ml-4 text-right">
              <p className="text-[10px] uppercase tracking-wider text-white/60">
                Son Kullanma
              </p>
              <p className="font-mono text-sm tracking-wider text-white">
                {displayExpiry}
              </p>
            </div>
          </div>
        </div>

        {/* Back Face */}
        <div
          className={`absolute inset-0 rounded-xl bg-gradient-to-br ${gradient} shadow-2xl`}
          style={{
            backfaceVisibility: 'hidden',
            transform: 'rotateY(180deg)',
          }}
        >
          {/* Magnetic Stripe */}
          <div className="mt-6 h-12 w-full bg-gray-900/80" />

          {/* CVV Area */}
          <div className="mt-5 px-5">
            <div className="flex items-center justify-end">
              <div className="w-3/4">
                <div className="h-9 rounded bg-white/90 px-3 py-1">
                  <p className="text-right font-mono text-lg tracking-widest text-gray-800">
                    {displayCvv}
                  </p>
                </div>
              </div>
            </div>
            <p className="mt-1 text-right text-[10px] text-white/60">CVV</p>
          </div>

          {/* Bottom info */}
          <div className="absolute bottom-4 left-5 right-5">
            <p className="text-[9px] leading-relaxed text-white/40">
              Bu kart, kart sahibinin kullanimi icindir. Yetkisiz kullanim yasaktir.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
