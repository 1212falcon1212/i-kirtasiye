'use client';

import { useState, useEffect, useCallback, useRef, useImperativeHandle, forwardRef } from 'react';
import { ShieldCheck, Loader2, CreditCard as CreditCardIcon } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { CreditCard3D } from './CreditCard3D';
import {
  paymentsApi,
  type SavedCard,
  type InstallmentRates,
  type InstallmentRate,
  type BinQueryResponse,
  type PaymentProcessRequest,
} from '@/lib/api';

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

interface CardFormProps {
  orderId: number;
  totalAmount: number;
  onSuccess: (html: string) => void;
  onError: (error: string) => void;
}

/** Embedded mode: no submit button, parent controls submission via ref */
interface EmbeddedCardFormProps {
  totalAmount: number;
  embedded: true;
}

export interface CardFormRef {
  /** Validates and returns card payload, or null if validation fails */
  getPayload: () => Omit<PaymentProcessRequest, 'order_id'> | null;
  /** Whether a saved card or new card is being used */
  isUsingSavedCard: boolean;
}

interface FieldErrors {
  cardNumber?: string;
  expiry?: string;
  cvv?: string;
  ccOwner?: string;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function luhnCheck(num: string): boolean {
  const digits = num.replace(/\D/g, '');
  let sum = 0;
  let isDouble = false;
  for (let i = digits.length - 1; i >= 0; i--) {
    let d = parseInt(digits[i], 10);
    if (isDouble) {
      d *= 2;
      if (d > 9) d -= 9;
    }
    sum += d;
    isDouble = !isDouble;
  }
  return sum % 10 === 0;
}

function formatCardNumber(value: string): string {
  const digits = value.replace(/\D/g, '').slice(0, 16);
  return digits.replace(/(.{4})/g, '$1 ').trim();
}

function formatExpiry(value: string): string {
  const digits = value.replace(/\D/g, '').slice(0, 4);
  if (digits.length > 2) {
    return `${digits.slice(0, 2)}/${digits.slice(2)}`;
  }
  return digits;
}

function formatPrice(amount: number): string {
  return new Intl.NumberFormat('tr-TR', {
    style: 'currency',
    currency: 'TRY',
    minimumFractionDigits: 2,
  }).format(amount);
}

// ---------------------------------------------------------------------------
// Component
// ---------------------------------------------------------------------------

export function CardForm({ orderId, totalAmount, onSuccess, onError }: CardFormProps) {
  // Card form state
  const [cardNumber, setCardNumber] = useState('');
  const [expiryRaw, setExpiryRaw] = useState('');
  const [cvv, setCvv] = useState('');
  const [ccOwner, setCcOwner] = useState('');
  const [isFlipped, setIsFlipped] = useState(false);
  const [brand, setBrand] = useState('');
  const [bank, setBank] = useState('');
  const [installmentCount, setInstallmentCount] = useState(0);
  const [storeCard, setStoreCard] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState<FieldErrors>({});

  // Saved cards
  const [savedCards, setSavedCards] = useState<SavedCard[]>([]);
  const [selectedSavedCard, setSelectedSavedCard] = useState<string | null>(null);
  const [savedCardCvv, setSavedCardCvv] = useState('');
  const [loadingSavedCards, setLoadingSavedCards] = useState(true);

  // Installments
  const [installmentRates, setInstallmentRates] = useState<InstallmentRates | null>(null);
  const [loadingInstallments, setLoadingInstallments] = useState(true);

  // BIN query debounce
  const binTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const lastBinRef = useRef('');

  // ---------------------------------------------------------------------------
  // Derived values
  // ---------------------------------------------------------------------------

  const rawDigits = cardNumber.replace(/\D/g, '');
  const expiryMonth = expiryRaw.replace(/\D/g, '').slice(0, 2);
  const expiryYear = expiryRaw.replace(/\D/g, '').slice(2, 4);

  const applicableRates: InstallmentRate[] =
    installmentRates && brand
      ? installmentRates[brand.toLowerCase()] ?? installmentRates[brand] ?? []
      : [];

  const selectedRate = applicableRates.find((r) => r.installment_count === installmentCount);
  const ratePercent = selectedRate?.rate ?? 0;

  // ---------------------------------------------------------------------------
  // Data fetching
  // ---------------------------------------------------------------------------

  useEffect(() => {
    async function fetchSavedCards() {
      try {
        const res = await paymentsApi.getSavedCards();
        if (res.data?.cards) {
          setSavedCards(res.data.cards);
        }
      } catch {
        // Silent - saved cards are optional
      } finally {
        setLoadingSavedCards(false);
      }
    }

    async function fetchInstallments() {
      try {
        const res = await paymentsApi.getInstallments();
        if (res.data) {
          setInstallmentRates(res.data);
        }
      } catch {
        // Silent - installments fallback to single payment
      } finally {
        setLoadingInstallments(false);
      }
    }

    fetchSavedCards();
    fetchInstallments();
  }, []);

  // ---------------------------------------------------------------------------
  // BIN query (debounced)
  // ---------------------------------------------------------------------------

  const doBinQuery = useCallback(async (bin: string) => {
    try {
      const res = await paymentsApi.binQuery(bin);
      if (res.data) {
        const data: BinQueryResponse = res.data;
        setBrand(data.brand || '');
        setBank(data.bank || '');
      }
    } catch {
      // Silent - BIN detection is best-effort
    }
  }, []);

  useEffect(() => {
    if (binTimerRef.current) clearTimeout(binTimerRef.current);

    const bin = rawDigits.slice(0, 6);
    if (bin.length >= 6 && bin !== lastBinRef.current) {
      binTimerRef.current = setTimeout(() => {
        lastBinRef.current = bin;
        doBinQuery(bin);
      }, 500);
    }

    if (rawDigits.length < 6) {
      setBrand('');
      setBank('');
      lastBinRef.current = '';
    }

    return () => {
      if (binTimerRef.current) clearTimeout(binTimerRef.current);
    };
  }, [rawDigits, doBinQuery]);

  // ---------------------------------------------------------------------------
  // Input handlers
  // ---------------------------------------------------------------------------

  function handleCardNumberChange(e: React.ChangeEvent<HTMLInputElement>) {
    const formatted = formatCardNumber(e.target.value);
    setCardNumber(formatted);
    if (errors.cardNumber) setErrors((prev) => ({ ...prev, cardNumber: undefined }));
  }

  function handleExpiryChange(e: React.ChangeEvent<HTMLInputElement>) {
    const formatted = formatExpiry(e.target.value);
    setExpiryRaw(formatted);
    if (errors.expiry) setErrors((prev) => ({ ...prev, expiry: undefined }));
  }

  function handleCvvChange(e: React.ChangeEvent<HTMLInputElement>) {
    const digits = e.target.value.replace(/\D/g, '').slice(0, 4);
    setCvv(digits);
    if (errors.cvv) setErrors((prev) => ({ ...prev, cvv: undefined }));
  }

  function handleSavedCardCvvChange(e: React.ChangeEvent<HTMLInputElement>) {
    const digits = e.target.value.replace(/\D/g, '').slice(0, 4);
    setSavedCardCvv(digits);
  }

  function handleCcOwnerChange(e: React.ChangeEvent<HTMLInputElement>) {
    setCcOwner(e.target.value.toUpperCase());
    if (errors.ccOwner) setErrors((prev) => ({ ...prev, ccOwner: undefined }));
  }

  // ---------------------------------------------------------------------------
  // Validation
  // ---------------------------------------------------------------------------

  function validate(): boolean {
    const newErrors: FieldErrors = {};

    if (selectedSavedCard) {
      const card = savedCards.find((c) => c.ctoken === selectedSavedCard);
      if (card?.require_cvv && savedCardCvv.length < 3) {
        newErrors.cvv = 'CVV en az 3 haneli olmalidir';
      }
      setErrors(newErrors);
      return Object.keys(newErrors).length === 0;
    }

    // Card number
    if (rawDigits.length !== 16) {
      newErrors.cardNumber = 'Kart numarasi 16 haneli olmalidir';
    } else if (!luhnCheck(rawDigits)) {
      newErrors.cardNumber = 'Gecersiz kart numarasi';
    }

    // Expiry
    const month = parseInt(expiryMonth, 10);
    const year = parseInt(expiryYear, 10);
    if (!expiryMonth || !expiryYear || month < 1 || month > 12) {
      newErrors.expiry = 'Gecerli bir son kullanma tarihi giriniz';
    } else {
      const now = new Date();
      const currentMonth = now.getMonth() + 1;
      const currentYear = now.getFullYear() % 100;
      if (year < currentYear || (year === currentYear && month < currentMonth)) {
        newErrors.expiry = 'Kartinizin suresi dolmus';
      }
    }

    // CVV
    if (cvv.length < 3) {
      newErrors.cvv = 'CVV en az 3 haneli olmalidir';
    }

    // Name
    if (!ccOwner || ccOwner.trim().length < 3) {
      newErrors.ccOwner = 'Kart sahibi adi en az 3 karakter olmalidir';
    }

    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  }

  // ---------------------------------------------------------------------------
  // Submit
  // ---------------------------------------------------------------------------

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    if (!validate()) return;

    setIsSubmitting(true);

    try {
      const payload: PaymentProcessRequest = {
        order_id: orderId,
        cvv: selectedSavedCard ? savedCardCvv : cvv,
        installment_count: installmentCount > 0 ? installmentCount : undefined,
      };

      if (selectedSavedCard) {
        payload.ctoken = selectedSavedCard;
      } else {
        payload.card_number = rawDigits;
        payload.expiry_month = expiryMonth;
        payload.expiry_year = expiryYear;
        payload.cc_owner = ccOwner;
        payload.store_card = storeCard;
      }

      const res = await paymentsApi.process(payload);

      if (res.data?.status === '3d_redirect' && res.data.html) {
        onSuccess(res.data.html);
      } else if (res.data?.status === 'failed') {
        onError(res.data.error || 'Ödeme basarisiz oldu');
      } else if (res.data?.status === 'success') {
        onSuccess('');
      } else {
        onError(res.error || 'Beklenmeyen bir hata olustu');
      }
    } catch {
      onError('Ödeme islemi sirasinda bir hata olustu');
    } finally {
      setIsSubmitting(false);
    }
  }

