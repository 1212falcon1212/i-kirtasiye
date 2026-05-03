"use client";

import { motion } from "framer-motion";
import {
  BadgeCheck,
  CheckCircle2,
  Globe,
  ArrowRight,
} from "lucide-react";
import Link from "next/link";

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

interface FeaturesSectionProps {
  content: HowItWorksContent;
}

const STEP_COLORS = [
  { bg: "bg-[#1e3a8a]", ring: "ring-accent/200", text: "text-white" },
  { bg: "bg-amber-500", ring: "ring-amber-200", text: "text-white" },
  { bg: "bg-accent-soft0", ring: "ring-accent/200", text: "text-white" },
];

export default function FeaturesSection({ content }: FeaturesSectionProps) {
  return (
    <section id="nasil-calisir" className="relative overflow-hidden">
      {/* Trusted by strip */}
      <div className="bg-white border-y border-slate-100 py-10">
        <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
          <p className="text-center text-sm text-slate-400 font-medium mb-6 tracking-wide uppercase">
            {content.trusted_by_text}
          </p>
          <div className="flex flex-wrap justify-center items-center gap-x-10 gap-y-4">
            {content.trusted_by_cities.map((city) => (
              <div
                key={city}
                className="flex items-center gap-2 text-slate-400 hover:text-slate-600 transition-colors"
              >
                <Globe className="w-4 h-4" />
                <span className="font-semibold text-sm">{city}</span>
              </div>
            ))}
          </div>
        </div>
      </div>

      {/* How it works */}
      <div className="py-24 lg:py-32 bg-[#fafaf7]">
        <div className="max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
          {/* Header */}
          <motion.div
            initial={{ opacity: 0, y: 20 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: "-60px" }}
            transition={{ duration: 0.5 }}
            className="text-center mb-16 lg:mb-20"
          >
            <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-accent-soft text-[#ea580c] text-sm font-semibold mb-5">
              <BadgeCheck className="w-3.5 h-3.5" />
              Nasıl Çalışır?
            </div>
            <h2 className="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold text-slate-900 leading-tight">
              {content.section_title}
            </h2>
            <div className="mx-auto mt-4 w-16 h-1 rounded-full bg-gradient-to-r from-[#ea580c] to-[#d99248]" />
            <p className="mt-5 text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed">
              {content.section_subtitle}
            </p>
          </motion.div>

          <div className="grid lg:grid-cols-12 gap-12 lg:gap-16 items-start">
            {/* Steps */}
            <div className="lg:col-span-7">
              <div className="space-y-0">
                {content.steps.map((step, i) => {
                  const colors = STEP_COLORS[i % STEP_COLORS.length];
                  const isLast = i === content.steps.length - 1;

                  return (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, x: -20 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true, margin: "-40px" }}
                      transition={{ duration: 0.4, delay: i * 0.1 }}
                      className="relative flex gap-6"
                    >
                      {/* Timeline */}
                      <div className="flex flex-col items-center">
                        <div
                          className={`relative w-12 h-12 rounded-2xl ${colors.bg} ${colors.text} ring-4 ${colors.ring} flex items-center justify-center font-bold text-lg shadow-lg flex-shrink-0`}
                        >
                          {i + 1}
                        </div>
                        {!isLast && (
                          <div className="w-px flex-1 min-h-[48px] bg-gradient-to-b from-slate-200 to-transparent mt-3" />
                        )}
                      </div>

                      {/* Content */}
                      <div className="pb-12 pt-1">
                        <h3 className="text-xl font-bold text-slate-900 mb-2">
                          {step.title}
                        </h3>
                        <p className="text-slate-500 leading-relaxed text-[15px]">
                          {step.description}
                        </p>
                      </div>
                    </motion.div>
                  );
                })}
              </div>

              {/* CTA after steps */}
              <motion.div
                initial={{ opacity: 0, y: 20 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true }}
                transition={{ duration: 0.4, delay: 0.3 }}
                className="ml-[72px]"
              >
                <Link
                  href="/register"
                  className="inline-flex items-center gap-2 px-7 py-3.5 bg-[#1e3a8a] hover:bg-[#1e40af] text-white rounded-xl font-bold text-sm shadow-lg shadow-cyan-200/50 transition-all duration-200 hover:shadow-xl"
                >
                  Hemen Başla
                  <ArrowRight className="w-4 h-4" />
                </Link>
              </motion.div>
            </div>

            {/* Verification Card */}
            <motion.div
              initial={{ opacity: 0, y: 30 }}
              whileInView={{ opacity: 1, y: 0 }}
              viewport={{ once: true, margin: "-40px" }}
              transition={{ duration: 0.6, delay: 0.2 }}
              className="lg:col-span-5 lg:sticky lg:top-28"
            >
              <div className="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                {/* Card header */}
                <div className="p-6 bg-gradient-to-br from-slate-900 to-slate-800">
                  <div className="flex items-center gap-4">
                    <div className="w-12 h-12 rounded-xl bg-gradient-to-br from-[#ea580c] to-[#d99248] flex items-center justify-center shadow-lg">
                      <BadgeCheck className="w-6 h-6 text-white" />
                    </div>
                    <div>
                      <h3 className="text-lg font-bold text-white">
                        {content.vergi_no_card_title}
                      </h3>
                      <p className="text-sm text-slate-400">
                        {content.vergi_no_card_subtitle}
                      </p>
                    </div>
                  </div>
                </div>

                <div className="p-6 space-y-4">
                  {/* Checklist */}
                  {content.vergi_no_checklist.map((item, i) => (
                    <motion.div
                      key={i}
                      initial={{ opacity: 0, x: -10 }}
                      whileInView={{ opacity: 1, x: 0 }}
                      viewport={{ once: true }}
                      transition={{ delay: 0.4 + i * 0.1 }}
                      className="flex items-center gap-3 p-3.5 rounded-xl bg-accent-soft/60 border border-accent-soft/60"
                    >
                      <CheckCircle2 className="w-5 h-5 text-accent-soft0 flex-shrink-0" />
                      <span className="text-sm font-medium text-accent-hover">
                        {item}
                      </span>
                    </motion.div>
                  ))}

                  {/* Demo input */}
                  <div className="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <div className="text-xs font-medium text-slate-400 mb-2.5 uppercase tracking-wider">
                      Örnek Vergi No Doğrulama
                    </div>
                    <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                      <input
                        type="text"
                        value="8699876543210"
                        readOnly
                        className="w-full sm:flex-1 min-w-0 px-4 py-2.5 bg-white rounded-lg border border-slate-200 font-mono text-sm text-slate-700"
                      />
                      <div className="px-4 py-2.5 bg-accent-soft0 text-white rounded-lg font-bold flex items-center justify-center gap-2 text-sm shadow-md whitespace-nowrap">
                        <CheckCircle2 className="w-4 h-4" />
                        Doğrulandı
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </motion.div>
          </div>
        </div>
      </div>
    </section>
  );
}
