import Image from "next/image";
import Link from "next/link";
import {
  ArrowRight,
  AudioWaveform,
  BadgeCheck,
  Bell,
  CalendarDays,
  Clock3,
  HeartHandshake,
  Landmark,
  Mic,
  MoonStar,
  Search,
  ShieldCheck,
  Sparkles,
  Star,
  Users,
  Video,
} from "lucide-react";

const categoryCards = [
  {
    title: "Temple Directory",
    description: "Verified temples with darshan slots, live queue estimates, and donation access.",
    icon: Landmark,
  },
  {
    title: "Book a Priest",
    description: "Match rituals with screened pandits by language, sampradaya, and response speed.",
    icon: BadgeCheck,
  },
  {
    title: "Astrology",
    description: "Get muhurta, kundli, and remedy guidance through chat, audio, or video.",
    icon: MoonStar,
  },
  {
    title: "Online Puja",
    description: "Coordinate remote ceremonies for families in different cities and time zones.",
    icon: Video,
  },
  {
    title: "Donations",
    description: "Support temple causes with transparent funds, receipts, and recurring giving.",
    icon: HeartHandshake,
  },
  {
    title: "Festivals",
    description: "Discover timely rituals, local events, and limited festival booking windows.",
    icon: Sparkles,
  },
  {
    title: "Live Darshan",
    description: "Join live temple streams and track special aarti or abhishek sessions.",
    icon: AudioWaveform,
  },
  {
    title: "Community",
    description: "Share family milestones, ask ritual questions, and follow temple updates.",
    icon: Users,
  },
];

const spotlightCards = [
  {
    title: "Discover verified temples",
    description: "Explore trusted mandirs with darshan timing, trust score, and ritual calendar.",
    image: "/navdhya-home/temple-directory-1.jpg",
    badge: "Temple Discovery",
  },
  {
    title: "Schedule rituals with confidence",
    description: "Book priests and ceremony support with preparation checklists built in.",
    image: "/navdhya-home/temple-ritual-1.jpg",
    badge: "Ritual Booking",
  },
];

const marketplaceCards = [
  {
    title: "Shri Durga Mandir",
    subtitle: "Live queue 12 min",
    meta: "Open until 9:30 PM",
    image: "/navdhya-home/temple-directory-2.jpg",
    highlight: "Donation enabled",
  },
  {
    title: "Pandit Aditya Sharma",
    subtitle: "14 years experience",
    meta: "Hindi, Sanskrit, English",
    image: "/navdhya-home/temple-ritual-2.jpg",
    highlight: "94% response rate",
  },
  {
    title: "Astrologer Meera Joshi",
    subtitle: "Kundli and muhurta specialist",
    meta: "Audio, video, chat",
    image: "/navdhya-home/temple-ritual-1.jpg",
    highlight: "Next slot in 25 min",
  },
];

const bookingSteps = [
  "Choose service",
  "Configure ritual",
  "Select expert",
  "Pick schedule",
  "Confirm location",
  "Review samagri",
  "Pay securely",
  "Track ceremony",
];

const trustLayers = [
  "Identity verified",
  "Temple association validated",
  "Skill and mantra screening",
  "Community review weighted trust score",
];

const roleCards = [
  {
    title: "Devotees",
    points: "Quick ritual booking, transparent pricing, repeat spiritual routines, family-safe coordination.",
  },
  {
    title: "Priests",
    points: "Availability calendar, trust dashboard, package clarity, verified lead pipeline.",
  },
  {
    title: "Astrologers",
    points: "Session modes, consultation queue, profile credibility, digital follow-up tools.",
  },
  {
    title: "Temple Admins",
    points: "Event operations, donations analytics, priest roster, darshan capacity control.",
  },
];