  // ---------------------------------------------------------------------------
  // Installment calculation
  // ---------------------------------------------------------------------------

  function calculateMonthly(count: number, rate: number): string {
    if (count <= 1) return formatPrice(totalAmount);
    const total = totalAmount * (1 + rate / 100);
    return formatPrice(total / count);
  }

  function calculateTotal(rate: number): string {
    return formatPrice(totalAmount * (1 + rate / 100));
  }

  // ---------------------------------------------------------------------------
  // Saved card selection
  // ---------------------------------------------------------------------------

  function selectSavedCard(ctoken: string) {
    setSelectedSavedCard(ctoken);
    setSavedCardCvv('');
    setErrors({});
    const card = savedCards.find((c) => c.ctoken === ctoken);
    if (card) {
      setBrand(card.c_brand || '');
      setBank(card.c_bank || '');
    }
  }

  function useNewCard() {
    setSelectedSavedCard(null);
    setSavedCardCvv('');
    setBrand('');
    setBank('');
    setErrors({});
  }

  // ---------------------------------------------------------------------------
  // Render
  // ---------------------------------------------------------------------------

  const isUsingSavedCard = selectedSavedCard !== null;

  return (
    <form onSubmit={handleSubmit} className="mx-auto w-full max-w-md space-y-6">
      {/* 3D Card Preview */}
      <CreditCard3D
        cardNumber={isUsingSavedCard ? '' : cardNumber}
        cardName={isUsingSavedCard ? '' : ccOwner}
        expiry={isUsingSavedCard ? '' : expiryRaw}
        cvv={isUsingSavedCard ? savedCardCvv : cvv}
        brand={brand}
        bank={bank}
        isFlipped={isFlipped}
      />

      {/* Saved Cards */}
      {!loadingSavedCards && savedCards.length > 0 && (
        <div className="space-y-3">
          <Label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Kayıtlı Kartlarim
          </Label>
          <div className="space-y-2">
            {savedCards.map((card) => (
              <button
                key={card.ctoken}
                type="button"
                onClick={() => selectSavedCard(card.ctoken)}
                className={`flex w-full items-center gap-3 rounded-lg border p-3 text-left transition-colors ${
                  selectedSavedCard === card.ctoken
                    ? 'border-[#fbeede] bg-[#fbeede] dark:border-[#934f12] dark:bg-[#934f12]/30'
                    : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600'
                }`}
              >
                <div
                  className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 ${
                    selectedSavedCard === card.ctoken
                      ? 'border-[#fbeede]'
                      : 'border-gray-300 dark:border-gray-600'
                  }`}
                >
                  {selectedSavedCard === card.ctoken && (
                    <div className="h-2.5 w-2.5 rounded-full bg-[#fbeede]" />
                  )}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                    **** **** **** {card.last_4}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    {card.c_bank} - {card.c_brand?.toUpperCase()} | {card.month}/{card.year}
                  </p>
                </div>
              </button>
            ))}
          </div>

          {/* CVV for saved card */}
          {isUsingSavedCard && (
            <div className="space-y-1.5">
              <Label htmlFor="saved-cvv">CVV</Label>
              <Input
                id="saved-cvv"
                type="password"
                inputMode="numeric"
                placeholder="***"
                maxLength={4}
                value={savedCardCvv}
                onChange={handleSavedCardCvvChange}
                onFocus={() => setIsFlipped(true)}
                onBlur={() => setIsFlipped(false)}
                className="w-24"
                autoComplete="cc-csc"
              />
              {errors.cvv && (
                <p className="text-xs text-red-500">{errors.cvv}</p>
              )}
            </div>
          )}

          {isUsingSavedCard && (
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={useNewCard}
              className="text-[#b8651a] hover:text-[#b8651a] dark:text-[#fbeede]"
            >
              Yeni Kart ile Ode
            </Button>
          )}
        </div>
      )}

      {/* New Card Form */}
      {!isUsingSavedCard && (
        <div className="space-y-4">
          {savedCards.length > 0 && (
            <div className="flex items-center gap-2 border-b border-gray-200 pb-2 dark:border-gray-700">
              <CreditCardIcon className="h-4 w-4 text-gray-400" />
              <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Yeni Kart Bilgileri
              </span>
            </div>
          )}

          {/* Card Number */}
          <div className="space-y-1.5">
            <Label htmlFor="card-number">Kart Numarasi</Label>
            <Input
              id="card-number"
              type="text"
              inputMode="numeric"
              placeholder="0000 0000 0000 0000"
              value={cardNumber}
              onChange={handleCardNumberChange}
              maxLength={19}
              className={errors.cardNumber ? 'border-red-500 focus-visible:ring-red-500' : ''}
              autoComplete="cc-number"
              aria-invalid={!!errors.cardNumber}
              aria-describedby={errors.cardNumber ? 'card-number-error' : undefined}
            />
            {errors.cardNumber && (
              <p id="card-number-error" className="text-xs text-red-500">
                {errors.cardNumber}
              </p>
            )}
          </div>

          {/* Expiry + CVV row */}
          <div className="grid grid-cols-2 gap-3">
            <div className="space-y-1.5">
              <Label htmlFor="expiry">Son Kullanma</Label>
              <Input
                id="expiry"
                type="text"
                inputMode="numeric"
                placeholder="AA/YY"
                value={expiryRaw}
                onChange={handleExpiryChange}
                maxLength={5}
                className={errors.expiry ? 'border-red-500 focus-visible:ring-red-500' : ''}
                autoComplete="cc-exp"
                aria-invalid={!!errors.expiry}
                aria-describedby={errors.expiry ? 'expiry-error' : undefined}
              />
              {errors.expiry && (
                <p id="expiry-error" className="text-xs text-red-500">
                  {errors.expiry}
                </p>
              )}
            </div>
            <div className="space-y-1.5">
              <Label htmlFor="cvv">CVV</Label>
              <Input
                id="cvv"
                type="password"
                inputMode="numeric"
                placeholder="***"
                value={cvv}
                onChange={handleCvvChange}
                onFocus={() => setIsFlipped(true)}
                onBlur={() => setIsFlipped(false)}
                maxLength={4}
                className={errors.cvv ? 'border-red-500 focus-visible:ring-red-500' : ''}
                autoComplete="cc-csc"
                aria-invalid={!!errors.cvv}
                aria-describedby={errors.cvv ? 'cvv-error' : undefined}
              />
              {errors.cvv && (
                <p id="cvv-error" className="text-xs text-red-500">
                  {errors.cvv}
                </p>
              )}
            </div>
          </div>

          {/* Card Owner */}
          <div className="space-y-1.5">
            <Label htmlFor="cc-owner">Kart Sahibi</Label>
            <Input
              id="cc-owner"
              type="text"
              placeholder="AD SOYAD"
              value={ccOwner}
              onChange={handleCcOwnerChange}
              className={`uppercase ${errors.ccOwner ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
              autoComplete="cc-name"
              aria-invalid={!!errors.ccOwner}
              aria-describedby={errors.ccOwner ? 'cc-owner-error' : undefined}
            />
            {errors.ccOwner && (
              <p id="cc-owner-error" className="text-xs text-red-500">
                {errors.ccOwner}
              </p>
            )}
          </div>

          {/* Store Card Checkbox */}
          <div className="flex items-center gap-2">
            <Checkbox
              id="store-card"
              checked={storeCard}
              onCheckedChange={(checked) => setStoreCard(checked === true)}
            />
            <Label htmlFor="store-card" className="cursor-pointer text-sm font-normal">
              Kartimi kaydet
            </Label>
          </div>
        </div>
      )}

      {/* Installment Selector */}
      {!loadingInstallments && applicableRates.length > 0 && (
        <div className="space-y-3">
          <Label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Taksit Secenekleri
          </Label>
          <div className="space-y-1.5">
            {/* Tek Cekim */}
            <label
              className={`flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors ${
                installmentCount === 0
                  ? 'border-[#fbeede] bg-[#fbeede] dark:border-[#934f12] dark:bg-[#934f12]/30'
                  : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
              }`}
            >
              <div className="flex items-center gap-3">
                <input
                  type="radio"
                  name="installment"
                  value={0}
                  checked={installmentCount === 0}
                  onChange={() => setInstallmentCount(0)}
                  className="h-4 w-4 accent-[#b8651a]"
                />
                <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                  Tek Cekim
                </span>
              </div>
              <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                {formatPrice(totalAmount)}
              </span>
            </label>

            {/* Installment options */}
            {applicableRates
              .filter((r) => r.installment_count > 1)
              .map((rate) => (
                <label
                  key={rate.installment_count}
                  className={`flex cursor-pointer items-center justify-between rounded-lg border p-3 transition-colors ${
                    installmentCount === rate.installment_count
                      ? 'border-[#fbeede] bg-[#fbeede] dark:border-[#934f12] dark:bg-[#934f12]/30'
                      : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600'
                  }`}
                >
                  <div className="flex items-center gap-3">
                    <input
                      type="radio"
                      name="installment"
                      value={rate.installment_count}
                      checked={installmentCount === rate.installment_count}
                      onChange={() => setInstallmentCount(rate.installment_count)}
                      className="h-4 w-4 accent-[#b8651a]"
                    />
                    <span className="text-sm font-medium text-gray-900 dark:text-gray-100">
                      {rate.installment_count} Taksit
                    </span>
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                      ({calculateMonthly(rate.installment_count, rate.rate)}/ay)
                    </span>
                  </div>
                  <span className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                    {calculateTotal(rate.rate)}
                  </span>
                </label>
              ))}
          </div>
        </div>
      )}

      {/* Total Amount Display */}
      <div className="flex items-center justify-between rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800/50">
        <span className="text-sm font-medium text-gray-600 dark:text-gray-400">
          Odenecek Tutar
        </span>
        <span className="text-lg font-bold text-[#b8651a] dark:text-[#fbeede]">
          {installmentCount > 0 && ratePercent > 0
            ? calculateTotal(ratePercent)
            : formatPrice(totalAmount)}
        </span>
      </div>

      {/* Submit Button */}
      <Button
        type="submit"
        disabled={isSubmitting}
        className="h-12 w-full bg-[#fbeede] text-base font-semibold text-white hover:bg-[#934f12] disabled:opacity-50 dark:bg-[#934f12] dark:hover:bg-[#934f12]"
      >
        {isSubmitting ? (
          <span className="flex items-center gap-2">
            <Loader2 className="h-5 w-5 animate-spin" />
            Ödeme İsleniyor...
          </span>
        ) : (
          <span className="flex items-center gap-2">
            <ShieldCheck className="h-5 w-5" />
            Güvenli Ödeme Yap
          </span>
        )}
      </Button>

      {/* Security Note */}
      <p className="text-center text-xs text-gray-400 dark:text-gray-500">
        Ödeme bilgileriniz 256-bit SSL ile sifrelenerek korunmaktadir.
      </p>
    </form>
  );
}

// ---------------------------------------------------------------------------
// EmbeddedCardForm — inline card form for checkout page (no submit button)
// ---------------------------------------------------------------------------

export const EmbeddedCardForm = forwardRef<CardFormRef, EmbeddedCardFormProps>(
  function EmbeddedCardForm({ totalAmount }, ref) {
    const [cardNumber, setCardNumber] = useState('');
    const [expiryRaw, setExpiryRaw] = useState('');
    const [cvv, setCvv] = useState('');
    const [ccOwner, setCcOwner] = useState('');
    const [isFlipped, setIsFlipped] = useState(false);
    const [brand, setBrand] = useState('');
    const [bank, setBank] = useState('');
    const [installmentCount, setInstallmentCount] = useState(0);
    const [storeCard, setStoreCard] = useState(false);
    const [errors, setErrors] = useState<FieldErrors>({});

    // Saved cards
    const [savedCards, setSavedCards] = useState<SavedCard[]>([]);
    const [selectedSavedCard, setSelectedSavedCard] = useState<string | null>(null);
    const [savedCardCvv, setSavedCardCvv] = useState('');
    const [loadingSavedCards, setLoadingSavedCards] = useState(true);

    // Installments
    const [installmentRates, setInstallmentRates] = useState<InstallmentRates | null>(null);
    const [loadingInstallments, setLoadingInstallments] = useState(true);

    // Card input mode
    const [cardMode, setCardMode] = useState<'new' | 'saved'>('new');

    const binTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const lastBinRef = useRef('');

    const rawDigits = cardNumber.replace(/\D/g, '');
    const expiryMonth = expiryRaw.replace(/\D/g, '').slice(0, 2);
    const expiryYear = expiryRaw.replace(/\D/g, '').slice(2, 4);

    const applicableRates: InstallmentRate[] =
      installmentRates && brand
        ? installmentRates[brand.toLowerCase()] ?? installmentRates[brand] ?? []
        : [];

    const selectedRate = applicableRates.find((r) => r.installment_count === installmentCount);
    const ratePercent = selectedRate?.rate ?? 0;

    const isUsingSaved = selectedSavedCard !== null && cardMode === 'saved';

    // Fetch saved cards + installments
    useEffect(() => {
      async function fetchSavedCards() {
        try {
          const res = await paymentsApi.getSavedCards();
          if (res.data?.cards && res.data.cards.length > 0) {
            setSavedCards(res.data.cards);
            setCardMode('saved');
            setSelectedSavedCard(res.data.cards[0].ctoken);
            setBrand(res.data.cards[0].c_brand || '');
            setBank(res.data.cards[0].c_bank || '');
          }
        } catch { /* silent */ } finally { setLoadingSavedCards(false); }
      }
      async function fetchInstallments() {
        try {
          const res = await paymentsApi.getInstallments();
          if (res.data) setInstallmentRates(res.data);
        } catch { /* silent */ } finally { setLoadingInstallments(false); }
      }
      fetchSavedCards();
      fetchInstallments();
    }, []);

    // BIN query
    const doBinQuery = useCallback(async (bin: string) => {
      try {
        const res = await paymentsApi.binQuery(bin);
        if (res.data) { setBrand(res.data.brand || ''); setBank(res.data.bank || ''); }
      } catch { /* silent */ }
    }, []);

    useEffect(() => {
      if (binTimerRef.current) clearTimeout(binTimerRef.current);
      const bin = rawDigits.slice(0, 6);
      if (bin.length >= 6 && bin !== lastBinRef.current) {
        binTimerRef.current = setTimeout(() => { lastBinRef.current = bin; doBinQuery(bin); }, 500);
      }
      if (rawDigits.length < 6) { setBrand(''); setBank(''); lastBinRef.current = ''; }
      return () => { if (binTimerRef.current) clearTimeout(binTimerRef.current); };
    }, [rawDigits, doBinQuery]);

    // Expose ref API
    useImperativeHandle(ref, () => ({
      isUsingSavedCard: isUsingSaved,
      getPayload() {
        const newErrors: FieldErrors = {};

        if (isUsingSaved) {
          const card = savedCards.find((c) => c.ctoken === selectedSavedCard);
          if (card?.require_cvv && savedCardCvv.length < 3) {
            newErrors.cvv = 'CVV en az 3 haneli olmalidir';
          }
          setErrors(newErrors);
          if (Object.keys(newErrors).length > 0) return null;

          return {
            ctoken: selectedSavedCard!,
            cvv: savedCardCvv,
            installment_count: installmentCount > 0 ? installmentCount : undefined,
          };
        }

        // Validate new card
        if (rawDigits.length !== 16) newErrors.cardNumber = 'Kart numarasi 16 haneli olmalidir';
        else if (!luhnCheck(rawDigits)) newErrors.cardNumber = 'Gecersiz kart numarasi';

        const month = parseInt(expiryMonth, 10);
        const year = parseInt(expiryYear, 10);
        if (!expiryMonth || !expiryYear || month < 1 || month > 12) {
          newErrors.expiry = 'Gecerli bir son kullanma tarihi giriniz';
        } else {
          const now = new Date();
          const cm = now.getMonth() + 1;
          const cy = now.getFullYear() % 100;
          if (year < cy || (year === cy && month < cm)) newErrors.expiry = 'Kartinizin suresi dolmus';
        }
        if (cvv.length < 3) newErrors.cvv = 'CVV en az 3 haneli olmalidir';
        if (!ccOwner || ccOwner.trim().length < 3) newErrors.ccOwner = 'Kart sahibi adi en az 3 karakter olmalidir';

        setErrors(newErrors);
        if (Object.keys(newErrors).length > 0) return null;

        return {
          card_number: rawDigits,
          expiry_month: expiryMonth,
          expiry_year: expiryYear,
          cvv,
          cc_owner: ccOwner,
          installment_count: installmentCount > 0 ? installmentCount : undefined,
          store_card: storeCard,
        };
      },
    }));

    function selectSavedCard(ctoken: string) {
      setSelectedSavedCard(ctoken);
      setCardMode('saved');
      setSavedCardCvv('');
      setErrors({});
      const card = savedCards.find((c) => c.ctoken === ctoken);
      if (card) { setBrand(card.c_brand || ''); setBank(card.c_bank || ''); }
    }

    function switchToNewCard() {
      setCardMode('new');
      setSelectedSavedCard(null);
      setSavedCardCvv('');
      setBrand('');
      setBank('');
      setErrors({});
    }

    return (
      <div className="space-y-5">
        {/* Card mode tabs */}
        {!loadingSavedCards && savedCards.length > 0 && (
          <div className="flex rounded-lg border border-slate-200 dark:border-slate-700 overflow-hidden">
            <button
              type="button"
              onClick={() => { if (savedCards[0]) selectSavedCard(savedCards[0].ctoken); }}
              className={`flex-1 py-2.5 text-sm font-medium transition-colors ${
                cardMode === 'saved'
                  ? 'bg-[#fbeede] text-[#b8651a] dark:bg-[#934f12]/30 dark:text-[#fbeede]'
                  : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-400'
              }`}
            >
              Kayıtlı Kart Kullan
            </button>
            <button
              type="button"
              onClick={switchToNewCard}
              className={`flex-1 py-2.5 text-sm font-medium transition-colors border-l border-slate-200 dark:border-slate-700 ${
                cardMode === 'new'
                  ? 'bg-[#fbeede] text-[#b8651a] dark:bg-[#934f12]/30 dark:text-[#fbeede]'
                  : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-400'
              }`}
            >
              Yeni Kart Ekle
            </button>
          </div>
        )}

        {/* 3D Card Preview */}
        <CreditCard3D
          cardNumber={isUsingSaved ? `**** **** **** ${savedCards.find(c => c.ctoken === selectedSavedCard)?.last_4 || ''}` : cardNumber}
          cardName={isUsingSaved ? (savedCards.find(c => c.ctoken === selectedSavedCard)?.c_name || '') : ccOwner}
          expiry={isUsingSaved ? (() => { const c = savedCards.find(c => c.ctoken === selectedSavedCard); return c ? `${c.month}/${c.year?.slice(-2)}` : ''; })() : expiryRaw}
          cvv={isUsingSaved ? savedCardCvv : cvv}
          brand={brand}
          bank={bank}
          isFlipped={isFlipped}
        />

        {/* Saved Cards List */}
        {cardMode === 'saved' && savedCards.length > 0 && (
          <div className="space-y-2">
            {savedCards.map((card) => (
              <button
                key={card.ctoken}
                type="button"
                onClick={() => selectSavedCard(card.ctoken)}
                className={`flex w-full items-center gap-3 rounded-xl border-2 p-3 text-left transition-all ${
                  selectedSavedCard === card.ctoken
                    ? 'border-[#b8651a] bg-[#b8651a]/5 dark:border-[#934f12] dark:bg-[#934f12]/30'
                    : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-800'
                }`}
              >
                <div className={`flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 ${
                  selectedSavedCard === card.ctoken ? 'border-[#b8651a]' : 'border-slate-300 dark:border-slate-600'
                }`}>
                  {selectedSavedCard === card.ctoken && <div className="h-2 w-2 rounded-full bg-[#b8651a]" />}
                </div>
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium text-slate-900 dark:text-slate-100">
                    **** **** **** {card.last_4}
                  </p>
                  <p className="text-xs text-slate-500 dark:text-slate-400">
                    {card.c_bank} - {card.c_brand?.toUpperCase()} | {card.month}/{card.year}
                  </p>
                </div>
              </button>
            ))}

            {/* CVV for saved card */}
            {isUsingSaved && (
              <div className="flex items-center gap-3 pt-2">
                <Label htmlFor="embedded-saved-cvv" className="text-sm">CVV</Label>
                <Input
                  id="embedded-saved-cvv"
                  type="password"
                  inputMode="numeric"
                  placeholder="***"
                  maxLength={4}
                  value={savedCardCvv}
                  onChange={(e) => setSavedCardCvv(e.target.value.replace(/\D/g, '').slice(0, 4))}
                  onFocus={() => setIsFlipped(true)}
                  onBlur={() => setIsFlipped(false)}
                  className="w-24"
                  autoComplete="cc-csc"
                />
                {errors.cvv && <p className="text-xs text-red-500">{errors.cvv}</p>}
              </div>
            )}
          </div>
        )}

        {/* New Card Form */}
        {cardMode === 'new' && (
          <div className="space-y-4">
            <div className="space-y-1.5">
              <Label htmlFor="emb-card-number">Kart Numarasi</Label>
              <Input
                id="emb-card-number"
                type="text"
                inputMode="numeric"
                placeholder="0000 0000 0000 0000"
                value={cardNumber}
                onChange={(e) => { setCardNumber(formatCardNumber(e.target.value)); if (errors.cardNumber) setErrors(p => ({ ...p, cardNumber: undefined })); }}
                maxLength={19}
                className={errors.cardNumber ? 'border-red-500 focus-visible:ring-red-500' : ''}
                autoComplete="cc-number"
              />
              {errors.cardNumber && <p className="text-xs text-red-500">{errors.cardNumber}</p>}
            </div>

            <div className="grid grid-cols-2 gap-3">
              <div className="space-y-1.5">
                <Label htmlFor="emb-expiry">Son Kullanma</Label>
                <Input
                  id="emb-expiry"
                  type="text"
                  inputMode="numeric"
                  placeholder="AA/YY"
                  value={expiryRaw}
                  onChange={(e) => { setExpiryRaw(formatExpiry(e.target.value)); if (errors.expiry) setErrors(p => ({ ...p, expiry: undefined })); }}
                  maxLength={5}
                  className={errors.expiry ? 'border-red-500 focus-visible:ring-red-500' : ''}
                  autoComplete="cc-exp"
                />
                {errors.expiry && <p className="text-xs text-red-500">{errors.expiry}</p>}
              </div>
              <div className="space-y-1.5">
                <Label htmlFor="emb-cvv">CVV</Label>
                <Input
                  id="emb-cvv"
                  type="password"
                  inputMode="numeric"
                  placeholder="***"
                  value={cvv}
                  onChange={(e) => { setCvv(e.target.value.replace(/\D/g, '').slice(0, 4)); if (errors.cvv) setErrors(p => ({ ...p, cvv: undefined })); }}
                  onFocus={() => setIsFlipped(true)}
                  onBlur={() => setIsFlipped(false)}
                  maxLength={4}
                  className={errors.cvv ? 'border-red-500 focus-visible:ring-red-500' : ''}
                  autoComplete="cc-csc"
                />
                {errors.cvv && <p className="text-xs text-red-500">{errors.cvv}</p>}
              </div>
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="emb-cc-owner">Kart Sahibi</Label>
              <Input
                id="emb-cc-owner"
                type="text"
                placeholder="AD SOYAD"
                value={ccOwner}
                onChange={(e) => { setCcOwner(e.target.value.toUpperCase()); if (errors.ccOwner) setErrors(p => ({ ...p, ccOwner: undefined })); }}
                className={`uppercase ${errors.ccOwner ? 'border-red-500 focus-visible:ring-red-500' : ''}`}
                autoComplete="cc-name"
              />
              {errors.ccOwner && <p className="text-xs text-red-500">{errors.ccOwner}</p>}
            </div>

            <div className="flex items-center gap-2">
              <Checkbox id="emb-store-card" checked={storeCard} onCheckedChange={(c) => setStoreCard(c === true)} />
              <Label htmlFor="emb-store-card" className="cursor-pointer text-sm font-normal">Kartimi kaydet</Label>
            </div>
          </div>
        )}

        {/* Installment Selector */}
        {!loadingInstallments && applicableRates.length > 0 && (
          <div className="space-y-3">
            <Label className="text-sm font-semibold text-slate-700 dark:text-slate-300">Taksit Secenekleri</Label>
            <div className="space-y-1.5">
              <label className={`flex cursor-pointer items-center justify-between rounded-xl border-2 p-3 transition-all ${
                installmentCount === 0 ? 'border-[#b8651a] bg-[#b8651a]/5' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'
              }`}>
                <div className="flex items-center gap-3">
                  <input type="radio" name="emb-installment" value={0} checked={installmentCount === 0} onChange={() => setInstallmentCount(0)} className="h-4 w-4 accent-[#b8651a]" />
                  <span className="text-sm font-medium text-slate-900 dark:text-slate-100">Tek Cekim</span>
                </div>
                <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">{formatPrice(totalAmount)}</span>
              </label>
              {applicableRates.filter(r => r.installment_count > 1).map((rate) => (
                <label key={rate.installment_count} className={`flex cursor-pointer items-center justify-between rounded-xl border-2 p-3 transition-all ${
                  installmentCount === rate.installment_count ? 'border-[#b8651a] bg-[#b8651a]/5' : 'border-slate-200 hover:border-slate-300 dark:border-slate-700'
                }`}>
                  <div className="flex items-center gap-3">
                    <input type="radio" name="emb-installment" value={rate.installment_count} checked={installmentCount === rate.installment_count} onChange={() => setInstallmentCount(rate.installment_count)} className="h-4 w-4 accent-[#b8651a]" />
                    <span className="text-sm font-medium text-slate-900 dark:text-slate-100">{rate.installment_count} Taksit</span>
                    <span className="text-xs text-slate-500">({formatPrice((totalAmount * (1 + rate.rate / 100)) / rate.installment_count)}/ay)</span>
                  </div>
                  <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">{formatPrice(totalAmount * (1 + rate.rate / 100))}</span>
                </label>
              ))}
            </div>
          </div>
        )}

        {/* Total with installment */}
        {installmentCount > 0 && ratePercent > 0 && (
          <div className="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-3 dark:bg-slate-800/50">
            <span className="text-sm font-medium text-slate-600 dark:text-slate-400">Odenecek Tutar</span>
            <span className="text-lg font-bold text-[#b8651a] dark:text-[#fbeede]">
              {formatPrice(totalAmount * (1 + ratePercent / 100))}
            </span>
          </div>
        )}
      </div>
    );
  }
);
