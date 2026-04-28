'use client';

import { useState, useEffect } from 'react';
import {
    Send,
    AlertCircle,
    Loader2,
    Phone,
    Mail,
    MapPin,
    Clock,
    MessageCircle,
    Facebook,
    Twitter,
    Instagram,
    Linkedin,
    ArrowRight,
    Shield,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Skeleton } from '@/components/ui/skeleton';
import { toast } from 'sonner';
import { cmsApi, CmsPage, FooterSettings, CmsLayoutResponse } from '@/lib/api';

interface ContactForm {
    name: string;
    email: string;
    phone: string;
    subject: string;
    message: string;
}

export default function ContactPage() {
    const [page, setPage] = useState<CmsPage | null>(null);
    const [footer, setFooter] = useState<FooterSettings | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [formData, setFormData] = useState<ContactForm>({
        name: '',
        email: '',
        phone: '',
        subject: '',
        message: '',
    });

    useEffect(() => {
        const load = async () => {
            try {
                const [pageRes, layoutRes] = await Promise.all([
                    cmsApi.getPage('iletisim'),
                    cmsApi.getLayout(),
                ]);

                const pageBody = pageRes.data as unknown as { data?: CmsPage } | undefined;
                if (pageBody?.data) {
                    setPage(pageBody.data);
                }

                const layoutBody = layoutRes.data as unknown as { data?: CmsLayoutResponse } | CmsLayoutResponse;
                const layout = (layoutBody as { data?: CmsLayoutResponse }).data ?? (layoutBody as CmsLayoutResponse);
                if (layout?.footer_settings) {
                    setFooter(layout.footer_settings);
                }

                if (!pageBody?.data && !layout?.footer_settings) {
                    setError(true);
                }
            } catch {
                setError(true);
            } finally {
                setLoading(false);
            }
        };
        load();
    }, []);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        await new Promise((resolve) => setTimeout(resolve, 800));

        toast.success('Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.');
        setFormData({ name: '', email: '', phone: '', subject: '', message: '' });
        setIsSubmitting(false);
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-white">
                <div className="bg-gradient-to-br from-slate-900 via-[#0a4f63] to-[#b8651a] py-20">
                    <div className="max-w-4xl mx-auto px-4 text-center space-y-3">
                        <Skeleton className="h-10 w-48 mx-auto bg-white/10" />
                        <Skeleton className="h-5 w-80 mx-auto bg-white/10" />
                    </div>
                </div>
                <div className="max-w-6xl mx-auto px-4 py-16 grid lg:grid-cols-5 gap-8">
                    <div className="lg:col-span-2 space-y-4">
                        {[1, 2, 3, 4].map(i => <Skeleton key={i} className="h-28 w-full rounded-2xl" />)}
                    </div>
                    <div className="lg:col-span-3">
                        <Skeleton className="h-[500px] w-full rounded-2xl" />
                    </div>
                </div>
            </div>
        );
    }

    if (error && !footer && !page) {
        return (
            <div className="min-h-screen bg-white flex items-center justify-center">
                <div className="text-center px-4">
                    <AlertCircle className="w-12 h-12 text-slate-400 mx-auto mb-4" />
                    <h2 className="text-xl font-semibold text-slate-900 mb-2">Sayfa yüklenemedi</h2>
                    <p className="text-slate-500 mb-4">Lütfen daha sonra tekrar deneyin.</p>
                    <Button variant="outline" onClick={() => window.location.reload()}>
                        Tekrar Dene
                    </Button>
                </div>
            </div>
        );
    }

    const socialLinks = [
        { url: footer?.facebook_url, icon: Facebook, label: 'Facebook' },
        { url: footer?.twitter_url, icon: Twitter, label: 'Twitter' },
        { url: footer?.instagram_url, icon: Instagram, label: 'Instagram' },
        { url: footer?.linkedin_url, icon: Linkedin, label: 'LinkedIn' },
    ].filter(s => s.url);

    return (
        <div className="min-h-screen bg-white">
            {/* Hero */}
            <section className="relative py-16 sm:py-20 overflow-hidden">
                <div className="absolute inset-0 bg-gradient-to-br from-slate-900 via-[#0a4f63] to-[#b8651a]" />

                {/* Mesh blobs */}
                <div className="absolute top-0 right-0 w-[50%] h-[50%] bg-[#d99248]/20 rounded-full blur-[120px]" />
                <div className="absolute bottom-0 left-0 w-[40%] h-[40%] bg-[#b8651a]/20 rounded-full blur-[100px]" />

                {/* Dot grid */}
                <div
                    className="absolute inset-0 opacity-[0.05]"
                    style={{
                        backgroundImage: 'radial-gradient(circle at 1px 1px, white 1px, transparent 0)',
                        backgroundSize: '32px 32px',
                    }}
                />

                <div className="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-sm border border-white/20 mb-5">
                        <MessageCircle className="w-3.5 h-3.5 text-accent" />
                        <span className="text-xs font-semibold text-white tracking-wide">İLETİŞİM</span>
                    </div>
                    <h1 className="text-4xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">
                        {page?.title || 'Bize Ulaşın'}
                    </h1>
                    <p className="text-lg text-white/75 max-w-2xl mx-auto leading-relaxed">
                        {page?.excerpt || 'Sorularınız, önerileriniz veya destek talepleriniz için buradayız.'}
                    </p>
                </div>
            </section>

            {/* Content */}
            <section className="py-12 md:py-16 bg-gradient-to-b from-slate-50/50 to-white">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="grid lg:grid-cols-5 gap-6 lg:gap-8">
                        {/* Left: Contact Info Cards */}
                        <div className="lg:col-span-2 space-y-4">
                            {footer?.phone && (
                                <ContactCard
                                    icon={Phone}
                                    label="Telefon"
                                    value={footer.phone}
                                    href={`tel:${footer.phone_raw || footer.phone}`}
                                    accent="cyan"
                                />
                            )}

                            {footer?.email && (
                                <ContactCard
                                    icon={Mail}
                                    label="E-posta"
                                    value={footer.email}
                                    href={`mailto:${footer.email}`}
                                    accent="cyan"
                                />
                            )}

                            {footer?.address && (
                                <ContactCard
                                    icon={MapPin}
                                    label="Adres"
                                    value={footer.address}
                                    accent="cyan"
                                />
                            )}

                            {(footer?.hours_weekday || footer?.hours_saturday || footer?.hours_sunday) && (
                                <div className="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-[#b8651a]/30 transition-all">
                                    <div className="flex items-start gap-4">
                                        <div className="w-11 h-11 rounded-xl bg-[#fbeede] flex items-center justify-center flex-shrink-0">
                                            <Clock className="w-5 h-5 text-[#b8651a]" />
                                        </div>
                                        <div className="flex-1">
                                            <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">
                                                Çalışma Saatleri
                                            </p>
                                            <div className="space-y-1.5">
                                                {footer.hours_weekday && (
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-slate-500">Hafta İçi</span>
                                                        <span className="font-semibold text-slate-900">{footer.hours_weekday}</span>
                                                    </div>
                                                )}
                                                {footer.hours_saturday && (
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-slate-500">Cumartesi</span>
                                                        <span className="font-semibold text-slate-900">{footer.hours_saturday}</span>
                                                    </div>
                                                )}
                                                {footer.hours_sunday && (
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-slate-500">Pazar</span>
                                                        <span className="font-semibold text-slate-900">{footer.hours_sunday}</span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Social Links */}
                            {socialLinks.length > 0 && (
                                <div className="bg-white rounded-2xl border border-slate-200 p-5">
                                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
                                        Sosyal Medya
                                    </p>
                                    <div className="flex items-center gap-2">
                                        {socialLinks.map(({ url, icon: Icon, label }) => (
                                            <a
                                                key={label}
                                                href={url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label={label}
                                                className="w-10 h-10 rounded-xl bg-slate-50 hover:bg-[#b8651a] text-slate-500 hover:text-white flex items-center justify-center transition-all"
                                            >
                                                <Icon className="w-4 h-4" />
                                            </a>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Trust badge */}
                            <div className="bg-gradient-to-br from-[#fbeede] to-white rounded-2xl border border-[#fbeede]/50 p-5">
                                <div className="flex items-start gap-3">
                                    <div className="w-9 h-9 rounded-lg bg-[#b8651a] flex items-center justify-center flex-shrink-0">
                                        <Shield className="w-4 h-4 text-white" />
                                    </div>
                                    <div>
                                        <p className="text-sm font-bold text-slate-900 mb-1">Güvenli İletişim</p>
                                        <p className="text-xs text-slate-600 leading-relaxed">
                                            Tüm iletişim bilgileriniz KVKK kapsamında gizli tutulur.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Right: Form */}
                        <div className="lg:col-span-3">
                            <div className="bg-white rounded-2xl border border-slate-200 p-6 sm:p-8 md:p-10 shadow-sm">
                                <div className="mb-6">
                                    <h2 className="text-2xl font-extrabold text-slate-900 mb-2">Bize Yazın</h2>
                                    <p className="text-slate-500">Formu doldurun, en kısa sürede size dönüş yapalım.</p>
                                </div>

                                <form onSubmit={handleSubmit} className="space-y-5">
                                    <div className="grid sm:grid-cols-2 gap-4">
                                        <FormField label="Ad Soyad" htmlFor="name" required>
                                            <Input
                                                id="name"
                                                value={formData.name}
                                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                                placeholder="Adınız Soyadınız"
                                                required
                                                className="h-11"
                                            />
                                        </FormField>
                                        <FormField label="E-posta" htmlFor="email" required>
                                            <Input
                                                id="email"
                                                type="email"
                                                value={formData.email}
                                                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                                                placeholder="ornek@email.com"
                                                required
                                                className="h-11"
                                            />
                                        </FormField>
                                    </div>

                                    <div className="grid sm:grid-cols-2 gap-4">
                                        <FormField label="Telefon" htmlFor="phone">
                                            <Input
                                                id="phone"
                                                type="tel"
                                                value={formData.phone}
                                                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                                                placeholder="0555 123 45 67"
                                                className="h-11"
                                            />
                                        </FormField>
                                        <FormField label="Konu" htmlFor="subject" required>
                                            <Input
                                                id="subject"
                                                value={formData.subject}
                                                onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                                                placeholder="Mesaj konusu"
                                                required
                                                className="h-11"
                                            />
                                        </FormField>
                                    </div>

                                    <FormField label="Mesajınız" htmlFor="message" required>
                                        <Textarea
                                            id="message"
                                            value={formData.message}
                                            onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                                            placeholder="Mesajınızı buraya yazın..."
                                            rows={6}
                                            required
                                        />
                                    </FormField>

                                    <div className="flex items-center justify-between pt-2 flex-wrap gap-3">
                                        <p className="text-xs text-slate-400 max-w-sm">
                                            Formu göndererek KVKK aydınlatma metnini kabul etmiş olursunuz.
                                        </p>
                                        <Button
                                            type="submit"
                                            disabled={isSubmitting}
                                            className="h-12 px-8 bg-[#b8651a] hover:bg-[#934f12] text-white font-bold rounded-xl shadow-lg shadow-[#b8651a]/20 gap-2 transition-all hover:shadow-xl"
                                        >
                                            {isSubmitting ? (
                                                <>
                                                    <Loader2 className="w-4 h-4 animate-spin" />
                                                    Gönderiliyor...
                                                </>
                                            ) : (
                                                <>
                                                    <Send className="w-4 h-4" />
                                                    Mesajı Gönder
                                                    <ArrowRight className="w-4 h-4" />
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
        </div>
    );
}

/* ─── Primitives ─── */

function ContactCard({
    icon: Icon,
    label,
    value,
    href,
}: {
    icon: React.ElementType;
    label: string;
    value: string;
    href?: string;
    accent?: 'cyan';
}) {
    const Wrap = href ? 'a' : 'div';
    return (
        <Wrap
            href={href}
            className={`block bg-white rounded-2xl border border-slate-200 p-5 transition-all ${href ? 'hover:shadow-md hover:border-[#b8651a]/30 hover:-translate-y-0.5' : ''}`}
        >
            <div className="flex items-start gap-4">
                <div className="w-11 h-11 rounded-xl bg-[#fbeede] flex items-center justify-center flex-shrink-0">
                    <Icon className="w-5 h-5 text-[#b8651a]" />
                </div>
                <div className="flex-1 min-w-0">
                    <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">
                        {label}
                    </p>
                    <p className="text-[15px] font-semibold text-slate-900 break-words">{value}</p>
                </div>
            </div>
        </Wrap>
    );
}

function FormField({
    label,
    htmlFor,
    required,
    children,
}: {
    label: string;
    htmlFor: string;
    required?: boolean;
    children: React.ReactNode;
}) {
    return (
        <div className="space-y-1.5">
            <Label htmlFor={htmlFor} className="text-sm font-semibold text-slate-700">
                {label}
                {required && <span className="text-[#b8651a] ml-0.5">*</span>}
            </Label>
            {children}
        </div>
    );
}
