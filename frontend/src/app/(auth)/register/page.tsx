'use client';

import { useState, useMemo } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { toast } from 'sonner';
import {
    Eye, EyeOff, Mail, Lock, Phone, User as UserIcon, Building2,
    MapPin, ArrowRight, ArrowLeft, Check, AlertCircle, Loader2,
    Hash, Pencil, Truck,
} from 'lucide-react';
import { api, authApi, RegisterData } from '@/lib/api';
import { useAuth } from '@/contexts/AuthContext';

type AccountType = 'retailer' | 'seller' | null;

const stepsFor = (t: AccountType) => {
    const base = [
        { id: 0, title: 'Hesap tipi' },
        { id: 1, title: 'Vergi No' },
        { id: 2, title: 'Firma' },
        { id: 3, title: 'Hesap' },
    ];
    if (t === 'seller') return base.filter((s) => s.id !== 1);
    return base;
};

function FieldLabel({ children, htmlFor }: { children: React.ReactNode; htmlFor?: string }) {
    return (
        <label htmlFor={htmlFor} className="block text-sm font-semibold mb-1.5" style={{ color: 'var(--fg)' }}>
            {children}
        </label>
    );
}

function ErrorText({ children }: { children?: React.ReactNode }) {
    if (!children) return null;
    return (
        <p className="mt-1 text-xs flex items-center gap-1" style={{ color: 'var(--danger)' }}>
            <AlertCircle className="w-3 h-3" /> {children}
        </p>
    );
}

