'use client';

import { cn } from '@/lib/utils';

interface SellerTypeBadgeProps {
  role: 'retailer' | 'seller' | string;
  size?: 'sm' | 'md' | 'lg';
  showLabel?: boolean;
  className?: string;
}

// Retailer "K" (Kırtasiye) Icon
const RetailerIcon = ({ className }: { className?: string }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    className={className}
  >
    {/* Rounded square background */}
    <rect x="2" y="2" width="20" height="20" rx="4" fill="currentColor" />
    {/* K letter */}
    <path
      d="M7 6H9.6V11L14 6H17L12.4 11.5L17.4 18H14.2L10.4 12.8L9.6 13.7V18H7V6Z"
      fill="white"
    />
  </svg>
);

// Distributor / Seller Building Icon
const DistributorIcon = ({ className }: { className?: string }) => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    xmlns="http://www.w3.org/2000/svg"
    className={className}
  >
    {/* Main building */}
    <path
      d="M3 21V7L12 3L21 7V21H3Z"
      fill="currentColor"
    />
    {/* Windows - row 1 */}
    <rect x="6" y="9" width="3" height="2.5" rx="0.5" fill="white" />
    <rect x="10.5" y="9" width="3" height="2.5" rx="0.5" fill="white" />
    <rect x="15" y="9" width="3" height="2.5" rx="0.5" fill="white" />
    {/* Windows - row 2 */}
    <rect x="6" y="13" width="3" height="2.5" rx="0.5" fill="white" />
    <rect x="10.5" y="13" width="3" height="2.5" rx="0.5" fill="white" />
    <rect x="15" y="13" width="3" height="2.5" rx="0.5" fill="white" />
    {/* Door */}
    <rect x="9.5" y="17" width="5" height="4" rx="0.5" fill="white" />
  </svg>
);

const sizeClasses = {
  sm: 'w-4 h-4',
  md: 'w-5 h-5',
  lg: 'w-6 h-6',
};

const labelSizeClasses = {
  sm: 'text-xs',
  md: 'text-sm',
  lg: 'text-base',
};

export function SellerTypeBadge({
  role,
  size = 'md',
  showLabel = false,
  className,
}: SellerTypeBadgeProps) {
  const isRetailer = role === 'retailer';
  const isSeller = role === 'seller';

  if (!isRetailer && !isSeller) {
    return null;
  }

  const Icon = isRetailer ? RetailerIcon : DistributorIcon;
  const label = isRetailer ? 'Kırtasiyeci' : 'Tedarikçi';

  return (
    <div
      className={cn(
        'inline-flex items-center gap-1.5',
        className
      )}
      title={label}
    >
      <Icon className={cn(sizeClasses[size], 'text-slate-800')} />
      {showLabel && (
        <span className={cn(labelSizeClasses[size], 'text-slate-600 font-medium')}>
          {label}
        </span>
      )}
    </div>
  );
}

// Export individual icons for direct use
export { RetailerIcon, DistributorIcon };
