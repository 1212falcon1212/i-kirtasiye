'use client';

interface SellerScoreBadgeProps {
  score: number | null | undefined;
  size?: 'sm' | 'md';
}

function getScoreColor(score: number): string {
  if (score >= 8) return 'bg-[#fbeede] text-white';
  if (score >= 6) return 'bg-[#b8651a] text-white';
  return 'bg-red-500 text-white';
}

export function SellerScoreBadge({ score, size = 'sm' }: SellerScoreBadgeProps) {
  const sizeClasses = size === 'md'
    ? 'px-1.5 py-0.5 text-[10px]'
    : 'px-1 py-0.5 text-[9px]';

  if (score == null) {
    return (
      <span className={`inline-flex items-center ${sizeClasses} bg-slate-400 text-white font-bold rounded`}>
        Yeni
      </span>
    );
  }

  return (
    <span className={`inline-flex items-center ${sizeClasses} ${getScoreColor(score)} font-bold rounded`}>
      {score.toFixed(1)}
    </span>
  );
}
