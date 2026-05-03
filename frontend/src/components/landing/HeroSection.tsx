"use client";

import { useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { motion } from "framer-motion";
import {
  ArrowRight,
  ChevronDown,
  ShieldCheck,
  BadgeCheck,
  Sparkles,
} from "lucide-react";

interface HeroContent {
  title: string;
  highlight_word: string;
  subtitle: string;
  cta_primary_text: string;
  cta_secondary_text: string;
  social_proof_text: string;
  social_proof_rating: string;
}

interface HeroSectionProps {
  content: HeroContent & { image?: string };
}

function renderTitle(title: string, highlight: string) {
  const parts = title.split("{highlight}");
  if (parts.length === 1) return <>{title}</>;
  return (
    <>
      {parts[0]}
      <span className="relative inline-block">
        <span className="relative z-10 bg-gradient-to-r from-[#ea580c] to-[#d99248] bg-clip-text text-transparent">
          {highlight}
        </span>
        <motion.span
          className="absolute -bottom-1 left-0 right-0 h-3 bg-accent-soft/40 rounded-full -z-0"
          initial={{ scaleX: 0 }}
          animate={{ scaleX: 1 }}
          transition={{ delay: 0.8, duration: 0.6, ease: "easeOut" }}
          style={{ originX: 0 }}
        />
      </span>
      {parts[1]}
    </>
  );
}

export default function HeroSection({ content }: HeroSectionProps) {
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
    <section className="relative min-h-[100dvh] flex items-center overflow-hidden">
      {/* Background layers */}
      <div className="absolute inset-0">
        {/* Background image from API */}
        {content.image && (
          <div
            className="absolute inset-0 bg-cover bg-center bg-no-repeat"
            style={{ backgroundImage: `url(${content.image})` }}
          />
        )}
        {/* Primary gradient overlay */}
        <div className={`absolute inset-0 ${content.image ? 'bg-gradient-to-r from-slate-900/90 via-slate-900/70 to-slate-900/50' : 'bg-gradient-to-br from-slate-900 via-[#1a0a14] to-[#2d0a1e]'}`} />

        {/* Mesh gradient blobs */}
        <div className="absolute top-0 right-0 w-[60%] h-[60%] bg-[#1e3a8a]/15 rounded-full blur-[120px]" />
        <div className="absolute bottom-0 left-0 w-[50%] h-[50%] bg-[#d99248]/10 rounded-full blur-[100px]" />
        <div className="absolute top-1/3 left-1/4 w-[30%] h-[30%] bg-purple-900/15 rounded-full blur-[80px]" />

        {/* Dot grid pattern */}
        <div
          className="absolute inset-0 opacity-[0.04]"
          style={{
            backgroundImage:
              "radial-gradient(circle at 1px 1px, white 1px, transparent 0)",
            backgroundSize: "40px 40px",
          }}
        />

        {/* Top fade for header blending */}
        <div className="absolute top-0 left-0 right-0 h-32 bg-gradient-to-b from-black/30 to-transparent" />
      </div>

      <div className="relative z-10 w-full max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-32 lg:py-0">
        <div className="grid lg:grid-cols-12 gap-12 lg:gap-8 items-center">
          {/* Left content - spans 7 cols */}
          <div className="lg:col-span-7">
            {/* Badge */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5 }}
              className="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/[0.08] backdrop-blur-sm border border-white/[0.12] mb-8"
            >
              <span className="flex h-2 w-2 relative">
                <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-accent opacity-75" />
                <span className="relative inline-flex rounded-full h-2 w-2 bg-accent" />
              </span>
              <span className="text-sm font-medium text-white/80">
                1.250+ kırtasiye aktif olarak kullanıyor
              </span>
            </motion.div>

            {/* Title */}
            <motion.h1
              initial={{ opacity: 0, y: 30 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.6, delay: 0.1 }}
              className="text-4xl sm:text-5xl lg:text-6xl xl:text-[3.5rem] font-extrabold text-white leading-[1.08] tracking-tight"
            >
              {renderTitle(content.title, content.highlight_word)}
            </motion.h1>

            {/* Subtitle */}
            <motion.p
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.3 }}
              className="mt-6 text-lg sm:text-xl text-white/60 leading-relaxed max-w-xl"
            >
              {content.subtitle}
            </motion.p>

            {/* Trust badges */}
            <motion.div
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.5, delay: 0.45 }}
              className="mt-8 flex flex-wrap gap-4"
            >
              {[
                { icon: ShieldCheck, text: "Vergi No Doğrulamalı" },
                { icon: BadgeCheck, text: "Komisyonsuz" },
                { icon: Sparkles, text: "Sabit 50 TL Hizmet" },
              ].map((badge) => (
                <div
                  key={badge.text}
                  className="flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white/[0.06] border border-white/[0.08]"
                >
                  <badge.icon className="w-4 h-4 text-[#d99248]" />
                  <span className="text-sm font-medium text-white/70">
                    {badge.text}
                  </span>
                </div>
              ))}
            </motion.div>

            {/* Social proof */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              transition={{ duration: 0.5, delay: 0.6 }}
              className="mt-10 flex items-center gap-4"
            >
              <div className="flex -space-x-2.5">
                {["E", "S", "M", "A", "K"].map((letter, i) => (
                  <div
                    key={i}
                    className="w-9 h-9 rounded-full bg-gradient-to-br from-[#ea580c] to-[#d99248] border-2 border-slate-900 flex items-center justify-center text-white font-bold text-xs"
                    style={{ zIndex: 5 - i }}
                  >
                    {letter}
                  </div>
                ))}
              </div>
              <div>
                <div className="flex items-center gap-1">
                  {[...Array(5)].map((_, i) => (
                    <svg
                      key={i}
                      className="w-3.5 h-3.5 text-amber-400 fill-current"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  ))}
                  <span className="ml-1.5 text-sm font-semibold text-white">
                    {content.social_proof_rating}
                  </span>
                </div>
                <p className="text-xs text-white/50 mt-0.5">
                  {content.social_proof_text}
                </p>
              </div>
            </motion.div>
          </div>

          {/* Right - Verification Card */}
          <motion.div
            initial={{ opacity: 0, y: 40, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            transition={{ duration: 0.7, delay: 0.3 }}
            className="lg:col-span-5"
          >
            <div className="relative">
              {/* Glow behind card */}
              <div className="absolute -inset-4 bg-gradient-to-br from-[#ea580c]/20 to-purple-600/10 rounded-3xl blur-2xl" />

              {/* Card */}
              <div className="relative bg-white/[0.07] backdrop-blur-xl border border-white/[0.12] rounded-2xl p-8 lg:p-10">
                <div className="text-center mb-8">
                  <div className="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-[#ea580c] to-[#d99248] mb-4 shadow-lg shadow-cyan-500/25">
                    <BadgeCheck className="w-7 h-7 text-white" />
                  </div>
                  <h2 className="text-xl font-bold text-white">
                    Hemen Üye Ol
                  </h2>
                  <p className="mt-2 text-sm text-white/50">
                    Vergi numaranız ile anında kayıt
                  </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-4">
                  <div className="relative">
                    <input
                      type="text"
                      value={gln}
                      onChange={(e) => setGln(e.target.value)}
                      placeholder="10 haneli vergi numaranız"
                      maxLength={13}
                      className="w-full px-5 py-4 bg-white/[0.08] border border-white/[0.12] rounded-xl text-white placeholder:text-white/30 font-mono text-base focus:outline-none focus:border-[#ea580c]/60 focus:bg-white/[0.12] focus:ring-2 focus:ring-[#ea580c]/20 transition-all"
                    />
                    {gln.length === 13 && (
                      <motion.div
                        initial={{ scale: 0 }}
                        animate={{ scale: 1 }}
                        className="absolute right-4 top-1/2 -translate-y-1/2"
                      >
                        <ShieldCheck className="w-5 h-5 text-accent" />
                      </motion.div>
                    )}
                  </div>

                  <button
                    type="submit"
                    className="w-full flex items-center justify-center gap-2.5 px-6 py-4 bg-gradient-to-r from-[#ea580c] to-[#e11d72] hover:from-[#c2410c] hover:to-[#ea580c] text-white rounded-xl font-bold text-base shadow-lg shadow-cyan-600/25 transition-all duration-200 hover:shadow-xl hover:shadow-cyan-600/30 active:scale-[0.98]"
                  >
                    {content.cta_primary_text}
                    <ArrowRight className="w-5 h-5" />
                  </button>
                </form>

                <p className="mt-5 text-center text-xs text-white/35 leading-relaxed">
                  Kayıt tamamen ücretsiz. Sadece Vergi numarası doğrulamalı kırtasiyecilera açık kapalı devre platform.
                </p>

                {/* Mini stats */}
                <div className="mt-6 pt-6 border-t border-white/[0.08] grid grid-cols-3 gap-3 text-center">
                  {[
                    { val: "1.250+", lbl: "Kırtasiye" },
                    { val: "5.000+", lbl: "Ürün" },
                    { val: "%0", lbl: "Komisyon" },
                  ].map((s) => (
                    <div key={s.lbl}>
                      <div className="text-lg font-bold text-white">{s.val}</div>
                      <div className="text-[11px] text-white/40">{s.lbl}</div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </motion.div>
        </div>
      </div>

      {/* Scroll indicator */}
      <motion.div
        className="absolute bottom-8 left-1/2 -translate-x-1/2 z-10"
        animate={{ y: [0, 8, 0] }}
        transition={{ duration: 2, repeat: Infinity, ease: "easeInOut" }}
      >
        <a
          href="#nasil-calisir"
          className="flex flex-col items-center gap-2 text-white/30 hover:text-white/60 transition-colors"
        >
          <span className="text-xs font-medium tracking-wider uppercase">
            Keşfet
          </span>
          <ChevronDown className="w-5 h-5" />
        </a>
      </motion.div>
    </section>
  );
}
