"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
import { api } from "@/lib/api";

import LandingHeader from "@/components/landing/LandingHeader";
import HeroSection from "@/components/landing/HeroSection";
import FeaturesSection from "@/components/landing/FeaturesSection";
import WhySection from "@/components/landing/WhySection";
import TestimonialsSection from "@/components/landing/TestimonialsSection";
import FaqSection from "@/components/landing/FaqSection";
import CtaSection from "@/components/landing/CtaSection";
import LandingFooter from "@/components/landing/LandingFooter";

// ─── Types ──────────────────────────────────────────────────────────────────

interface HeroContent {
  title: string;
  highlight_word: string;
  subtitle: string;
  cta_primary_text: string;
  cta_secondary_text: string;
  social_proof_text: string;
  social_proof_rating: string;
}

interface HowItWorksStep {
  title: string;
  description: string;
}

interface HowItWorksContent {
  section_title: string;
  section_subtitle: string;
  steps: HowItWorksStep[];
  vergi_no_card_title: string;
  vergi_no_card_subtitle: string;
  vergi_no_checklist: string[];
  trusted_by_text: string;
  trusted_by_cities: string[];
}

interface AdvantageFeature {
  icon: string;
  title: string;
  description: string;
}

interface AdvantagesContent {
  section_title: string;
  section_subtitle: string;
  features: AdvantageFeature[];
}

interface StatItem {
  value: number;
  suffix: string;
  label: string;
}

interface StatsContent {
  section_title: string;
  section_subtitle: string;
  items: StatItem[];
}

interface TestimonialItem {
  quote: string;
  author: string;
  role: string;
}

interface TestimonialsContent {
  section_title: string;
  section_subtitle: string;
  items: TestimonialItem[];
}

interface FaqItem {
  question: string;
  answer: string;
}

interface FaqContent {
  section_title: string;
  items: FaqItem[];
}

interface CtaContent {
  title: string;
  subtitle: string;
  cta_primary_text: string;
  cta_secondary_text: string;
}

interface LandingContent {
  hero: HeroContent;
  how_it_works: HowItWorksContent;
  advantages: AdvantagesContent;
  stats: StatsContent;
  testimonials: TestimonialsContent;
  faq: FaqContent;
  cta: CtaContent;
}

// ─── Defaults ───────────────────────────────────────────────────────────────

