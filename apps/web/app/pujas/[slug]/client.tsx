'use client'

import { useState } from "react";
import Link from "next/link";
import type { SEOPage } from "@/lib/content/types";
import type { PujaPageViewModel } from "@/lib/content/puja";
import {
  Award,
  BookOpen,
  CalendarDays,
  Check,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Clock,
  MapPin,
  PhoneCall,
  ShieldCheck,
  Star,
  User,
} from "lucide-react";

type Props = {
  page: SEOPage;
  puja: PujaPageViewModel;
};

export default function PujaDetailsClient({ page, puja }: Props) {
  const [currentImage, setCurrentImage] = useState(0);
  const [expandedFaq, setExpandedFaq] = useState<number | null>(null);
  const [selectedPackage, setSelectedPackage] = useState(0);

  const nextImage = () => setCurrentImage((prev) => (prev + 1) % puja.galleryImages.length);
  const prevImage = () => setCurrentImage((prev) => (prev - 1 + puja.galleryImages.length) % puja.galleryImages.length);

  return (
    <div className="min-h-screen bg-background">
      <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          <div className="space-y-7 lg:col-span-2">
            <nav className="text-xs">
              <Link href="/" className="text-muted-foreground transition-colors hover:text-foreground">Home</Link>
              <span className="mx-1.5 text-muted-foreground">/</span>
              <Link href="/pujas" className="text-muted-foreground transition-colors hover:text-foreground">Puja Services</Link>
              <span className="mx-1.5 text-muted-foreground">/</span>
              <span className="font-medium text-foreground">{puja.title}</span>
            </nav>

            <div className="border-b border-border pb-4">
              <h1 className="mb-2 text-xl font-bold text-foreground font-serif">{puja.title}</h1>
              <p className="max-w-3xl text-sm leading-6 text-muted-foreground">{puja.description}</p>
              <div className="mt-3 flex flex-wrap items-center gap-2.5 text-xs">
                <div className="flex items-center gap-1">
                  <MapPin size={12} className="text-primary" />
                  <span className="whitespace-nowrap">{puja.serviceAreas.join(", ")}</span>
                </div>
                <div className="flex items-center gap-1">
                  <ShieldCheck size={12} className="text-primary" />
                  <span className="whitespace-nowrap">Verified Pandits</span>
                </div>
                <div className="flex items-center gap-1">
                  <Award size={12} className="text-primary" />
                  <span className="whitespace-nowrap">Vedic Ceremony Support</span>
                </div>
              </div>
            </div>

            <div className="relative">
              <div className="aspect-video overflow-hidden rounded-md bg-muted">
                <img
                  src={puja.galleryImages[currentImage]}
                  alt={`${puja.title} by Navdhya`}
                  className="h-full w-full object-cover"
                />
              </div>
              <button
                onClick={prevImage}
                className="absolute left-2 top-1/2 -translate-y-1/2 rounded-full border border-border bg-background/90 p-1.5 shadow transition-all hover:bg-background"
              >
                <ChevronLeft size={16} className="text-foreground" />
              </button>
              <button
                onClick={nextImage}
                className="absolute right-2 top-1/2 -translate-y-1/2 rounded-full border border-border bg-background/90 p-1.5 shadow transition-all hover:bg-background"
              >
                <ChevronRight size={16} className="text-foreground" />
              </button>

              <div className="mt-2.5 flex justify-center gap-1">
                {puja.galleryImages.map((img, index) => (
                  <button
                    key={img}
                    onClick={() => setCurrentImage(index)}
                    className={`h-12 w-12 overflow-hidden rounded-sm border transition-all ${
                      currentImage === index ? "border-primary" : "border-transparent hover:border-border"
                    }`}
                  >
                    <img src={img} alt={`Gallery thumbnail ${index + 1}`} className="h-full w-full object-cover" />
                  </button>
                ))}
              </div>
            </div>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">About This Puja</h2>
              <p className="text-sm leading-7 text-muted-foreground">{puja.summary}</p>
              {puja.serviceProof.length > 0 && (
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  {puja.serviceProof.map((point) => (
                    <div key={point} className="rounded-sm bg-muted/30 p-2.5 text-xs text-foreground">
                      {point}
                    </div>
                  ))}
                </div>
              )}
            </section>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">Puja Packages</h2>
              <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
                {puja.packages.map((pkg, index) => (
                  <button
                    key={`${pkg.name}-${pkg.price}`}
                    type="button"
                    onClick={() => setSelectedPackage(index)}
                    className={`rounded border p-3 text-left transition-all ${
                      selectedPackage === index
                        ? "border-primary bg-primary/5"
                        : "border-border hover:border-primary/50"
                    }`}
                  >
                    <div className="flex items-start gap-2.5">
                      <div className="flex h-9 w-9 items-center justify-center rounded-sm bg-[#0E3F2E] text-white">
                        <BookOpen size={16} />
                      </div>
                      <div className="flex-1">
                        <h3 className="mb-1 text-sm font-bold text-foreground">{pkg.name}</h3>
                        <p className="mb-2 text-xs leading-5 text-muted-foreground">{pkg.description}</p>
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Clock size={12} />
                            <span>{pkg.duration ?? "60 to 120 minutes"}</span>
                          </div>
                          <div className="text-sm font-bold text-primary">{pkg.price}</div>
                        </div>
                      </div>
                    </div>
                  </button>
                ))}
              </div>
            </section>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">What&apos;s Included</h2>
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                {puja.includes.map((item) => (
                  <div key={item} className="flex items-start gap-2 rounded-sm bg-muted/30 p-2.5 text-xs">
                    <Check size={14} className="mt-0.5 flex-shrink-0 text-primary" />
                    <span className="leading-5 text-foreground">{item}</span>
                  </div>
                ))}
              </div>
            </section>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">Ceremony Process</h2>
              <div className="space-y-2.5">
                {puja.process?.map((step, index) => (
                  <div key={`${step.step}-${index}`} className="flex gap-3 rounded border border-border p-3 text-sm">
                    <div className="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-[#0E3F2E] text-xs font-bold text-white">
                      {index + 1}
                    </div>
                    <div className="min-w-0">
                      <h3 className="mb-1 font-bold text-foreground">{step.step}</h3>
                      <p className="text-xs leading-5 text-muted-foreground">{step.description}</p>
                    </div>
                  </div>
                ))}
              </div>
            </section>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">Why Families Book This Ceremony</h2>
              <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                {puja.benefits.map((benefit) => (
                  <div key={benefit} className="flex items-start gap-2 rounded-sm bg-muted/30 p-2.5 text-xs">
                    <Check size={14} className="mt-0.5 flex-shrink-0 text-primary" />
                    <span className="leading-5 text-foreground">{benefit}</span>
                  </div>
                ))}
              </div>
            </section>

            <section className="space-y-3.5">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-bold text-foreground font-serif">Planning Guide</h2>
                <span className="text-xs text-muted-foreground">Booking lead time: {puja.bookingLeadTime}</span>
              </div>
              <article
                className="prose prose-sm max-w-none text-foreground prose-headings:font-serif prose-a:text-primary"
                dangerouslySetInnerHTML={{ __html: page.content }}
              />
            </section>

            <section className="space-y-3.5">
              <h2 className="text-lg font-bold text-foreground font-serif">Frequently Asked Questions</h2>
              <div className="space-y-2">
                {puja.faqs.map((faq, index) => (
                  <div key={faq.question} className="overflow-hidden rounded border border-border">
                    <button
                      type="button"
                      className="flex w-full items-center justify-between bg-muted/30 p-3 text-left transition-colors hover:bg-muted/50"
                      onClick={() => setExpandedFaq(expandedFaq === index ? null : index)}
                    >
                      <span className="pr-3 text-sm font-medium leading-6 text-foreground">{faq.question}</span>
                      <ChevronDown
                        size={16}
                        className={`ml-2 flex-shrink-0 text-muted-foreground transition-transform ${
                          expandedFaq === index ? "rotate-180" : ""
                        }`}
                      />
                    </button>
                    {expandedFaq === index && (
                      <div className="p-3 pt-0">
                        <p className="text-xs leading-6 text-muted-foreground">{faq.answer}</p>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </section>
          </div>

          <div className="space-y-5 lg:h-fit lg:self-start lg:pt-36">
            <div className="rounded border border-border bg-card p-3">
              <div className="mb-2.5 flex items-center gap-1.5">
                <Star size={12} className="fill-current text-yellow-500" />
                <span className="text-sm font-bold">4.9 Excellent</span>
                <span className="ml-auto text-xs text-muted-foreground">Delhi NCR support</span>
              </div>

              <div className="mb-3 flex items-center gap-1.5">
                <Check size={12} className="text-primary" />
                <span className="text-xs text-muted-foreground">Verified pandits and practical booking guidance</span>
              </div>

              <div className="mb-3">
                <div className="mb-1 text-xs text-muted-foreground">Starts from</div>
                <div className="text-lg font-bold text-primary">{puja.packages[selectedPackage]?.price ?? puja.priceRange}</div>
              </div>

              <button className="mb-4 flex w-full items-center justify-center gap-1.5 rounded bg-[#0E3F2E] py-2.5 text-sm font-bold text-white transition hover:bg-[#0E3F2E]/90">
                <BookOpen size={14} />
                Book This Puja
              </button>

              <div className="border-t border-border pt-3">
                <h3 className="mb-2.5 text-base font-bold font-serif">Your Booking Snapshot</h3>
                <div className="space-y-2">
                  <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <CalendarDays size={12} className="flex-shrink-0" />
                    <span className="w-20">Lead time</span>
                    <span className="ml-auto text-right">{puja.bookingLeadTime}</span>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <MapPin size={12} className="flex-shrink-0" />
                    <span className="w-20">Coverage</span>
                    <span className="ml-auto text-right">{puja.serviceAreas.join(", ")}</span>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <ShieldCheck size={12} className="flex-shrink-0" />
                    <span className="w-20">Trust</span>
                    <span className="ml-auto text-right">Verified pandit support</span>
                  </div>
                  <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <Star size={12} className="flex-shrink-0" />
                    <span className="w-20">Fit</span>
                    <span className="ml-auto text-right">Delhi NCR family bookings</span>
                  </div>
                </div>

                <div className="mt-3 grid grid-cols-2 gap-1.5">
                  <button className="flex items-center justify-center gap-1 rounded border border-primary py-1.5 text-xs font-medium text-primary transition hover:bg-primary/10">
                    <PhoneCall size={12} />
                    Call
                  </button>
                  <button className="flex items-center justify-center gap-1 rounded bg-primary py-1.5 text-xs font-medium text-primary-foreground transition hover:bg-primary/90">
                    <User size={12} />
                    Message
                  </button>
                </div>
              </div>
            </div>

            <div className="rounded border border-border bg-card p-3">
              <h3 className="mb-2.5 text-base font-bold font-serif">Service Highlights</h3>
              <div className="space-y-2">
                {puja.highlights.map((highlight) => (
                  <div key={highlight} className="flex items-center gap-2 rounded-sm bg-muted/30 p-2 text-xs">
                    <Check size={12} className="flex-shrink-0 text-primary" />
                    <span className="leading-5 text-foreground">{highlight}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
