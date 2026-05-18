"use client";

import Link from "next/link";
import Image from "next/image";

export default function LandingFooter() {
  return (
    <footer className="bg-slate-950 text-slate-400">
      <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8 py-16">
        <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-10">
          {/* Brand */}
          <div className="lg:col-span-1">
            <Link href="/" className="flex items-center gap-2.5 mb-5">
              <Image
                src="/logo.webp"
                alt="i-kırtasiye logo"
                width={140}
                height={46}
              />
            </Link>
            <p className="text-sm leading-relaxed text-slate-500">
              Eczacılar arası güvenli B2B ticaret platformu. Vergi numarası doğrulaması ile
              kapalı devre sistem.
            </p>
          </div>

          {/* Platform */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm">Platform</h4>
            <ul className="space-y-3 text-sm">
              <li>
                <a
                  href="#nasil-calisir"
                  className="hover:text-accent transition-colors"
                >
                  Nasıl Çalışır?
                </a>
              </li>
              <li>
                <a
                  href="#avantajlar"
                  className="hover:text-accent transition-colors"
                >
                  Avantajlar
                </a>
              </li>
              <li>
                <Link
                  href="/register"
                  className="hover:text-accent transition-colors"
                >
                  Kayıt Ol
                </Link>
              </li>
              <li>
                <Link
                  href="/login"
                  className="hover:text-accent transition-colors"
                >
                  Giriş Yap
                </Link>
              </li>
            </ul>
          </div>

          {/* Yardim */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm">Yardım</h4>
            <ul className="space-y-3 text-sm">
              <li>
                <Link
                  href="/yardim"
                  className="hover:text-accent transition-colors"
                >
                  Yardım Merkezi
                </Link>
              </li>
              <li>
                <Link
                  href="/yardim/satici-rehberi/urun-ekleme"
                  className="hover:text-accent transition-colors"
                >
                  Satıcı Rehberi
                </Link>
              </li>
              <li>
                <Link
                  href="/yardim/alici-rehberi/siparis-takibi"
                  className="hover:text-accent transition-colors"
                >
                  Alıcı Rehberi
                </Link>
              </li>
              <li>
                <Link
                  href="/iletisim"
                  className="hover:text-accent transition-colors"
                >
                  İletişim
                </Link>
              </li>
            </ul>
          </div>

          {/* Yasal */}
          <div>
            <h4 className="font-semibold text-white mb-4 text-sm">Yasal</h4>
            <ul className="space-y-3 text-sm">
              <li>
                <Link
                  href="/legal/terms"
                  className="hover:text-accent transition-colors"
                >
                  Kullanım Koşulları
                </Link>
              </li>
              <li>
                <Link
                  href="/legal/privacy"
                  className="hover:text-accent transition-colors"
                >
                  Gizlilik Politikası
                </Link>
              </li>
              <li>
                <Link
                  href="/legal/kvkk"
                  className="hover:text-accent transition-colors"
                >
                  KVKK Aydınlatma
                </Link>
              </li>
            </ul>
          </div>
        </div>

        <div className="border-t border-slate-800/60 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
          <p className="text-sm text-slate-500">
            &copy; {new Date().getFullYear()} i-Depo. Tüm hakları saklıdır.
          </p>
          <div className="flex items-center gap-3">
            <span className="text-xs text-slate-600">Güvenli Ödeme:</span>
            <div className="flex items-center gap-1.5">
              {["VISA", "MC", "EFT"].map((method) => (
                <div
                  key={method}
                  className="px-2.5 py-1 bg-slate-800/60 rounded text-[10px] font-bold text-slate-400"
                >
                  {method}
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </footer>
  );
}
