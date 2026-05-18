"use client";

import Link from "next/link";
import Image from "next/image";
import { useState, useEffect, useCallback } from "react";
import { Menu, X } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

const NAV_ITEMS = [
  { label: "Nasıl Çalışır?", href: "#nasil-calisir" },
  { label: "Avantajlar", href: "#avantajlar" },
  { label: "Referanslar", href: "#referanslar" },
  { label: "SSS", href: "#sss" },
];

export default function LandingHeader() {
  const [scrolled, setScrolled] = useState(false);
  const [mobileOpen, setMobileOpen] = useState(false);

  const handleScroll = useCallback(() => {
    setScrolled(window.scrollY > 40);
  }, []);

  useEffect(() => {
    window.addEventListener("scroll", handleScroll, { passive: true });
    return () => window.removeEventListener("scroll", handleScroll);
  }, [handleScroll]);

  const closeMobile = useCallback(() => setMobileOpen(false), []);

  return (
    <>
      <header
        className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
          scrolled
            ? "bg-white/95 backdrop-blur-xl shadow-sm border-b border-accent-soft/60"
            : "bg-transparent"
        }`}
      >
        <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16 lg:h-20">
            {/* Logo */}
            <Link href="/" className="flex items-center gap-2.5 group">
              <Image
                src="/logo.webp"
                alt="i-kırtasiye logo"
                width={140}
                height={46}
                className="transition-all duration-300"
                priority
              />
            </Link>

            {/* Desktop nav */}
            <nav className="hidden lg:flex items-center gap-1">
              {NAV_ITEMS.map((item) => (
                <a
                  key={item.href}
                  href={item.href}
                  className={`px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 ${
                    scrolled
                      ? "text-slate-600 hover:text-[#ea580c] hover:bg-accent-soft/60"
                      : "text-white/80 hover:text-white hover:bg-white/10"
                  }`}
                >
                  {item.label}
                </a>
              ))}
            </nav>

            {/* Desktop CTA */}
            <div className="hidden lg:flex items-center gap-3">
              <Link
                href="/login"
                className={`px-5 py-2 rounded-lg text-sm font-semibold transition-all duration-200 ${
                  scrolled
                    ? "text-slate-700 hover:text-[#ea580c] hover:bg-accent-soft/60"
                    : "text-white/90 hover:text-white hover:bg-white/10"
                }`}
              >
                Giriş Yap
              </Link>
              <Link
                href="/register"
                className={`px-5 py-2.5 rounded-xl text-sm font-bold transition-all duration-200 ${
                  scrolled
                    ? "bg-[#1e3a8a] text-white hover:bg-[#1e40af] shadow-md shadow-cyan-200/50"
                    : "bg-white text-[#ea580c] hover:bg-accent-soft shadow-lg shadow-black/10"
                }`}
              >
                Ücretsiz Başla
              </Link>
            </div>

            {/* Mobile burger */}
            <button
              onClick={() => setMobileOpen(!mobileOpen)}
              className={`lg:hidden p-2 rounded-lg transition-all duration-200 ${
                scrolled
                  ? "text-slate-700 hover:bg-slate-100"
                  : "text-white hover:bg-white/10"
              }`}
              aria-label="Menu"
            >
              {mobileOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </header>

      {/* Mobile menu overlay */}
      <AnimatePresence>
        {mobileOpen && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm lg:hidden"
            onClick={closeMobile}
          >
            <motion.div
              initial={{ x: "100%" }}
              animate={{ x: 0 }}
              exit={{ x: "100%" }}
              transition={{ type: "spring", damping: 30, stiffness: 300 }}
              className="absolute right-0 top-0 bottom-0 w-[280px] bg-white shadow-2xl"
              onClick={(e) => e.stopPropagation()}
            >
              <div className="flex items-center justify-between p-5 border-b border-slate-100">
                <Image
                  src="/logo.webp"
                  alt="i-kırtasiye logo"
                  width={120}
                  height={39}
                />
                <button
                  onClick={closeMobile}
                  className="p-2 rounded-lg hover:bg-slate-100 text-slate-500"
                >
                  <X className="w-5 h-5" />
                </button>
              </div>

              <nav className="p-5 space-y-1">
                {NAV_ITEMS.map((item) => (
                  <a
                    key={item.href}
                    href={item.href}
                    onClick={closeMobile}
                    className="block px-4 py-3 rounded-xl text-slate-700 font-medium hover:bg-accent-soft hover:text-[#ea580c] transition-colors"
                  >
                    {item.label}
                  </a>
                ))}
              </nav>

              <div className="p-5 space-y-3 border-t border-slate-100">
                <Link
                  href="/login"
                  onClick={closeMobile}
                  className="block w-full text-center px-5 py-3 rounded-xl border-2 border-slate-200 text-slate-700 font-semibold hover:border-accent-soft hover:text-[#ea580c] transition-colors"
                >
                  Giriş Yap
                </Link>
                <Link
                  href="/register"
                  onClick={closeMobile}
                  className="block w-full text-center px-5 py-3 rounded-xl bg-[#1e3a8a] text-white font-bold hover:bg-[#1e40af] transition-colors"
                >
                  Ücretsiz Başla
                </Link>
              </div>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </>
  );
}
