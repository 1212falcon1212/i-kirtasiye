"use client";

import { useState, useCallback, useEffect, useRef } from "react";
import { useRouter } from "next/navigation";
import { motion } from "framer-motion";
import { ArrowRight, BadgeCheck, Building2, Box, CreditCard, TrendingUp } from "lucide-react";
import type { LucideIcon } from "lucide-react";

interface StatItem {
  value: number;
  suffix: string;
  label: string;
}

interface CtaContent {
  title: string;
  subtitle: string;
  cta_primary_text: string;
  cta_secondary_text: string;
}

interface CtaSectionProps {
  content: CtaContent;
  stats: StatItem[];
}

const STAT_ICONS: LucideIcon[] = [Building2, Box, CreditCard, TrendingUp];

function AnimatedCounter({
  value,
  suffix,
  label,
  icon: Icon,
}: {
  value: number;
  suffix: string;
  label: string;
  icon: LucideIcon;
}) {
  const [count, setCount] = useState(0);
  const ref = useRef<HTMLDivElement>(null);
  const hasAnimated = useRef(false);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !hasAnimated.current) {
          hasAnimated.current = true;
          const duration = 2500;
          let startTime: number | null = null;

          const animate = (currentTime: number) => {
            if (!startTime) startTime = currentTime;
            const progress = Math.min((currentTime - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 4);
            setCount(Math.floor(value * eased));
            if (progress < 1) requestAnimationFrame(animate);
          };
          requestAnimationFrame(animate);
        }
      },
      { threshold: 0.5 }
    );

    observer.observe(el);
    return () => observer.disconnect();
  }, [value]);

  return (
    <div ref={ref} className="text-center group">
      <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/[0.08] mb-4 group-hover:bg-white/[0.12] transition-colors">
        <Icon className="w-7 h-7 text-accent" />
      </div>
      <div className="text-3xl md:text-4xl font-extrabold text-white mb-1.5 tabular-nums">
        {count.toLocaleString("tr-TR")}
        {suffix}
      </div>
      <div className="text-accent-soft/60 text-sm font-medium">{label}</div>
    </div>
  );
}

export default function CtaSection({ content, stats }: CtaSectionProps) {
  const [gln, setGln] = useState("");
  const router = useRouter();

  const handleSubmit = useCallback(
    (e: React.FormEvent) => {
      e.preventDefault();
      const trimmed = gln.trim();
      if (trimmed.length > 0) {
        router.push(`/register?gln=${encodeURIComponent(trimmed)}`);
      } else {
        router.push("/register");
      }
    },
    [gln, router]
  );

  return (
    <section className="relative overflow-hidden">
      {/* Stats section */}
      <div className="py-24 lg:py-28 bg-gradient-to-br from-slate-900 via-[#1a0a14] to-[#2d0a1e] relative">
        {/* Background pattern */}
        <div
          className="absolute inset-0 opacity-[0.04]"
          style={{
            backgroundImage:
              "radial-gradient(circle at 1.5px 1.5px, white 1px, transparent 0)",
            backgroundSize: "32px 32px",
          }}
        />
        <div className="absolute top-0 left-1/4 w-96 h-96 bg-[#b8651a]/10 rounded-full blur-[120px]" />
        <div className="absolute bottom-0 right-1/4 w-80 h-80 bg-purple-600/8 rounded-full blur-[100px]" />

        <div className="relative max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-60px" }}
            className="text-center mb-16"
          >
            <h2 className="text-3xl sm:text-4xl font-extrabold text-white mb-4">
              Rakamlarla i-Depo
            </h2>
            <p className="text-lg text-slate-400 max-w-lg mx-auto">
              Her gün büyüyen güvenilir eczacı ağı
            </p>
          </motion.div>

          <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            {stats.map((stat, i) => (
              <AnimatedCounter
                key={i}
                value={stat.value}
                suffix={stat.suffix}
                label={stat.label}
                icon={STAT_ICONS[i] || Building2}
              />
            ))}
          </div>
        </div>
      </div>

      {/* CTA section */}
      <div className="py-24 lg:py-28 bg-gradient-to-br from-[#b8651a] via-[#a5165a] to-[#831843] relative">
        {/* Background */}
        <div className="absolute inset-0 opacity-10">
          <div
            className="absolute inset-0"
            style={{
              backgroundImage:
                "radial-gradient(circle at 2px 2px, white 1px, transparent 0)",
              backgroundSize: "48px 48px",
            }}
          />
        </div>
        <div className="absolute top-0 right-0 w-[500px] h-[500px] bg-white/5 rounded-full blur-3xl -translate-y-1/2 translate-x-1/2" />

        <div className="relative max-w-4xl mx-auto px-5 sm:px-6 lg:px-8 text-center">
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-60px" }}
          >
            <div className="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-white/[0.12] mb-6">
              <BadgeCheck className="w-8 h-8 text-white" />
            </div>

            <h2 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white leading-tight mb-5">
              {content.title}
            </h2>
            <p className="text-lg sm:text-xl text-accent-soft/70 max-w-2xl mx-auto leading-relaxed mb-10">
              {content.subtitle}
            </p>

            {/* Verification form */}
            <form
              onSubmit={handleSubmit}
              className="max-w-md mx-auto flex flex-col sm:flex-row gap-3"
            >
              <input
                type="text"
                value={gln}
                onChange={(e) => setGln(e.target.value)}
                placeholder="Vergi numaranız"
                maxLength={13}
                className="flex-1 px-5 py-4 bg-white/[0.12] border border-white/[0.2] rounded-xl text-white placeholder:text-white/40 font-mono focus:outline-none focus:border-white/40 focus:ring-2 focus:ring-white/10 transition-all backdrop-blur-sm"
              />
              <button
                type="submit"
                className="px-7 py-4 bg-white text-[#b8651a] rounded-xl font-bold shadow-xl shadow-black/10 hover:bg-accent-soft transition-all duration-200 flex items-center justify-center gap-2 whitespace-nowrap active:scale-[0.98]"
              >
                {content.cta_primary_text}
                <ArrowRight className="w-5 h-5" />
              </button>
            </form>

            <p className="mt-5 text-sm text-accent-soft/40">
              Üyelik tamamen ücretsiz. Komisyon yok, sabit 50 TL hizmet bedeli.
            </p>
          </motion.div>
        </div>
      </div>
    </section>
  );
}
