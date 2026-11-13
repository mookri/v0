Ran list all modules in platform-navdhya
Ran list Platform subsystems

Here is the complete inventory of business and platform modules in the Navdhya codebase that we can focus on next, grouped by domain:

---

### 1. Supply & Service Fulfillment Modules
* **`Pandit` (`app/Modules/Pandit`)**:
  - Vedic traditions (Smartha, Vaishnava, Shaiva), Shakhas, Gotra, languages, and rituals mastered.
  - Granular availability windows, travel buffer zones, and calendar blackouts.
  - Pandit performance ratings, puja fulfillment tracking, and assignment acceptance rules.
* **`Temple` (`app/Modules/Temple`)**:
  - Temple profiles, deities, sanctum rules, daily aarti schedules, and priest councils.
  - Temple homa/darshan slot capacity controls and physical venue booking limits.
* **`Vendor` (`app/Modules/Vendor`)**:
  - Puja Samagri kits (Satyanarayan kit, Rudrabhishek samagri, Havan wood, ghee).
  - Multi-warehouse stock tracking, vendor order fulfillment, and delivery dispatch.

---

### 2. Devotee & Demand Experience Modules
* **`Customers` (`app/Modules/Customers`)**:
  - Devotee spiritual profile: Gotra, Nakshatra, Rashi, Kuladevata, and family member details.
  - Annual tithi / ancestral ritual reminders (Shradh, birthdays, wedding anniversaries).
  - Multi-channel notification preferences (WhatsApp, SMS via MSG91, Email).
* **`Catalog` (`app/Modules/Catalog`)**:
  - Central directory of Rituals, Havans, Sanskars, Astrology, and Temple Pujas.
  - Dynamic duration rules, required samagri kit bindings, and tiered pricing.

---

### 3. Discovery & Ritual Knowledge Modules
* **`Search` (`app/Modules/Search`)**:
  - Sanskrit & Devanagari transliteration engine (phonetic search, e.g., "Rudrabhishek" vs "रुद्राभिषेक").
  - Multi-entity search across Pujas, Pandits, and Temples with locality filtering.
* **`SeoContent` (`app/Modules/SeoContent`)**:
  - Hindu Panchang calendar, auspicious muhurat calculations, and festival guides.

---

### 4. Trust, Safety & Compliance
* **`Onboarding` (`app/Modules/Onboarding`)**:
  - Pandit qualification vetting (Veda Pathashala certificate / Vidwat Patra verification).
  - Temple trustee verification, vendor GSTIN & bank penny-drop validation.
  - KYC/AML verification pipeline and background check approvals.

---

Which of these modules (**Pandit**, **Temple**, **Vendor**, **Customers**, **Catalog**, **Search**, or **Onboarding**) would you like us to focus on?

Ran list root directories
Ran list apps, packages, services, workers

Here is the full ecosystem breakdown across the entire Navdhya Ritual Services monorepo, covering every package, frontend app, backend subsystem, and integration layer:

---

### 1. Devotee Personalization & Astrology Engine
* **`@navdhya/personalization` & `@navdhya/recommendations` (`packages/personalization`, `packages/recommendations`)**:
  - **Kundli & Astrological Profiling**: Recommends pujas, havans, and remedial rituals based on devotee Rashi, Nakshatra, and current planetary transits (*Dasha/Gochar*).
  - **Lifecycle Milestones (*Sanskars*)**: Suggests timely rituals for life events (Garbhadhana, Namakaran, Mundan, Upanayana, Vivaha, Griha Pravesh, Shradh/Pitru Paksha).
* **`@navdhya/customer-profile` (`packages/customer-profile`)**:
  - Devotee family tree management, Gotra lineage, Kuladevata/Ishtadevata tracking, and annual ancestral death anniversary (*Tithi*) calendar reminders.

---

