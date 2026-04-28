import type { Metadata } from "next";
import LandingClient from "./LandingClient";

export const metadata: Metadata = {
  title: "i-kirtasiye | Türkiye'nin İlk Komisyonsuz B2B Kırtasiye Pazaryeri",
  description:
    "Sabit aylık üyelik, yüzdesel komisyon yok. Vergi numarası doğrulamalı güvenli B2B kırtasiye ticareti. Onaylı tedarikçiler ve binlerce kırtasiyeci aktif olarak kullanıyor.",
  keywords: [
    "kırtasiye",
    "B2B",
    "pazaryeri",
    "kırtasiye pazaryeri",
    "toptan kırtasiye",
    "ofis malzemeleri",
    "okul malzemeleri",
    "komisyonsuz",
    "vergi numarası",
  ],
  openGraph: {
    title: "i-kirtasiye | Komisyonsuz B2B Kırtasiye Pazaryeri",
    description:
      "Türkiye'nin ilk komisyonsuz B2B kırtasiye pazaryeri. Vergi numarası doğrulaması ile güvenli toptan tedarik.",
    type: "website",
    siteName: "i-kirtasiye",
  },
  twitter: {
    card: "summary_large_image",
    title: "i-kirtasiye | Komisyonsuz B2B Kırtasiye Pazaryeri",
    description:
      "Türkiye'nin ilk komisyonsuz B2B kırtasiye pazaryeri.",
  },
};

export default function HomePage() {
  return <LandingClient />;
}
