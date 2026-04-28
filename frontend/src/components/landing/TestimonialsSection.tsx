"use client";

import { useState, useEffect, useCallback, useRef } from "react";
import { motion } from "framer-motion";
import { Users, ChevronLeft, ChevronRight, Quote } from "lucide-react";

interface TestimonialItem {
  quote: string;
  author: string;
  role: string;
  photo?: string;
}

interface TestimonialsSectionProps {
  title: string;
  subtitle: string;
  items: TestimonialItem[];
}

function getInitials(name: string): string {
  const cleaned = name.replace(/\./g, "").trim().split(/\s+/);
  if (cleaned.length >= 2) return (cleaned[0][0] + cleaned[1][0]).toUpperCase();
  return name.substring(0, 2).toUpperCase();
}

const AVATAR_GRADIENTS = [
  "from-[#b8651a] to-[#d99248]",
  "from-amber-500 to-orange-500",
  "from-cyan-500 to-teal-500",
  "from-blue-500 to-indigo-500",
  "from-violet-500 to-purple-500",
];

export default function TestimonialsSection({
  title,
  subtitle,
  items,
}: TestimonialsSectionProps) {
  const [activeIndex, setActiveIndex] = useState(0);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

  const startAutoPlay = useCallback(() => {
    if (intervalRef.current) clearInterval(intervalRef.current);
    intervalRef.current = setInterval(() => {
      setActiveIndex((prev) => (prev + 1) % items.length);
    }, 5000);
  }, [items.length]);

  useEffect(() => {
    if (items.length <= 1) return;
    startAutoPlay();
    return () => {
      if (intervalRef.current) clearInterval(intervalRef.current);
    };
  }, [items.length, startAutoPlay]);

  const goTo = useCallback(
    (index: number) => {
      setActiveIndex(index);
      startAutoPlay();
    },
    [startAutoPlay]
  );

  const prev = useCallback(() => {
    goTo((activeIndex - 1 + items.length) % items.length);
  }, [activeIndex, items.length, goTo]);

  const next = useCallback(() => {
    goTo((activeIndex + 1) % items.length);
  }, [activeIndex, items.length, goTo]);

  if (items.length === 0) return null;

  return (
    <section
      id="referanslar"
      className="py-24 lg:py-32 bg-gradient-to-b from-[#fbeede] via-[#fbeede]/30 to-white relative overflow-hidden"
    >
      {/* Background decorations */}
      <div className="absolute top-20 left-10 w-72 h-72 bg-accent-soft/20 rounded-full blur-3xl" />
      <div className="absolute bottom-20 right-10 w-64 h-64 bg-purple-200/15 rounded-full blur-3xl" />

      <div className="relative max-w-7xl mx-auto px-5 sm:px-6 lg:px-8">
        {/* Header */}
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          whileInView={{ opacity: 1, y: 0 }}
          viewport={{ once: true, margin: "-60px" }}
          transition={{ duration: 0.5 }}
          className="text-center mb-16"
        >
          <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white text-[#b8651a] text-sm font-semibold mb-5 shadow-sm">
            <Users className="w-3.5 h-3.5" />
            Referanslar
          </div>
          <h2 className="text-3xl sm:text-4xl lg:text-[2.75rem] font-extrabold text-slate-900 leading-tight">
            {title}
          </h2>
          <div className="mx-auto mt-4 w-16 h-1 rounded-full bg-gradient-to-r from-[#b8651a] to-[#d99248]" />
          <p className="mt-5 text-lg text-slate-500 max-w-2xl mx-auto leading-relaxed">
            {subtitle}
          </p>
        </motion.div>

        {/* Desktop: Grid */}
        <div className="hidden md:grid md:grid-cols-3 gap-6">
          {items.map((item, i) => {
            const gradient = AVATAR_GRADIENTS[i % AVATAR_GRADIENTS.length];
            return (
              <motion.div
                key={i}
                initial={{ opacity: 0, y: 30 }}
                whileInView={{ opacity: 1, y: 0 }}
                viewport={{ once: true, margin: "-40px" }}
                transition={{ duration: 0.4, delay: i * 0.1 }}
                className="relative bg-white rounded-2xl p-7 shadow-sm border border-accent-soft/50 hover:shadow-lg hover:shadow-cyan-100/40 transition-all duration-300 group"
              >
                {/* Quote icon */}
                <div className="absolute top-6 right-6 opacity-[0.06] group-hover:opacity-[0.1] transition-opacity">
                  <Quote className="w-12 h-12 text-[#b8651a]" />
                </div>

                {/* Stars */}
                <div className="flex items-center gap-0.5 mb-5">
                  {[...Array(5)].map((_, j) => (
                    <svg
                      key={j}
                      className="w-4 h-4 text-amber-400 fill-current"
                      viewBox="0 0 20 20"
                    >
                      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                  ))}
                </div>

                {/* Quote */}
                <p className="text-slate-600 leading-relaxed mb-6 text-[15px]">
                  &ldquo;{item.quote}&rdquo;
                </p>

                {/* Author */}
                <div className="flex items-center gap-3 pt-5 border-t border-slate-100">
                  {item.photo ? (
                    <img src={item.photo} alt={item.author} className="w-10 h-10 rounded-full object-cover shadow-md" />
                  ) : (
                    <div
                      className={`w-10 h-10 rounded-full bg-gradient-to-br ${gradient} flex items-center justify-center text-white font-bold text-sm shadow-md`}
                    >
                      {getInitials(item.author)}
                    </div>
                  )}
                  <div>
                    <div className="font-semibold text-slate-900 text-sm">
                      {item.author}
                    </div>
                    <div className="text-xs text-slate-500">{item.role}</div>
                  </div>
                </div>
              </motion.div>
            );
          })}
        </div>

        {/* Mobile: Carousel */}
        <div className="md:hidden">
          <div className="relative overflow-hidden">
            <motion.div
              key={activeIndex}
              initial={{ opacity: 0, x: 40 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -40 }}
              transition={{ duration: 0.3 }}
              className="bg-white rounded-2xl p-7 shadow-sm border border-accent-soft/50"
            >
              <div className="flex items-center gap-0.5 mb-5">
                {[...Array(5)].map((_, j) => (
                  <svg
                    key={j}
                    className="w-4 h-4 text-amber-400 fill-current"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                ))}
              </div>

              <p className="text-slate-600 leading-relaxed mb-6">
                &ldquo;{items[activeIndex].quote}&rdquo;
              </p>

              <div className="flex items-center gap-3 pt-5 border-t border-slate-100">
                {items[activeIndex].photo ? (
                  <img src={items[activeIndex].photo} alt={items[activeIndex].author} className="w-10 h-10 rounded-full object-cover shadow-md" />
                ) : (
                  <div
                    className={`w-10 h-10 rounded-full bg-gradient-to-br ${
                      AVATAR_GRADIENTS[activeIndex % AVATAR_GRADIENTS.length]
                    } flex items-center justify-center text-white font-bold text-sm`}
                  >
                    {getInitials(items[activeIndex].author)}
                  </div>
                )}
                <div>
                  <div className="font-semibold text-slate-900 text-sm">
                    {items[activeIndex].author}
                  </div>
                  <div className="text-xs text-slate-500">
                    {items[activeIndex].role}
                  </div>
                </div>
              </div>
            </motion.div>
          </div>

          {/* Controls */}
          <div className="flex items-center justify-center gap-4 mt-6">
            <button
              onClick={prev}
              className="p-2 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-[#b8651a] hover:border-accent-soft transition-colors shadow-sm"
              aria-label="Onceki"
            >
              <ChevronLeft className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-2">
              {items.map((_, i) => (
                <button
                  key={i}
                  onClick={() => goTo(i)}
                  className={`transition-all duration-300 rounded-full ${
                    i === activeIndex
                      ? "w-8 h-2.5 bg-[#b8651a]"
                      : "w-2.5 h-2.5 bg-slate-300 hover:bg-slate-400"
                  }`}
                  aria-label={`Yorum ${i + 1}`}
                />
              ))}
            </div>

            <button
              onClick={next}
              className="p-2 rounded-full bg-white border border-slate-200 text-slate-500 hover:text-[#b8651a] hover:border-accent-soft transition-colors shadow-sm"
              aria-label="Sonraki"
            >
              <ChevronRight className="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>
    </section>
  );
}