### 2. Vedic Content, Muhurat & AI Discovery (AEO/SEO)
* **`@navdhya/aeo` & `@navdhya/seo` (`packages/aeo`, `packages/seo`, `packages/content`)**:
  - **AI Engine Optimization (AEO)**: Structured JSON-LD / schema.org spiritual markup formatted specifically for LLM search agents (ChatGPT, Gemini, Perplexity) answering queries like *"How to perform Satyanarayan Puja at home"*.
  - **Panchang & Muhurat Engine**: Automated calculation of Choghadiya, Rahu Kaal, Shubh Muhurats, and festival timings for 100+ cities globally.
* **`@navdhya/i18n` (`packages/i18n`)**:
  - Multi-language localization across 9 languages: Sanskrit, Hindi, Tamil, Telugu, Kannada, Marathi, Gujarati, Bengali, and English.

---

### 3. Communications, Notifications & WhatsApp Business
* **`@navdhya/communication` & `@navdhya/notifications` (`packages/communication`, `packages/notifications`)**:
  - **WhatsApp Business API**: Live automated Puja confirmation cards, Pandit arrival alerts, live streaming links, and post-puja digital prasad dispatch tracking.
  - **SMS via MSG91 (`@navdhya/otp`)**: Multi-tiered OTP verification, failover routing, and booking reminders.
  - **Transactional Email**: Rich, brand-compliant receipt and invoice delivery (`#FFF7ED` Warm Ivory, `#2C2033` Aubergine Ink, `#F16A24` Navdhya Flame).

---

### 4. Trust, Support & Devotee Satisfaction
* **`@navdhya/feedback` (`packages/feedback`)**:
  - Devotee post-puja satisfaction ratings, Vedic chanting quality feedback, and Pandit punctuality scoring.
* **`@navdhya/support` & `@navdhya/incident` (`packages/support`, `packages/incident`)**:
  - Dispute resolution, Pandit rescheduling emergencies, temple access issues, and service replacement workflows.

---

### 5. Multi-App Frontend Portfolio (`apps/`)
* **`apps/customer`**:
  - Devotee web application: ritual discovery, Pandit selection, instant checkout, address & gotra forms, and booking history.
* **`apps/partner`**:
  - Pandit and Temple mobile-first web app: daily assignment queue, one-click `:accept`/`:start`/`:complete`, calendar conflict alerts, and net compensation tracking.
* **`apps/dashboard`**:
  - Platform Operations & Compliance console: Pandit dispatch oversight, Maker/Checker payout approval queue, tax datasets, and tenant billing oversight.
* **`apps/web`**:
  - Devotee acquisition portal: high-performance marketing landing pages, muhurat tools, festival guides, and temple directories.

---

### 6. Design System & Sacred Ember Tokens
* **`@navdhya/design-system` (`packages/design-system`)**:
  - Strict compliance with `AGENTS.md`: Warm Ivory (`#FFF7ED`, 45%), Aubergine Ink (`#2C2033`, 22%), Navdhya Flame (`#F16A24`, 14%), Soft Sand (`#EFE3D3`, 10%), Deep Ember (`#B74717`, 6%), Deep Text (`#2A2421`, 3%).
  - Zero green anywhere in the core identity.

---

### 7. Playwright E2E User Journeys (`e2e/`)
* Real browser automated user journeys:
  - Discovery $\to$ Kundli details $\to$ Pandit selection $\to$ Checkout hold $\to$ Razorpay modal $\to$ Booking Confirmation $\to$ Pandit App Acceptance $\to$ Completion & Invoice Generation.

---

Which of these domains would you like to explore or build out further:
1. **Astrology & Ritual Personalization** (`Kundli / Muhurat / Recommendations`)
2. **WhatsApp & Omnichannel Notifications** (`MSG91 / WhatsApp / Email`)
3. **Admin & Operator Dashboard Panels** (`apps/dashboard` & console views)
4. **Devotee Web / Mobile UI Experience** (`apps/customer` & `apps/web`)
5. **Pandit Partner Mobile Web App** (`apps/partner`)
6. **Playwright E2E Browser Test Journeys** (`e2e/`)