const DEFAULT_CONTENT: LandingContent = {
  hero: {
    title: "Türkiye'nin {highlight} B2B Kırtasiye Pazaryeri",
    highlight_word: "Komisyonsuz",
    subtitle:
      "Sabit aylık üyelik, yüzdesel komisyon yok. Onaylı tedarikçilerden vergi numarası doğrulamalı toptan kırtasiye, ofis ve okul malzemesi tedariki.",
    cta_primary_text: "Kırtasiyeci olarak başvur",
    cta_secondary_text: "Tedarikçi olarak kayıt ol",
    social_proof_text: "1.000+ kırtasiyeci güveniyor",
    social_proof_rating: "4.9",
  },
  how_it_works: {
    section_title: "3 Adımda Toptan Tedarike Başlayın",
    section_subtitle:
      "Vergi numaranız ile dakikalar içinde sisteme dahil olun, onaylı tedarikçilerden güvenli toptan tedarike başlayın.",
    steps: [
      {
        title: "Vergi No ile hızlı kayıt",
        description:
          "10 haneli vergi numaranızı girin, kurumunuz otomatik olarak doğrulansın. Onaylı listede iseniz hesabınız anında aktif olur.",
      },
      {
        title: "Kataloğu keşfet, sepete ekle",
        description:
          "Binlerce SKU arasından arama yapın, en uygun fiyatlı satıcıyı seçin. Excel ile toplu sipariş oluşturun.",
      },
      {
        title: "Güvenle al / sat",
        description:
          "Güvenli ödeme altyapısı ile siparişinizi tamamlayın. Hakedişler anında hesabınıza yansır.",
      },
    ],
    vergi_no_card_title: "Vergi No Doğrulama",
    vergi_no_card_subtitle: "Sadece onaylı kırtasiyeciler",
    vergi_no_checklist: [
      "Gerçek zamanlı vergi numarası doğrulaması",
      "Onaylı kurumlar listesi (whitelist)",
      "Anında hesap aktivasyonu",
    ],
    trusted_by_text: "Türkiye'nin dört bir yanından kırtasiyeciler güveniyor",
    trusted_by_cities: [
      "İstanbul",
      "Ankara",
      "İzmir",
      "Bursa",
      "Antalya",
      "Adana",
    ],
  },
  advantages: {
    section_title: "Neden i-kirtasiye?",
    section_subtitle:
      "Kırtasiyeciler ve tedarikçiler için tasarlanmış komisyonsuz B2B pazaryeri.",
    features: [
      {
        icon: "trending-up",
        title: "En uygun toptan fiyat",
        description:
          "Aynı ürün için tüm tedarikçilerin ilanlarını karşılaştırın, piyasanın en düşük fiyatını yakalayın.",
      },
      {
        icon: "box",
        title: "Stokları toptan eritin",
        description:
          "Fazla stoklarınızı kırtasiyecilere toptan satın, raf ömrü sorununu kâra dönüştürün.",
      },
      {
        icon: "file-check",
        title: "Otomatik e-fatura",
        description:
          "Her işlem için otomatik e-fatura. Muhasebe entegrasyonu ile iş yükünüzü azaltın.",
      },
      {
        icon: "truck",
        title: "Ücretsiz kargo",
        description:
          "1.500₺ üzeri tüm siparişlerde kargo satıcı tarafından karşılanır. Alıcıya ek maliyet yok.",
      },
      {
        icon: "shield",
        title: "Komisyonsuz satış",
        description:
          "Yüzdesel komisyon yok. Sadece sabit aylık üyelik bedeli.",
      },
    ],
  },
  stats: {
    section_title: "Rakamlarla i-kirtasiye",
    section_subtitle: "Her gün büyüyen güvenilir kırtasiye ağı",
    items: [
      { value: 1250, suffix: "+", label: "Kayıtlı kırtasiyeci" },
      { value: 5000, suffix: "+", label: "Aktif ilan" },
      { value: 200, suffix: "+", label: "Onaylı tedarikçi" },
      { value: 0, suffix: "%", label: "Komisyon oranı" },
    ],
  },
  testimonials: {
    section_title: "Kırtasiyeciler ne diyor?",
    section_subtitle:
      "Platformumuzu kullanan kırtasiyecilerin ve tedarikçilerin deneyimlerini okuyun.",
    items: [
      {
        quote:
          "Toptan defter ve kalem tedarikinde fiyatları çok rahat karşılaştırabiliyorum. Vergi no doğrulaması da güven veriyor.",
        author: "Mavi Kırtasiye",
        role: "Kırtasiyeci, İstanbul",
      },
      {
        quote:
          "Excel ile binlerce SKU'yu tek seferde sepete attım. 24 saatte teslim aldık.",
        author: "Defter Dünyası",
        role: "Kırtasiyeci, Ankara",
      },
      {
        quote:
          "Tedarikçi olarak ilanlarımız hızla görünür hale geldi. Komisyonsuz olması büyük avantaj.",
        author: "Akademi Toptan",
        role: "Tedarikçi, İzmir",
      },
    ],
  },
  faq: {
    section_title: "Sıkça sorulan sorular",
    items: [
      {
        question: "Onaylı listede değilim, nasıl başvurabilirim?",
        answer:
          "İletişim formu üzerinden firma bilgilerinizi iletin. Onayınız tamamlandığında size geri dönüş yapılır ve vergi numaranız ile kayıt olabilirsiniz.",
      },
      {
        question: "Vergi numaramı nasıl doğrulayabilirim?",
        answer:
          "Kayıt sayfasında 10 haneli vergi numaranızı girin ve 'Doğrula' butonuna basın. Sistem onaylı listede olup olmadığınızı anında kontrol eder.",
      },
      {
        question: "Üyelik ücreti var mı?",
        answer:
          "Kayıt ücretsizdir. Aktif satış yapan tedarikçiler için sabit aylık üyelik bedeli vardır. Yüzdesel komisyon alınmaz.",
      },
      {
        question: "Kargo nasıl çalışıyor?",
        answer:
          "1.500₺ üzeri siparişlerde kargo satıcı tarafından karşılanır. Belirli ürünlerde 24 saatte teslim seçeneği mevcuttur.",
      },
    ],
  },
  cta: {
    title: "Hemen ücretsiz başlayın",
    subtitle:
      "Vergi numaranız ile dakikalar içinde kayıt olun. Onaylı kırtasiyeciler için anında aktivasyon.",
    cta_primary_text: "Kırtasiyeci olarak başvur",
    cta_secondary_text: "Tedarikçi olarak kayıt ol",
  },
};