export function NavdhyaMarketplaceHome() {
  return (
    <main className="w-full overflow-x-hidden bg-[#f5efe4] text-[#1f1c18]">
      <section className="relative isolate overflow-hidden border-b border-[#d9cbb5] bg-[linear-gradient(135deg,#f7f1e7_0%,#f4ead8_55%,#ead9b4_100%)]">
        <div className="absolute inset-x-0 top-0 h-[26rem] bg-[radial-gradient(circle_at_top_left,rgba(187,94,35,0.18),transparent_42%),radial-gradient(circle_at_top_right,rgba(49,71,113,0.18),transparent_38%)]" />
        <div className="mx-auto grid max-w-7xl gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1.2fr_0.95fr] lg:px-8 lg:py-16">
          <div className="relative z-10 space-y-8">
            <div className="inline-flex items-center gap-2 rounded-full border border-[#d7c7ad] bg-white/80 px-4 py-2 text-sm text-[#6f5c43] shadow-[0_10px_30px_rgba(140,100,37,0.08)] backdrop-blur">
              <ShieldCheck className="h-4 w-4 text-[#0f4c5c]" />
              Trust-first spiritual operating system
            </div>

            <div className="space-y-4">
              <p className="font-sacred-display text-4xl leading-none tracking-[-0.04em] text-[#1f1c18] sm:text-5xl lg:text-7xl">
                Sacred access,
                <br />
                designed for real life.
              </p>
              <p className="max-w-2xl text-base leading-7 text-[#5d5347] sm:text-lg">
                Navdhya is evolving from a service site into a trusted marketplace for temples, priests, astrologers,
                rituals, donations, and long-term spiritual journeys. The structure is inspired by lightweight mobile
                discovery, but rebuilt for a premium sacred-tech experience.
              </p>
            </div>

            <div className="grid gap-3 rounded-[2rem] border border-[#d8c8b1] bg-white/85 p-4 shadow-[0_25px_70px_rgba(92,63,21,0.12)] backdrop-blur sm:grid-cols-[1fr_auto] sm:items-center">
              <div className="flex items-center gap-3 rounded-[1.4rem] border border-[#ece1d1] bg-[#f9f6f0] px-4 py-3">
                <Search className="h-5 w-5 text-[#8f6b2d]" />
                <input
                  aria-label="Search spiritual services"
                  className="w-full bg-transparent text-sm text-[#332c25] outline-none placeholder:text-[#8d8376]"
                  placeholder="Search temples, priests, pujas, astrologers, or festivals"
                  type="search"
                />
              </div>
              <div className="flex gap-3">
                <Link
                  href="/contact"
                  className="inline-flex items-center justify-center rounded-full bg-[#b5652a] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#9d5524]"
                >
                  Quick Book
                </Link>
                <Link
                  href="/services"
                  className="inline-flex items-center justify-center rounded-full border border-[#d6c5ac] bg-white px-5 py-3 text-sm font-semibold text-[#3b3027] transition hover:border-[#b99763]"
                >
                  Explore Services
                </Link>
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-3">
              <div className="rounded-[1.6rem] border border-[#dac9b1] bg-white/75 p-5 shadow-[0_18px_40px_rgba(92,63,21,0.08)]">
                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[#8f6b2d]">Trust Layer</p>
                <p className="mt-3 text-3xl font-semibold text-[#1f1c18]">4-step</p>
                <p className="mt-2 text-sm leading-6 text-[#60554a]">Identity, temple, skill, and community-backed validation.</p>
              </div>
              <div className="rounded-[1.6rem] border border-[#dac9b1] bg-white/75 p-5 shadow-[0_18px_40px_rgba(92,63,21,0.08)]">
                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[#8f6b2d]">Speed Goal</p>
                <p className="mt-3 text-3xl font-semibold text-[#1f1c18]">{"< 20s"}</p>
                <p className="mt-2 text-sm leading-6 text-[#60554a]">Search-to-book flow tuned for ritual discovery without confusion.</p>
              </div>
              <div className="rounded-[1.6rem] border border-[#dac9b1] bg-white/75 p-5 shadow-[0_18px_40px_rgba(92,63,21,0.08)]">
                <p className="text-xs font-semibold uppercase tracking-[0.24em] text-[#8f6b2d]">Experience Split</p>
                <p className="mt-3 text-3xl font-semibold text-[#1f1c18]">Mobile + Desktop</p>
                <p className="mt-2 text-sm leading-6 text-[#60554a]">Search-first on mobile, productivity-first for admin and vendor workflows.</p>
              </div>
            </div>
          </div>

          <div className="relative z-10">
            <div className="rounded-[2.25rem] border border-[#d8c8b1] bg-[#fffdfa]/90 p-4 shadow-[0_32px_90px_rgba(92,63,21,0.18)]">
              <div className="rounded-[2rem] border border-[#ede0cd] bg-[linear-gradient(180deg,#fffefb_0%,#f6efe3_100%)] p-5">
                <div className="flex items-center justify-between">
                  <div>
                    <p className="text-sm font-semibold text-[#3b3027]">Today on Navdhya</p>
                    <p className="text-sm text-[#7d7163]">Ekadashi rituals and temple bookings available</p>
                  </div>
                  <Bell className="h-5 w-5 text-[#8f6b2d]" />
                </div>

                <div className="mt-5 rounded-[1.5rem] border border-[#e5d8c5] bg-white p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.8)]">
                  <div className="flex items-center gap-3 text-[#6a5b4b]">
                    <Search className="h-5 w-5 text-[#8f6b2d]" />
                    <span className="text-sm">Search for puja, temple, pandit, or muhurta</span>
                    <Mic className="ml-auto h-5 w-5 text-[#51638f]" />
                  </div>
                </div>

                <div className="mt-5 grid grid-cols-2 gap-3">
                  {categoryCards.slice(0, 4).map(({ title, icon: Icon }) => (
                    <div
                      key={title}
                      className="rounded-[1.4rem] border border-[#e6d8c5] bg-white px-4 py-4 shadow-[0_12px_28px_rgba(92,63,21,0.06)]"
                    >
                      <Icon className="h-5 w-5 text-[#0f4c5c]" />
                      <p className="mt-4 text-sm font-semibold text-[#2d241c]">{title}</p>
                    </div>
                  ))}
                </div>

                <div className="mt-5 space-y-3">
                  {spotlightCards.map((card) => (
                    <div
                      key={card.title}
                      className="overflow-hidden rounded-[1.6rem] border border-[#e5d8c5] bg-white shadow-[0_18px_36px_rgba(92,63,21,0.08)]"
                    >
                      <div className="relative h-44">
                        <Image alt={card.title} fill className="object-cover" sizes="(max-width: 1024px) 100vw, 420px" src={card.image} />
                        <div className="absolute left-3 top-3 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#4d3f30]">
                          {card.badge}
                        </div>
                      </div>
                      <div className="p-4">
                        <p className="text-lg font-semibold text-[#201a14]">{card.title}</p>
                        <p className="mt-1 text-sm leading-6 text-[#6b6156]">{card.description}</p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-2xl">
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Information Architecture</p>
            <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#1f1c18] sm:text-5xl">
              Search first, then guide every next step.
            </h2>
            <p className="mt-4 text-base leading-7 text-[#62584c]">
              The layout turns the homepage into a command center: sacred search, category discovery, urgency cues, and
              trust explanations before any hard sell.
            </p>
          </div>
          <div className="rounded-[1.6rem] border border-[#d8c8b1] bg-white px-5 py-4 text-sm text-[#5b5147] shadow-[0_18px_42px_rgba(92,63,21,0.08)]">
            Personalized cues: festival calendar, location, past bookings, and spiritual preferences.
          </div>
        </div>

        <div className="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
          {categoryCards.map(({ title, description, icon: Icon }) => (
            <div
              key={title}
              className="rounded-[1.8rem] border border-[#dcccb6] bg-white p-6 shadow-[0_20px_45px_rgba(92,63,21,0.07)] transition duration-300 hover:-translate-y-1 hover:shadow-[0_26px_55px_rgba(92,63,21,0.12)]"
            >
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f4eadb]">
                <Icon className="h-5 w-5 text-[#0f4c5c]" />
              </div>
              <h3 className="mt-5 text-lg font-semibold text-[#231c16]">{title}</h3>
              <p className="mt-3 text-sm leading-6 text-[#685d51]">{description}</p>
            </div>
          ))}
        </div>
      </section>

      <section className="border-y border-[#dcccb6] bg-[#f1e6d1]">
        <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">What&apos;s New</p>
              <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
                Discovery modules inspired by mobile, rebuilt for Navdhya.
              </h2>
            </div>
            <p className="max-w-xl text-base leading-7 text-[#615548]">
              The reference app gave us softness, rounded card rhythm, and lightweight navigation. The content, trust
              logic, and sacred tone are now unmistakably Navdhya.
            </p>
          </div>

          <div className="mt-8 grid gap-5 lg:grid-cols-2">
            {spotlightCards.map((card) => (
              <article
                key={card.title}
                className="overflow-hidden rounded-[2rem] border border-[#d9c8ae] bg-white shadow-[0_24px_60px_rgba(92,63,21,0.12)]"
              >
                <div className="relative h-64">
                  <Image alt={card.title} fill className="object-cover" sizes="(max-width: 1024px) 100vw, 50vw" src={card.image} />
                  <div className="absolute inset-0 bg-gradient-to-t from-[#271d13]/45 via-transparent to-transparent" />
                  <div className="absolute left-5 top-5 rounded-full bg-white/90 px-4 py-2 text-xs font-semibold tracking-[0.18em] text-[#473827]">
                    {card.badge}
                  </div>
                </div>
                <div className="space-y-3 p-6">
                  <h3 className="text-2xl font-semibold text-[#201a14]">{card.title}</h3>
                  <p className="text-base leading-7 text-[#665b4f]">{card.description}</p>
                  <Link href="/services" className="inline-flex items-center gap-2 text-sm font-semibold text-[#0f4c5c]">
                    View module
                    <ArrowRight className="h-4 w-4" />
                  </Link>
                </div>
              </article>
            ))}
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
          <div>
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Marketplace Liquidity</p>
            <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
              Priests, temples, and astrologers should feel alive, not listed.
            </h2>
            <div className="mt-8 grid gap-4 md:grid-cols-3">
              {marketplaceCards.map((card) => (
                <article
                  key={card.title}
                  className="overflow-hidden rounded-[1.8rem] border border-[#dcccb6] bg-white shadow-[0_18px_42px_rgba(92,63,21,0.08)]"
                >
                  <div className="relative h-52">
                    <Image alt={card.title} fill className="object-cover" sizes="(max-width: 1024px) 100vw, 33vw" src={card.image} />
                  </div>
                  <div className="space-y-2 p-5">
                    <div className="flex items-start justify-between gap-3">
                      <h3 className="text-lg font-semibold text-[#201a14]">{card.title}</h3>
                      <div className="inline-flex items-center gap-1 rounded-full bg-[#edf4ef] px-2.5 py-1 text-xs font-semibold text-[#2a6b40]">
                        <Star className="h-3.5 w-3.5 fill-current" />
                        4.9
                      </div>
                    </div>
                    <p className="text-sm font-medium text-[#3d3128]">{card.subtitle}</p>
                    <p className="text-sm text-[#6a5f53]">{card.meta}</p>
                    <div className="flex items-center gap-2 pt-2 text-sm font-semibold text-[#0f4c5c]">
                      <Clock3 className="h-4 w-4" />
                      {card.highlight}
                    </div>
                  </div>
                </article>
              ))}
            </div>
          </div>

          <div className="rounded-[2rem] border border-[#dcccb6] bg-white p-6 shadow-[0_26px_62px_rgba(92,63,21,0.1)]">
            <div className="flex items-center justify-between gap-4">
              <div>
                <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Urgency Cards</p>
                <h3 className="mt-2 text-2xl font-semibold text-[#201a14]">Festival-aware prompts</h3>
              </div>
              <CalendarDays className="h-6 w-6 text-[#0f4c5c]" />
            </div>
            <div className="mt-6 space-y-4">
              <div className="rounded-[1.4rem] bg-[#f7f1e6] p-4">
                <p className="text-sm font-semibold text-[#3b3027]">Tomorrow is Amavasya</p>
                <p className="mt-1 text-sm leading-6 text-[#675d52]">Surface fast-moving priest slots, samagri reminders, and home ritual readiness.</p>
              </div>
              <div className="rounded-[1.4rem] bg-[#f3e5cf] p-4">
                <p className="text-sm font-semibold text-[#3b3027]">Special Rudrabhishek windows filling</p>
                <p className="mt-1 text-sm leading-6 text-[#675d52]">Use waitlist logic and alternate temple suggestions to prevent drop-off.</p>
              </div>
              <div className="rounded-[1.4rem] bg-[#e9f0f2] p-4">
                <p className="text-sm font-semibold text-[#243642]">NRI family coordination detected</p>
                <p className="mt-1 text-sm leading-6 text-[#56636e]">Offer timezone-safe online puja, astrologer consult, and elder briefing templates.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="border-y border-[#d9c8ae] bg-white">
        <div className="mx-auto grid max-w-7xl gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[0.95fr_1.05fr] lg:px-8">
          <div className="rounded-[2rem] border border-[#dcccb6] bg-[#fcfaf6] p-6 shadow-[0_24px_58px_rgba(92,63,21,0.08)]">
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Booking Engine</p>
            <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
              Ritual booking without ritual confusion.
            </h2>
            <p className="mt-4 text-base leading-7 text-[#64594c]">
              Instead of one big form, Navdhya should guide the family across a clear eight-step flow and inject support
              when uncertainty is highest.
            </p>
            <div className="mt-8 grid gap-3">
              {bookingSteps.map((step, index) => (
                <div key={step} className="flex items-center gap-4 rounded-[1.2rem] border border-[#eadfcf] bg-white px-4 py-3">
                  <div className="flex h-9 w-9 items-center justify-center rounded-full bg-[#f3e5cf] text-sm font-semibold text-[#8f6b2d]">
                    {index + 1}
                  </div>
                  <p className="text-sm font-medium text-[#2d241c]">{step}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="space-y-6">
            <div className="rounded-[2rem] border border-[#dcccb6] bg-[#153945] p-6 text-white shadow-[0_28px_70px_rgba(15,76,92,0.24)]">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#d7b978]">Booking Tracker</p>
              <h3 className="mt-3 text-2xl font-semibold">From confirmed to blessings delivered</h3>
              <div className="mt-6 grid gap-3 sm:grid-cols-2">
                {["Confirmed", "Priest Assigned", "Preparation", "In Progress", "Completed", "Blessings Delivered"].map(
                  (status, index) => (
                    <div key={status} className="rounded-[1.3rem] border border-white/10 bg-white/8 px-4 py-4">
                      <p className="text-xs uppercase tracking-[0.22em] text-white/60">Stage {index + 1}</p>
                      <p className="mt-2 text-sm font-semibold text-white">{status}</p>
                    </div>
                  ),
                )}
              </div>
            </div>

            <div className="rounded-[2rem] border border-[#dcccb6] bg-[#f7f0e2] p-6 shadow-[0_24px_60px_rgba(92,63,21,0.08)]">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Smart Assistance</p>
              <div className="mt-4 grid gap-4 sm:grid-cols-3">
                <div className="rounded-[1.3rem] bg-white p-4">
                  <p className="text-sm font-semibold text-[#201a14]">Best Time</p>
                  <p className="mt-2 text-sm leading-6 text-[#675d52]">Muhurta suggestions with astrologer escalation if needed.</p>
                </div>
                <div className="rounded-[1.3rem] bg-white p-4">
                  <p className="text-sm font-semibold text-[#201a14]">Materials</p>
                  <p className="mt-2 text-sm leading-6 text-[#675d52]">Contextual samagri checklist tied to the chosen ritual package.</p>
                </div>
                <div className="rounded-[1.3rem] bg-white p-4">
                  <p className="text-sm font-semibold text-[#201a14]">Preparation</p>
                  <p className="mt-2 text-sm leading-6 text-[#675d52]">Family-facing readiness notes to reduce day-of friction.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div className="grid gap-6 lg:grid-cols-[0.92fr_1.08fr]">
          <div className="rounded-[2rem] border border-[#dcccb6] bg-[#f4ead8] p-6 shadow-[0_24px_62px_rgba(92,63,21,0.08)]">
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Trust Systems</p>
            <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
              Trust before conversion, on every screen.
            </h2>
            <div className="mt-6 space-y-3">
              {trustLayers.map((layer) => (
                <div key={layer} className="flex items-center gap-3 rounded-[1.3rem] border border-[#dfcfb9] bg-white px-4 py-4">
                  <ShieldCheck className="h-5 w-5 text-[#2a6b40]" />
                  <p className="text-sm font-medium text-[#2d241c]">{layer}</p>
                </div>
              ))}
            </div>
          </div>

          <div className="grid gap-4 sm:grid-cols-2">
            {roleCards.map((role) => (
              <div
                key={role.title}
                className="rounded-[1.8rem] border border-[#dcccb6] bg-white p-6 shadow-[0_18px_42px_rgba(92,63,21,0.08)]"
              >
                <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">{role.title}</p>
                <p className="mt-4 text-base leading-7 text-[#63594d]">{role.points}</p>
              </div>
            ))}
            <div className="rounded-[1.8rem] border border-[#153945]/15 bg-[#153945] p-6 text-white shadow-[0_24px_60px_rgba(15,76,92,0.24)] sm:col-span-2">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#d7b978]">Temple Admin Control Center</p>
              <p className="mt-4 max-w-3xl text-base leading-7 text-white/80">
                Event calendar, donation analytics, priest roster, darshan slots, crowd flow, festival orchestration,
                and live-stream management belong in one operational dashboard, not scattered tools.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section className="border-y border-[#d9c8ae] bg-[#fbf8f2]">
        <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
          <div className="max-w-2xl">
            <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Desktop Architecture</p>
            <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
              Desktop is not stretched mobile.
            </h2>
            <p className="mt-4 text-base leading-7 text-[#62584c]">
              For temple admins and high-intent planners, the desktop experience should behave like a calm productivity
              workspace with simultaneous filters, cards, and operational context.
            </p>
          </div>

          <div className="mt-8 rounded-[2.2rem] border border-[#dcccb6] bg-white p-4 shadow-[0_28px_70px_rgba(92,63,21,0.1)]">
            <div className="grid gap-4 lg:grid-cols-[0.24fr_0.46fr_0.3fr]">
              <div className="rounded-[1.7rem] bg-[#f4ead8] p-5">
                <p className="text-sm font-semibold text-[#3a3027]">Left Nav</p>
                <div className="mt-4 space-y-3">
                  {["Home", "Services", "Bookings", "Community", "Profile"].map((item) => (
                    <div key={item} className="rounded-[1rem] bg-white px-4 py-3 text-sm font-medium text-[#40362d]">
                      {item}
                    </div>
                  ))}
                </div>
              </div>

              <div className="rounded-[1.7rem] border border-[#e7dac6] bg-[#fffdf9] p-5">
                <div className="flex items-center gap-3 rounded-[1.2rem] border border-[#e7dac6] bg-white px-4 py-3">
                  <Search className="h-5 w-5 text-[#8f6b2d]" />
                  <p className="text-sm text-[#6f6458]">Universal search, quick actions, and recommendation rails</p>
                </div>
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                  {["Nearby Temples", "Popular Priests", "Trending Rituals", "Recommended Astrologers"].map((rail) => (
                    <div key={rail} className="rounded-[1.3rem] bg-[#f8f2e8] p-4">
                      <p className="text-sm font-semibold text-[#2f251d]">{rail}</p>
                      <p className="mt-2 text-sm text-[#6a5f53]">Curated with location, trust, and availability signals.</p>
                    </div>
                  ))}
                </div>
              </div>

              <div className="space-y-4 rounded-[1.7rem] bg-[#153945] p-5 text-white">
                <div className="rounded-[1.3rem] border border-white/10 bg-white/8 p-4">
                  <p className="text-sm font-semibold">Map / Calendar / Availability</p>
                  <p className="mt-2 text-sm leading-6 text-white/75">Keep decision context visible while users compare options.</p>
                </div>
                <div className="rounded-[1.3rem] border border-white/10 bg-white/8 p-4">
                  <p className="text-sm font-semibold">Vendor Ops</p>
                  <p className="mt-2 text-sm leading-6 text-white/75">Drag-drop slot management, recurring availability, and surge mode.</p>
                </div>
                <div className="rounded-[1.3rem] border border-white/10 bg-white/8 p-4">
                  <p className="text-sm font-semibold">Failure Prevention</p>
                  <p className="mt-2 text-sm leading-6 text-white/75">Conflicts, no-shows, low-trust listings, and unclear pricing are blocked early.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
        <div className="rounded-[2.4rem] border border-[#dcccb6] bg-[linear-gradient(135deg,#fffaf1_0%,#f3e5cf_55%,#e7d5b1_100%)] p-8 shadow-[0_30px_80px_rgba(92,63,21,0.12)] sm:p-10">
          <div className="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-3xl">
              <p className="text-sm font-semibold uppercase tracking-[0.22em] text-[#8f6b2d]">Implementation Direction</p>
              <h2 className="mt-3 font-sacred-display text-3xl tracking-[-0.04em] text-[#201a14] sm:text-5xl">
                Navdhya should feel like trust infrastructure, not a prettier directory.
              </h2>
              <p className="mt-4 text-base leading-7 text-[#5f5549]">
                This homepage now points the product in that direction: sacred minimalism, clearer behavioral UX,
                marketplace depth, and stronger trust explanations. The next layer is wiring these patterns into
                service listings, vendor panels, and booking flows.
              </p>
            </div>
            <div className="flex flex-wrap gap-3">
              <Link
                href="/services"
                className="inline-flex items-center justify-center rounded-full bg-[#153945] px-5 py-3 text-sm font-semibold text-white transition hover:bg-[#0f2f38]"
              >
                Explore service IA
              </Link>
              <Link
                href="/contact"
                className="inline-flex items-center justify-center rounded-full border border-[#b99763] bg-white/85 px-5 py-3 text-sm font-semibold text-[#3c3128] transition hover:bg-white"
              >
                Start the next refactor
              </Link>
            </div>
          </div>

          <div className="mt-8 grid gap-4 md:grid-cols-3">
            <div className="rounded-[1.4rem] bg-white/80 p-5">
              <p className="text-sm font-semibold text-[#251d16]">Journey Maps</p>
              <p className="mt-2 text-sm leading-6 text-[#64594d]">Devotee, priest, astrologer, and temple-admin flows need dedicated screens next.</p>
            </div>
            <div className="rounded-[1.4rem] bg-white/80 p-5">
              <p className="text-sm font-semibold text-[#251d16]">Component System</p>
              <p className="mt-2 text-sm leading-6 text-[#64594d]">Search, trust badges, steppers, timelines, analytics widgets, and status chips are now clearly defined.</p>
            </div>
            <div className="rounded-[1.4rem] bg-white/80 p-5">
              <p className="text-sm font-semibold text-[#251d16]">Technical Boundaries</p>
              <p className="mt-2 text-sm leading-6 text-[#64594d]">Next.js, TypeScript, Tailwind, and motion-ready structures remain aligned with the existing stack.</p>
            </div>
          </div>
        </div>
      </section>
    </main>
  );
}