export default function RegisterPage() {
    const router = useRouter();
    const { setUser } = useAuth();

    const [accountType, setAccountType] = useState<AccountType>(null);
    const [currentStep, setCurrentStep] = useState(0);

    const [vergiNo, setVergiNo] = useState('');

    const [formData, setFormData] = useState({
        email: '',
        phone: '',
        password: '',
        password_confirmation: '',
        business_name: '',
        nickname: '',
        address: '',
        city: '',
        district: '',
    });

    const [error, setError] = useState('');
    const [fieldErrors, setFieldErrors] = useState<Record<string, string>>({});
    const [submitting, setSubmitting] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);
    const [acceptedTerms, setAcceptedTerms] = useState(false);

    const steps = useMemo(() => stepsFor(accountType), [accountType]);
    const currentIndex = steps.findIndex((s) => s.id === currentStep);

    const passwordStrength = useMemo(() => {
        const p = formData.password;
        let s = 0;
        if (p.length >= 8) s += 20;
        if (/[a-z]/.test(p)) s += 20;
        if (/[A-Z]/.test(p)) s += 20;
        if (/\d/.test(p)) s += 20;
        if (/[^a-zA-Z0-9]/.test(p)) s += 20;
        return s;
    }, [formData.password]);

    const strengthLabel =
        passwordStrength <= 20 ? 'Çok zayıf' :
        passwordStrength <= 40 ? 'Zayıf' :
        passwordStrength <= 60 ? 'Orta' :
        passwordStrength <= 80 ? 'İyi' : 'Güçlü';

    const strengthColor =
        passwordStrength <= 40 ? 'var(--danger)' :
        passwordStrength <= 60 ? 'var(--warning)' :
        'var(--success)';

    const set = (key: keyof typeof formData, val: string) =>
        setFormData((p) => ({ ...p, [key]: val }));

    const goTo = (id: number) => {
        setError('');
        setFieldErrors({});
        setCurrentStep(id);
    };

    const handleVergiNext = () => {
        setError('');
        setFieldErrors({});
        if (!/^\d{10}$/.test(vergiNo)) {
            setFieldErrors({ vergi_no: 'Vergi numarası 10 haneli olmalıdır' });
            return;
        }
        goTo(2);
    };

    const handleStartOver = () => {
        setAccountType(null);
        setCurrentStep(0);
        setVergiNo('');
    };

    const validateCompanyStep = () => {
        const errs: Record<string, string> = {};
        if (!formData.business_name) errs.business_name = 'Firma adı gerekli';
        if (!formData.nickname) errs.nickname = 'Görünür isim gerekli';
        else if (formData.nickname.length < 3) errs.nickname = 'En az 3 karakter';
        if (!formData.city) errs.city = 'Şehir gerekli';
        if (!formData.district) errs.district = 'İlçe gerekli';
        if (!formData.address) errs.address = 'Adres gerekli';
        return errs;
    };

    const validateAccountStep = () => {
        const errs: Record<string, string> = {};
        if (!formData.email) errs.email = 'E-posta gerekli';
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) errs.email = 'Geçerli e-posta';
        if (!formData.phone) errs.phone = 'Telefon gerekli';
        if (formData.password.length < 8) errs.password = 'En az 8 karakter';
        if (!/[a-z]/.test(formData.password) || !/[A-Z]/.test(formData.password))
            errs.password = errs.password || 'Büyük ve küçük harf içermeli';
        if (!/\d/.test(formData.password)) errs.password = errs.password || 'Rakam içermeli';
        if (!/[^a-zA-Z0-9]/.test(formData.password)) errs.password = errs.password || 'Özel karakter içermeli';
        if (formData.password !== formData.password_confirmation)
            errs.password_confirmation = 'Şifreler eşleşmiyor';
        if (!acceptedTerms) errs.terms = 'Sözleşmeleri kabul etmelisiniz';
        return errs;
    };

    const handleSubmit = async () => {
        const errs = validateAccountStep();
        if (Object.keys(errs).length > 0) {
            setFieldErrors(errs);
            return;
        }
        setSubmitting(true);
        const payload: RegisterData = {
            email: formData.email,
            password: formData.password,
            password_confirmation: formData.password_confirmation,
            role: accountType as 'retailer' | 'seller',
            business_name: formData.business_name,
            nickname: formData.nickname,
            phone: formData.phone,
            address: formData.address,
            city: formData.city,
            district: formData.district,
            vergi_no: vergiNo || undefined,
        };
        const res = await authApi.register(payload);
        if (res.data) {
            api.setToken(res.data.token);
            setUser(res.data.user);
            toast.success('Kayıt başarılı', { description: 'Hoş geldiniz!' });
            router.push('/documents');
        } else if (res.errors && typeof res.errors === 'object') {
            const be: Record<string, string> = {};
            let first = '';
            for (const [k, v] of Object.entries(res.errors)) {
                if (Array.isArray(v) && v.length) {
                    be[k] = v[0];
                    if (!first) first = v[0];
                }
            }
            setFieldErrors(be);
            setError(first || res.error || 'Kayıt başarısız');
        } else {
            setError(res.error || 'Kayıt başarısız');
        }
        setSubmitting(false);
    };

    const inputClass = 'input';
    const helpText = (s: string) => (
        <p className="mt-1 text-xs" style={{ color: 'var(--fg-soft)' }}>
            {s}
        </p>
    );

    return (
        <div className="w-full">
            {/* Heading */}
            <div className="mb-6">
                <span className="eyebrow">Yeni hesap</span>
                <h1 className="text-2xl font-bold mt-1" style={{ color: 'var(--fg)' }}>
                    i-kirtasiye&apos;ye katıl
                </h1>
                <p className="text-sm mt-1" style={{ color: 'var(--fg-muted)' }}>
                    Onaylı toptancılardan komisyonsuz tedarik için kayıt olun.
                </p>
            </div>

            {/* Step indicator */}
            <div className="flex items-center gap-2 mb-6">
                {steps.map((step, i) => {
                    const completed = i < currentIndex;
                    const current = step.id === currentStep;
                    return (
                        <div key={step.id} className="flex items-center flex-1 gap-2">
                            <div
                                className="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold border transition-colors flex-shrink-0"
                                style={{
                                    background: completed
                                        ? 'var(--accent)'
                                        : current
                                          ? 'var(--accent-soft)'
                                          : 'var(--bg-elevated)',
                                    color: completed
                                        ? 'var(--accent-fg)'
                                        : current
                                          ? 'var(--accent)'
                                          : 'var(--fg-soft)',
                                    borderColor: current || completed ? 'var(--accent)' : 'var(--border)',
                                }}
                            >
                                {completed ? <Check className="w-3.5 h-3.5" /> : i + 1}
                            </div>
                            <span
                                className="text-[11px] font-medium hidden sm:inline whitespace-nowrap"
                                style={{ color: current ? 'var(--accent)' : 'var(--fg-soft)' }}
                            >
                                {step.title}
                            </span>
                            {i < steps.length - 1 && (
                                <div className="h-px flex-1" style={{ background: completed ? 'var(--accent)' : 'var(--border)' }} />
                            )}
                        </div>
                    );
                })}
            </div>

            {/* Banner error */}
            {error && (
                <div className="mb-4 p-3 rounded-md flex items-start gap-2" style={{ background: 'var(--danger-soft)', color: 'var(--danger)' }}>
                    <AlertCircle className="w-4 h-4 flex-shrink-0 mt-0.5" />
                    <p className="text-sm font-medium">{error}</p>
                </div>
            )}

            {/* Step 0: Account type */}
            {currentStep === 0 && (
                <div className="space-y-3">
                    {[
                        {
                            type: 'retailer' as const,
                            Icon: Pencil,
                            title: 'Kırtasiyeci (alıcı)',
                            desc: 'Vergi numaranızı girin, tedarikçilerden komisyonsuz toptan satın alın.',
                            tags: ['Vergi No zorunlu', 'Satın al'],
                        },
                        {
                            type: 'seller' as const,
                            Icon: Truck,
                            title: 'Tedarikçi (satıcı)',
                            desc: 'Ürün kataloğunuza ilan açın, kırtasiyecilere toplu satış yapın.',
                            tags: ['Vergi No opsiyonel', 'Sat'],
                        },
                    ].map(({ type, Icon, title, desc, tags }) => {
                        const sel = accountType === type;
                        return (
                            <button
                                key={type}
                                type="button"
                                onClick={() => setAccountType(type)}
                                className="w-full p-4 text-left rounded-lg transition-colors"
                                style={{
                                    background: sel ? 'var(--accent-soft)' : 'var(--bg-elevated)',
                                    border: `1px solid ${sel ? 'var(--accent)' : 'var(--border)'}`,
                                }}
                            >
                                <div className="flex items-start gap-3">
                                    <div
                                        className="w-10 h-10 rounded-md flex items-center justify-center flex-shrink-0"
                                        style={{
                                            background: sel ? 'var(--accent)' : 'var(--accent-soft)',
                                            color: sel ? 'var(--accent-fg)' : 'var(--accent)',
                                        }}
                                    >
                                        <Icon className="w-5 h-5" />
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <div className="flex items-center justify-between gap-2">
                                            <span className="font-bold text-sm" style={{ color: 'var(--fg)' }}>
                                                {title}
                                            </span>
                                            {sel && (
                                                <span
                                                    className="w-5 h-5 rounded-full flex items-center justify-center"
                                                    style={{ background: 'var(--accent)', color: 'var(--accent-fg)' }}
                                                >
                                                    <Check className="w-3 h-3" />
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-xs mt-1" style={{ color: 'var(--fg-muted)' }}>
                                            {desc}
                                        </p>
                                        <div className="flex flex-wrap gap-1.5 mt-2">
                                            {tags.map((t) => (
                                                <span key={t} className="chip">
                                                    {t}
                                                </span>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            </button>
                        );
                    })}

                    <button
                        type="button"
                        onClick={() => goTo(accountType === 'seller' ? 2 : 1)}
                        disabled={!accountType}
                        className="btn btn-primary w-full mt-2"
                        style={{ height: 44 }}
                    >
                        Devam et <ArrowRight className="w-4 h-4" />
                    </button>
                </div>
            )}

            {/* Step 1: Vergi No (retailer) */}
            {currentStep === 1 && accountType === 'retailer' && (
                <div className="space-y-4">
                    <div>
                        <FieldLabel htmlFor="vergi_no">Vergi numarası</FieldLabel>
                        <div className="relative">
                            <Hash
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="vergi_no"
                                inputMode="numeric"
                                maxLength={10}
                                value={vergiNo}
                                onChange={(e) => {
                                    const v = e.target.value.replace(/\D/g, '').slice(0, 10);
                                    setVergiNo(v);
                                }}
                                placeholder="10 haneli vergi no"
                                className={inputClass}
                                style={{ paddingLeft: 36, fontFamily: 'var(--font-mono)' }}
                            />
                        </div>
                        <ErrorText>{fieldErrors.vergi_no}</ErrorText>
                        {helpText('10 haneli kurum vergi numaranızı girin.')}
                    </div>

                    <div className="flex justify-between gap-2 pt-2">
                        <button type="button" onClick={() => goTo(0)} className="btn btn-ghost">
                            <ArrowLeft className="w-4 h-4" /> Geri
                        </button>
                        <button
                            type="button"
                            onClick={handleVergiNext}
                            disabled={vergiNo.length !== 10}
                            className="btn btn-primary"
                        >
                            Devam et <ArrowRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            )}

            {/* Step 2: Firma bilgileri */}
            {currentStep === 2 && (
                <div className="space-y-4">
                    <div>
                        <FieldLabel htmlFor="business_name">Firma adı</FieldLabel>
                        <div className="relative">
                            <Building2
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="business_name"
                                type="text"
                                value={formData.business_name}
                                onChange={(e) => set('business_name', e.target.value)}
                                placeholder="Mavi Kırtasiye Ltd. Şti."
                                className={inputClass}
                                style={{ paddingLeft: 36 }}
                            />
                        </div>
                        <ErrorText>{fieldErrors.business_name}</ErrorText>
                    </div>

                    <div>
                        <FieldLabel htmlFor="nickname">Görünür isim (rumuz)</FieldLabel>
                        <div className="relative">
                            <UserIcon
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="nickname"
                                type="text"
                                value={formData.nickname}
                                onChange={(e) => set('nickname', e.target.value)}
                                placeholder="Mavi Kırtasiye"
                                className={inputClass}
                                style={{ paddingLeft: 36 }}
                            />
                        </div>
                        {helpText('Pazaryerinde diğer kullanıcılara gösterilecek isim.')}
                        <ErrorText>{fieldErrors.nickname}</ErrorText>
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <FieldLabel htmlFor="city">Şehir</FieldLabel>
                            <input
                                id="city"
                                type="text"
                                value={formData.city}
                                onChange={(e) => set('city', e.target.value)}
                                placeholder="İstanbul"
                                className={inputClass}
                            />
                            <ErrorText>{fieldErrors.city}</ErrorText>
                        </div>
                        <div>
                            <FieldLabel htmlFor="district">İlçe</FieldLabel>
                            <input
                                id="district"
                                type="text"
                                value={formData.district}
                                onChange={(e) => set('district', e.target.value)}
                                placeholder="Kadıköy"
                                className={inputClass}
                            />
                            <ErrorText>{fieldErrors.district}</ErrorText>
                        </div>
                    </div>

                    <div>
                        <FieldLabel htmlFor="address">Adres</FieldLabel>
                        <div className="relative">
                            <MapPin
                                className="absolute left-3 top-3 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <textarea
                                id="address"
                                value={formData.address}
                                onChange={(e) => set('address', e.target.value)}
                                rows={3}
                                placeholder="Açık adres"
                                className="w-full rounded-md border outline-none transition-colors p-3 resize-none"
                                style={{
                                    paddingLeft: 36,
                                    background: 'var(--bg-elevated)',
                                    borderColor: 'var(--border)',
                                    color: 'var(--fg)',
                                }}
                            />
                        </div>
                        <ErrorText>{fieldErrors.address}</ErrorText>
                    </div>

                    <div className="flex justify-between gap-2 pt-2">
                        <button
                            type="button"
                            onClick={() => goTo(accountType === 'seller' ? 0 : 1)}
                            className="btn btn-ghost"
                        >
                            <ArrowLeft className="w-4 h-4" /> Geri
                        </button>
                        <button
                            type="button"
                            onClick={() => {
                                const errs = validateCompanyStep();
                                if (Object.keys(errs).length > 0) {
                                    setFieldErrors(errs);
                                    return;
                                }
                                goTo(3);
                            }}
                            className="btn btn-primary"
                        >
                            Devam et <ArrowRight className="w-4 h-4" />
                        </button>
                    </div>
                </div>
            )}

            {/* Step 3: Hesap */}
            {currentStep === 3 && (
                <div className="space-y-4">
                    <div>
                        <FieldLabel htmlFor="email">E-posta</FieldLabel>
                        <div className="relative">
                            <Mail
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="email"
                                type="email"
                                value={formData.email}
                                onChange={(e) => set('email', e.target.value)}
                                placeholder="info@firma.com"
                                className={inputClass}
                                style={{ paddingLeft: 36 }}
                            />
                        </div>
                        <ErrorText>{fieldErrors.email}</ErrorText>
                    </div>

                    <div>
                        <FieldLabel htmlFor="phone">Telefon</FieldLabel>
                        <div className="relative">
                            <Phone
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="phone"
                                type="tel"
                                value={formData.phone}
                                onChange={(e) => set('phone', e.target.value)}
                                placeholder="0 (5xx) xxx xx xx"
                                className={inputClass}
                                style={{ paddingLeft: 36 }}
                            />
                        </div>
                        <ErrorText>{fieldErrors.phone}</ErrorText>
                    </div>

                    <div>
                        <FieldLabel htmlFor="password">Şifre</FieldLabel>
                        <div className="relative">
                            <Lock
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                value={formData.password}
                                onChange={(e) => set('password', e.target.value)}
                                placeholder="En az 8 karakter"
                                className={inputClass}
                                style={{ paddingLeft: 36, paddingRight: 36 }}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((v) => !v)}
                                className="absolute right-3 top-1/2 -translate-y-1/2"
                                style={{ color: 'var(--fg-soft)' }}
                            >
                                {showPassword ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        {formData.password && (
                            <div className="mt-2">
                                <div className="flex justify-between text-xs mb-1">
                                    <span style={{ color: 'var(--fg-muted)' }}>Şifre gücü</span>
                                    <span style={{ color: strengthColor, fontWeight: 600 }}>{strengthLabel}</span>
                                </div>
                                <div className="h-1 rounded-full overflow-hidden" style={{ background: 'var(--bg-muted)' }}>
                                    <div
                                        style={{
                                            width: `${passwordStrength}%`,
                                            height: '100%',
                                            background: strengthColor,
                                            transition: 'width 200ms',
                                        }}
                                    />
                                </div>
                            </div>
                        )}
                        <ErrorText>{fieldErrors.password}</ErrorText>
                    </div>

                    <div>
                        <FieldLabel htmlFor="password_confirmation">Şifre tekrar</FieldLabel>
                        <div className="relative">
                            <Lock
                                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4"
                                style={{ color: 'var(--fg-soft)' }}
                            />
                            <input
                                id="password_confirmation"
                                type={showConfirm ? 'text' : 'password'}
                                value={formData.password_confirmation}
                                onChange={(e) => set('password_confirmation', e.target.value)}
                                placeholder="Şifreyi tekrar girin"
                                className={inputClass}
                                style={{ paddingLeft: 36, paddingRight: 36 }}
                            />
                            <button
                                type="button"
                                onClick={() => setShowConfirm((v) => !v)}
                                className="absolute right-3 top-1/2 -translate-y-1/2"
                                style={{ color: 'var(--fg-soft)' }}
                            >
                                {showConfirm ? <EyeOff className="w-4 h-4" /> : <Eye className="w-4 h-4" />}
                            </button>
                        </div>
                        <ErrorText>{fieldErrors.password_confirmation}</ErrorText>
                    </div>

                    <label className="flex items-start gap-2 text-sm cursor-pointer">
                        <input
                            type="checkbox"
                            checked={acceptedTerms}
                            onChange={(e) => setAcceptedTerms(e.target.checked)}
                            className="mt-0.5"
                        />
                        <span style={{ color: 'var(--fg-muted)' }}>
                            <Link href="/legal/sozlesme" className="font-semibold" style={{ color: 'var(--accent)' }}>
                                Üyelik sözleşmesi
                            </Link>{' '}
                            ve{' '}
                            <Link href="/legal/gizlilik" className="font-semibold" style={{ color: 'var(--accent)' }}>
                                gizlilik politikasını
                            </Link>{' '}
                            okudum, kabul ediyorum.
                        </span>
                    </label>
                    <ErrorText>{fieldErrors.terms}</ErrorText>

                    <div className="flex justify-between gap-2 pt-2">
                        <button type="button" onClick={() => goTo(2)} className="btn btn-ghost">
                            <ArrowLeft className="w-4 h-4" /> Geri
                        </button>
                        <button
                            type="button"
                            onClick={handleSubmit}
                            disabled={submitting}
                            className="btn btn-primary"
                        >
                            {submitting ? (
                                <>
                                    <Loader2 className="w-4 h-4 animate-spin" /> Kaydediliyor
                                </>
                            ) : (
                                <>
                                    Hesap oluştur <ArrowRight className="w-4 h-4" />
                                </>
                            )}
                        </button>
                    </div>
                </div>
            )}

            {/* Footer link */}
            <div className="text-center mt-6 text-sm" style={{ color: 'var(--fg-muted)' }}>
                Zaten hesabın var mı?{' '}
                <Link href="/login" className="font-semibold" style={{ color: 'var(--accent)' }}>
                    Giriş yap
                </Link>
                {accountType && (
                    <>
                        {' · '}
                        <button
                            type="button"
                            onClick={handleStartOver}
                            className="font-semibold"
                            style={{ color: 'var(--fg-soft)' }}
                        >
                            Baştan başla
                        </button>
                    </>
                )}
            </div>
        </div>
    );
}