// ─── Loading skeleton ───────────────────────────────────────────────────────

function LandingSkeleton() {
  return (
    <div className="min-h-screen bg-slate-900">
      {/* Header skeleton */}
      <div className="fixed top-0 left-0 right-0 z-50 h-16 lg:h-20" />

      {/* Hero skeleton */}
      <div className="min-h-screen flex items-center justify-center">
        <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 w-full">
          <div className="grid lg:grid-cols-12 gap-12 items-center">
            <div className="lg:col-span-7 space-y-6">
              <div className="h-4 w-48 bg-white/[0.06] rounded-full animate-pulse" />
              <div className="space-y-3">
                <div className="h-12 w-full bg-white/[0.06] rounded-xl animate-pulse" />
                <div className="h-12 w-3/4 bg-white/[0.06] rounded-xl animate-pulse" />
              </div>
              <div className="h-6 w-2/3 bg-white/[0.06] rounded-lg animate-pulse" />
              <div className="flex gap-4">
                <div className="h-14 w-44 bg-white/[0.06] rounded-xl animate-pulse" />
                <div className="h-14 w-44 bg-white/[0.06] rounded-xl animate-pulse" />
              </div>
            </div>
            <div className="lg:col-span-5 hidden lg:block">
              <div className="h-[420px] bg-white/[0.04] rounded-2xl border border-white/[0.06] animate-pulse" />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

// ─── Component ──────────────────────────────────────────────────────────────

export default function LandingClient() {
  const { isAuthenticated, isLoading: authLoading } = useAuth();
  const router = useRouter();
  const [content, setContent] = useState<LandingContent>(DEFAULT_CONTENT);

  // Auth redirect
  useEffect(() => {
    if (!authLoading && isAuthenticated) {
      router.push("/market");
    }
  }, [authLoading, isAuthenticated, router]);

  // Fetch CMS content
  useEffect(() => {
    let cancelled = false;

    async function fetchContent() {
      try {
        const response = await api.get<LandingContent>("/landing-content");
        if (!cancelled && response.data) {
          setContent((prev) => ({
            hero: { ...prev.hero, ...response.data!.hero },
            how_it_works: {
              ...prev.how_it_works,
              ...response.data!.how_it_works,
            },
            advantages: { ...prev.advantages, ...response.data!.advantages },
            stats: { ...prev.stats, ...response.data!.stats },
            testimonials: {
              ...prev.testimonials,
              ...response.data!.testimonials,
            },
            faq: { ...prev.faq, ...response.data!.faq },
            cta: { ...prev.cta, ...response.data!.cta },
          }));
        }
      } catch {
        // Defaults are already rendered
      }
    }

    fetchContent();
    return () => {
      cancelled = true;
    };
  }, []);

  // Show skeleton during auth check
  if (authLoading) {
    return <LandingSkeleton />;
  }

  // Don't render for authenticated users (they'll be redirected)
  if (isAuthenticated) {
    return <LandingSkeleton />;
  }

  return (
    <div
      className="min-h-screen overflow-x-hidden"
      style={{
        // Smooth scrolling for anchor links
        scrollBehavior: "smooth",
      }}
    >
      <LandingHeader />

      <main>
        <HeroSection content={content.hero} />

        <FeaturesSection content={content.how_it_works} />

        <WhySection
          title={content.advantages.section_title}
          subtitle={content.advantages.section_subtitle}
          features={content.advantages.features}
        />

        <TestimonialsSection
          title={content.testimonials.section_title}
          subtitle={content.testimonials.section_subtitle}
          items={content.testimonials.items}
        />

        <FaqSection
          title={content.faq.section_title}
          items={content.faq.items}
        />

        <CtaSection content={content.cta} stats={content.stats.items} />
      </main>

      <LandingFooter />
    </div>
  );
}
