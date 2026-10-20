"use client";

import {
  useEffect,
  useRef,
  useState,
  type ComponentType,
  type KeyboardEvent,
} from "react";

import { useRouter } from "next/navigation";

import {
  ArrowLeft,
  ArrowRight,
  CalendarDays,
  Clock3,
  Compass,
  Flame,
  Gem,
  HeartPulse,
  Home,
  Search,
  Sparkles,
  UserRound,
  Volume2,
  X,
} from "lucide-react";

type IconType = ComponentType<{
  className?: string;
  strokeWidth?: number;
}>;

type SearchSuggestion = {
  id: string;
  label: string;
  type: "puja" | "pandit" | "service";
  icon: IconType;
  href: string;
};

type SearchCategory = {
  id: string;
  name: string;
  icon: IconType;
  href: string;
};

type MobileAnimatedSearchBarProps = {
  onSearch?: (query: string) => void;
  onCategorySelect?: (categoryId: string) => void;
  onRecentSelect?: (query: string) => void;
  onDiscoverSelect?: (tag: string) => void;
};

const DISCOVER_TAGS = [
  {
    id: "peace",
    label: "Peace of mind",
    href: "/book?intent=peace",
  },
  {
    id: "beginnings",
    label: "New beginnings",
    href: "/book?intent=new-beginnings",
  },
  {
    id: "wealth",
    label: "Wealth & prosperity",
    href: "/book?intent=wealth",
  },
  {
    id: "health",
    label: "Health & wellness",
    href: "/book?intent=health",
  },
];

const INITIAL_RECENT_SEARCHES = [
  "Satyanarayan Puja",
  "North Indian Pandit",
  "Vastu",
];

const CATEGORIES: SearchCategory[] = [
  {
    id: "daily-puja",
    name: "Daily Puja",
    icon: Flame,
    href: "/book?category=daily-puja",
  },
  {
    id: "astrology",
    name: "Astrology",
    icon: Sparkles,
    href: "/astrology",
  },
  {
    id: "vastu",
    name: "Vastu",
    icon: Compass,
    href: "/services?service=vastu",
  },
  {
    id: "festivals",
    name: "Festivals",
    icon: CalendarDays,
    href: "/festivals",
  },
  {
    id: "marriage",
    name: "Marriage",
    icon: HeartPulse,
    href: "/services?service=marriage",
  },
  {
    id: "havan",
    name: "Havan",
    icon: Flame,
    href: "/book?service=havan",
  },
];

const SUGGESTIONS: SearchSuggestion[] = [
  {
    id: "satyanarayan",
    label: "Satyanarayan Puja",
    type: "puja",
    icon: Flame,
    href: "/book?puja=satyanarayan",
  },
  {
    id: "griha-shanti",
    label: "Griha Shanti Puja",
    type: "puja",
    icon: Home,
    href: "/book?puja=griha-shanti",
  },
  {
    id: "lakshmi",
    label: "Lakshmi Puja",
    type: "puja",
    icon: Gem,
    href: "/book?puja=lakshmi",
  },
  {
    id: "pandit",
    label: "Find a Pandit",
    type: "pandit",
    icon: UserRound,
    href: "/pandits",
  },
  {
    id: "vastu-service",
    label: "Vastu Consultation",
    type: "service",
    icon: Compass,
    href: "/services?service=vastu",
  },
];

export default function MobileAnimatedSearchBar({
  onSearch,
  onCategorySelect,
  onRecentSelect,
  onDiscoverSelect,
}: MobileAnimatedSearchBarProps) {
  const router = useRouter();

  const [isOpen, setIsOpen] = useState(false);
  const [query, setQuery] = useState("");
  const [recentSearches, setRecentSearches] = useState(
    INITIAL_RECENT_SEARCHES,
  );
  const [isListening, setIsListening] = useState(false);
  const [voiceSupported, setVoiceSupported] = useState(false);

  const inputRef = useRef<HTMLInputElement>(null);

  const previousBodyOverflow = useRef("");
  const recognitionRef = useRef<{
    start: () => void;
    stop: () => void;
    onresult: ((event: any) => void) | null;
    onend: (() => void) | null;
    onerror: (() => void) | null;
  } | null>(null);

  /*
   * =========================================================
   * ROUTING
   * =========================================================
   */

  const navigate = (href: string) => {
    setIsOpen(false);
    setIsListening(false);
    router.push(href);
  };

  /*
   * =========================================================
   * SEARCH
   * =========================================================
   */

  const submitSearch = (value?: string) => {
    const trimmed = (value ?? query).trim();

    if (!trimmed) {
      inputRef.current?.focus();
      return;
    }

    setRecentSearches((current) => {
      const filtered = current.filter(
        (item) =>
          item.toLowerCase() !== trimmed.toLowerCase(),
      );

      return [trimmed, ...filtered].slice(0, 5);
    });

    onSearch?.(trimmed);

    // Default behavior.
    // Parent callback can still observe/override the search.
    navigate(
      `/services?search=${encodeURIComponent(trimmed)}`,
    );
  };

  /*
   * =========================================================
   * OPEN SEARCH
   * =========================================================
   */

  const openSearch = () => {
    setIsOpen(true);
  };

  /*
   * =========================================================
   * CLOSE SEARCH
   * =========================================================
   */

  const closeSearch = () => {
    setIsOpen(false);
    setIsListening(false);
  };

  /*
   * =========================================================
   * BODY SCROLL LOCK
   * =========================================================
   */

  useEffect(() => {
    if (!isOpen) return;

    previousBodyOverflow.current =
      document.body.style.overflow;

    document.body.style.overflow = "hidden";

    const frame = window.requestAnimationFrame(() => {
      inputRef.current?.focus();
    });

    return () => {
      window.cancelAnimationFrame(frame);

      document.body.style.overflow =
        previousBodyOverflow.current;
    };
  }, [isOpen]);

  /*
   * =========================================================
   * ESCAPE
   * =========================================================
   */

  useEffect(() => {
    if (!isOpen) return;

    const handleEscape = (event: globalThis.KeyboardEvent) => {
      if (event.key === "Escape") {
        closeSearch();
      }
    };

    document.addEventListener("keydown", handleEscape);

    return () => {
      document.removeEventListener(
        "keydown",
        handleEscape,
      );
    };
  }, [isOpen]);

  /*
   * =========================================================
   * VOICE SEARCH SUPPORT
   * =========================================================
   */

  useEffect(() => {
    if (typeof window === "undefined") return;

    const browserWindow =
      window as typeof window & {
        SpeechRecognition?: any;
        webkitSpeechRecognition?: any;
      };

    const SpeechRecognition =
      browserWindow.SpeechRecognition ??
      browserWindow.webkitSpeechRecognition;

    if (!SpeechRecognition) {
      setVoiceSupported(false);
      return;
    }

    setVoiceSupported(true);

    const recognition = new SpeechRecognition();

    recognition.lang = "en-IN";
    recognition.interimResults = false;
    recognition.continuous = false;

    recognition.onresult = (event: any) => {
      const transcript =
        event.results?.[0]?.[0]?.transcript ?? "";

      if (!transcript) return;

      setQuery(transcript);
      setIsListening(false);

      requestAnimationFrame(() => {
        submitSearch(transcript);
      });
    };

    recognition.onend = () => {
      setIsListening(false);
    };

    recognition.onerror = () => {
      setIsListening(false);
    };

    recognitionRef.current = recognition;

    return () => {
      try {
        recognition.stop();
      } catch {
        // Recognition may already be stopped.
      }

      recognitionRef.current = null;
    };
  }, []);

  /*
   * =========================================================
   * VOICE SEARCH
   * =========================================================
   */

  const toggleVoiceSearch = () => {
    if (!voiceSupported || !recognitionRef.current) {
      return;
    }

    if (isListening) {
      try {
        recognitionRef.current.stop();
      } catch {
        // Ignore already stopped state.
      }

      setIsListening(false);
      return;
    }

    try {
      setIsListening(true);
      recognitionRef.current.start();
    } catch {
      setIsListening(false);
    }
  };

  /*
   * =========================================================
   * INPUT
   * =========================================================
   */

  const handleInputKeyDown = (
    event: KeyboardEvent<HTMLInputElement>,
  ) => {
    if (event.key === "Enter") {
      event.preventDefault();
      submitSearch();
    }
  };

  /*
   * =========================================================
   * FILTER SUGGESTIONS
   * =========================================================
   */

  const normalizedQuery = query.trim().toLowerCase();

  const filteredSuggestions = normalizedQuery
    ? SUGGESTIONS.filter((item) =>
      item.label
        .toLowerCase()
        .includes(normalizedQuery),
    )
    : [];

  /*
   * =========================================================
   * DISCOVER ACTION
   * =========================================================
   */

  const handleDiscover = (
    tag: (typeof DISCOVER_TAGS)[number],
  ) => {
    setQuery(tag.label);

    onDiscoverSelect?.(tag.label);

    navigate(tag.href);
  };

  /*
   * =========================================================
   * RECENT SEARCH
   * =========================================================
   */

  const handleRecent = (search: string) => {
    setQuery(search);

    onRecentSelect?.(search);

    submitSearch(search);
  };

  /*
   * =========================================================
   * CATEGORY
   * =========================================================
   */

  const handleCategory = (
    category: SearchCategory,
  ) => {
    onCategorySelect?.(category.id);

    navigate(category.href);
  };

  /*
   * =========================================================
   * CLEAR RECENT
   * =========================================================
   */

  const clearRecentSearches = () => {
    setRecentSearches([]);
  };

  /*
   * =========================================================
   * RENDER
   * =========================================================
   */

  return (
    <>
      {/* =====================================================
          SEARCH TRIGGER
      ====================================================== */}

      <button
        type="button"
        onClick={openSearch}
        aria-label="Open search"
        className="
          flex
          h-[46px]
          w-full
          items-center
          gap-3
          rounded-full
          border
          border-[#E4E7EC]
          bg-white
          px-4
          shadow-[0_3px_12px_rgba(16,24,40,0.07)]
          transition
          hover:border-[#D0D5DD]
          active:scale-[0.99]
          focus-visible:outline-none
          focus-visible:ring-2
          focus-visible:ring-[#E8A846]
          focus-visible:ring-offset-2
        "
      >
        <Search
          className="
            h-[17px]
            w-[17px]
            shrink-0
            text-[#98A2B3]
          "
          strokeWidth={2}
        />

        <span
          className="
            min-w-0
            flex-1
            truncate
            text-left
            text-[13px]
            font-medium
            text-[#667085]
          "
        >
          Search pujas, pandits, rituals...
        </span>

        <span
          className="
            flex
            h-[30px]
            w-[30px]
            shrink-0
            items-center
            justify-center
            rounded-full
            border
            border-[#EAECF0]
            bg-[#FAFAFA]
          "
          aria-hidden="true"
        >
          <Sparkles
            className="
              h-[14px]
              w-[14px]
              text-[#475467]
            "
            strokeWidth={1.8}
          />
        </span>
      </button>

      {/* =====================================================
          SEARCH SCREEN
      ====================================================== */}

      {isOpen && (
        <div
          className="
            fixed
            inset-0
            z-[999]
            flex
            flex-col
            bg-white
          "
          role="dialog"
          aria-modal="true"
          aria-label="Navdhya search"
        >
          {/* =================================================
              HEADER
          ================================================== */}

          <header
            className="
              shrink-0
              border-b
              border-[#EAECF0]
              bg-white
              px-3
              pb-3
              pt-[max(10px,env(safe-area-inset-top))]
            "
          >
            <div className="flex items-center gap-2">
              {/* Back */}
              <button
                type="button"
                onClick={closeSearch}
                aria-label="Close search"
                className="
                  flex
                  h-[44px]
                  w-[44px]
                  shrink-0
                  items-center
                  justify-center
                  rounded-full
                  text-[#344054]
                  transition
                  hover:bg-[#F2F4F7]
                  active:scale-95
                  focus-visible:outline-none
                  focus-visible:ring-2
                  focus-visible:ring-[#E8A846]
                "
              >
                <ArrowLeft
                  className="h-5 w-5"
                  strokeWidth={1.9}
                />
              </button>

              {/* Search input */}
              <div
                className="
                  flex
                  h-[46px]
                  min-w-0
                  flex-1
                  items-center
                  gap-2.5
                  rounded-full
                  border
                  border-[#D0D5DD]
                  bg-[#F9FAFB]
                  px-3.5
                  transition
                  focus-within:border-[#023020]
                  focus-within:bg-white
                  focus-within:ring-2
                  focus-within:ring-[#023020]/10
                "
              >
                <Search
                  className="
                    h-[17px]
                    w-[17px]
                    shrink-0
                    text-[#667085]
                  "
                  strokeWidth={2}
                />

                <label
                  htmlFor="navdhya-mobile-search"
                  className="sr-only"
                >
                  Search Navdhya
                </label>

                <input
                  ref={inputRef}
                  id="navdhya-mobile-search"
                  type="search"
                  inputMode="search"
                  autoComplete="off"
                  value={query}
                  onChange={(event) =>
                    setQuery(event.target.value)
                  }
                  onKeyDown={handleInputKeyDown}
                  placeholder="Puja, pandit, ritual..."
                  className="
                    min-w-0
                    flex-1
                    bg-transparent
                    text-[14px]
                    font-medium
                    text-[#101828]
                    outline-none
                    placeholder:text-[#98A2B3]
                  "
                />

                {query.length > 0 && (
                  <button
                    type="button"
                    onClick={() => setQuery("")}
                    aria-label="Clear search"
                    className="
                      flex
                      h-6
                      w-6
                      shrink-0
                      items-center
                      justify-center
                      rounded-full
                      bg-[#E4E7EC]
                      text-[#667085]
                      active:scale-95
                    "
                  >
                    <X className="h-3.5 w-3.5" />
                  </button>
                )}
              </div>

              {/* Voice */}
              <button
                type="button"
                onClick={toggleVoiceSearch}
                disabled={!voiceSupported}
                aria-label={
                  voiceSupported
                    ? isListening
                      ? "Stop voice search"
                      : "Start voice search"
                    : "Voice search unavailable"
                }
                className={`
                  flex
                  h-[44px]
                  w-[44px]
                  shrink-0
                  items-center
                  justify-center
                  rounded-full
                  border
                  transition
                  focus-visible:outline-none
                  focus-visible:ring-2
                  focus-visible:ring-[#E8A846]
                  ${isListening
                    ? "border-[#FF4B16] bg-[#FFF3EE] text-[#FF4B16]"
                    : voiceSupported
                      ? "border-[#EAECF0] bg-white text-[#344054]"
                      : "border-[#F2F4F7] bg-[#F9FAFB] text-[#D0D5DD]"
                  }
                `}
              >
                <Volume2
                  className="h-[18px] w-[18px]"
                  strokeWidth={1.8}
                />
              </button>
            </div>

            {isListening && (
              <p className="px-[56px] pt-2 text-[10px] font-medium text-[#FF4B16]">
                Listening… speak now
              </p>
            )}
          </header>

          {/* =================================================
              SCROLL CONTENT
          ================================================== */}

          <div
            className="
              scrollbar-hide
              flex-1
              overflow-y-auto
              overscroll-contain
              bg-white
              pb-[max(32px,env(safe-area-inset-bottom))]
            "
          >
            {/* =================================================
                ACTIVE QUERY
            ================================================== */}

            {normalizedQuery ? (
              <section className="px-5 pt-6">
                <p
                  className="
                    text-[10px]
                    font-semibold
                    uppercase
                    tracking-[0.14em]
                    text-[#98A2B3]
                  "
                >
                  Search
                </p>

                <h2
                  className="
                    mt-1
                    text-[20px]
                    font-semibold
                    tracking-[-0.4px]
                    text-[#101828]
                  "
                >
                  Results for “{query.trim()}”
                </h2>

                {filteredSuggestions.length > 0 ? (
                  <div
                    className="
                      mt-4
                      overflow-hidden
                      rounded-2xl
                      border
                      border-[#EAECF0]
                      bg-white
                    "
                  >
                    {filteredSuggestions.map(
                      (suggestion, index) => {
                        const Icon = suggestion.icon;

                        return (
                          <button
                            key={suggestion.id}
                            type="button"
                            onClick={() => {
                              setRecentSearches((current) => {
                                const filtered =
                                  current.filter(
                                    (item) =>
                                      item !==
                                      suggestion.label,
                                  );

                                return [
                                  suggestion.label,
                                  ...filtered,
                                ].slice(0, 5);
                              });

                              onSearch?.(
                                suggestion.label,
                              );

                              navigate(suggestion.href);
                            }}
                            className={`
                              flex
                              w-full
                              items-center
                              gap-3
                              px-4
                              py-3.5
                              text-left
                              transition
                              hover:bg-[#FAFAFA]
                              active:bg-[#F2F4F7]
                              ${index !==
                                filteredSuggestions.length -
                                1
                                ? "border-b border-[#F2F4F7]"
                                : ""
                              }
                            `}
                          >
                            <span
                              className="
                                flex
                                h-10
                                w-10
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-[#F7F4EC]
                                text-[#023020]
                              "
                            >
                              <Icon
                                className="h-[18px] w-[18px]"
                                strokeWidth={1.8}
                              />
                            </span>

                            <span className="min-w-0 flex-1">
                              <span
                                className="
                                  block
                                  truncate
                                  text-[13px]
                                  font-semibold
                                  text-[#101828]
                                "
                              >
                                {suggestion.label}
                              </span>

                              <span
                                className="
                                  mt-0.5
                                  block
                                  text-[11px]
                                  capitalize
                                  text-[#98A2B3]
                                "
                              >
                                {suggestion.type}
                              </span>
                            </span>

                            <ArrowRight
                              className="
                                h-4
                                w-4
                                shrink-0
                                text-[#98A2B3]
                              "
                              strokeWidth={1.7}
                            />
                          </button>
                        );
                      },
                    )}
                  </div>
                ) : (
                  <div
                    className="
                      mt-5
                      rounded-2xl
                      border
                      border-dashed
                      border-[#D0D5DD]
                      px-5
                      py-8
                      text-center
                    "
                  >
                    <Search
                      className="
                        mx-auto
                        h-7
                        w-7
                        text-[#98A2B3]
                      "
                      strokeWidth={1.5}
                    />

                    <p
                      className="
                        mt-3
                        text-[13px]
                        font-semibold
                        text-[#344054]
                      "
                    >
                      No direct match found
                    </p>

                    <p
                      className="
                        mt-1
                        text-[11px]
                        leading-relaxed
                        text-[#98A2B3]
                      "
                    >
                      Press Enter to search Navdhya
                      services.
                    </p>

                    <button
                      type="button"
                      onClick={() => submitSearch()}
                      className="
                        mt-4
                        inline-flex
                        h-9
                        items-center
                        gap-2
                        rounded-full
                        bg-[#023020]
                        px-4
                        text-[11px]
                        font-semibold
                        text-white
                        active:scale-[0.98]
                      "
                    >
                      Search “{query.trim()}”
                      <ArrowRight
                        className="h-3.5 w-3.5"
                      />
                    </button>
                  </div>
                )}
              </section>
            ) : (
              <>
                {/* =========================================
                    DISCOVER
                ========================================== */}

                <section className="pt-6">
                  <div className="px-5">
                    <p
                      className="
                        text-[10px]
                        font-semibold
                        uppercase
                        tracking-[0.14em]
                        text-[#98A2B3]
                      "
                    >
                      Discover
                    </p>

                    <h2
                      className="
                        mt-1
                        text-[22px]
                        font-semibold
                        tracking-[-0.5px]
                        text-[#101828]
                      "
                    >
                      Find what feels right
                    </h2>
                  </div>

                  <div
                    className="
                      scrollbar-hide
                      mt-4
                      flex
                      gap-2.5
                      overflow-x-auto
                      px-5
                      pb-1
                    "
                  >
                    {DISCOVER_TAGS.map((tag) => (
                      <button
                        key={tag.id}
                        type="button"
                        onClick={() => handleDiscover(tag)}
                        className="
                          flex
                          shrink-0
                          items-center
                          gap-2
                          rounded-full
                          border
                          border-[#EAECF0]
                          bg-white
                          px-3.5
                          py-2.5
                          shadow-[0_2px_8px_rgba(16,24,40,0.04)]
                          transition
                          hover:border-[#D0D5DD]
                          active:scale-[0.98]
                        "
                      >
                        <Sparkles
                          className="
                            h-3.5
                            w-3.5
                            text-[#E8A846]
                          "
                          strokeWidth={1.9}
                        />

                        <span
                          className="
                            whitespace-nowrap
                            text-[12px]
                            font-medium
                            text-[#344054]
                          "
                        >
                          {tag.label}
                        </span>
                      </button>
                    ))}
                  </div>
                </section>

                {/* =========================================
                    RECENT
                ========================================== */}

                {recentSearches.length > 0 && (
                  <section className="mt-8 px-5">
                    <div className="flex items-center justify-between">
                      <div>
                        <p
                          className="
                            text-[10px]
                            font-semibold
                            uppercase
                            tracking-[0.14em]
                            text-[#98A2B3]
                          "
                        >
                          Recent
                        </p>

                        <h3
                          className="
                            mt-1
                            text-[17px]
                            font-semibold
                            text-[#101828]
                          "
                        >
                          Your recent searches
                        </h3>
                      </div>

                      <button
                        type="button"
                        onClick={clearRecentSearches}
                        className="
                          text-[11px]
                          font-semibold
                          text-[#667085]
                          underline
                          underline-offset-2
                          active:text-[#023020]
                        "
                      >
                        Clear
                      </button>
                    </div>

                    <div
                      className="
                        mt-3
                        overflow-hidden
                        rounded-2xl
                        border
                        border-[#EAECF0]
                        bg-white
                      "
                    >
                      {recentSearches.map(
                        (search, index) => (
                          <button
                            key={search}
                            type="button"
                            onClick={() =>
                              handleRecent(search)
                            }
                            className={`
                              flex
                              w-full
                              items-center
                              gap-3
                              px-4
                              py-3
                              text-left
                              transition
                              hover:bg-[#FAFAFA]
                              active:bg-[#F2F4F7]
                              ${index !==
                                recentSearches.length - 1
                                ? "border-b border-[#F2F4F7]"
                                : ""
                              }
                            `}
                          >
                            <Clock3
                              className="
                                h-4
                                w-4
                                shrink-0
                                text-[#98A2B3]
                              "
                              strokeWidth={1.8}
                            />

                            <span
                              className="
                                min-w-0
                                flex-1
                                truncate
                                text-[12px]
                                font-medium
                                text-[#475467]
                              "
                            >
                              {search}
                            </span>

                            <ArrowRight
                              className="
                                h-3.5
                                w-3.5
                                text-[#C1C7D0]
                              "
                              strokeWidth={1.7}
                            />
                          </button>
                        ),
                      )}
                    </div>
                  </section>
                )}

                {/* =========================================
                    CATEGORIES
                ========================================== */}

                <section className="mt-8 px-5">
                  <p
                    className="
                      text-[10px]
                      font-semibold
                      uppercase
                      tracking-[0.14em]
                      text-[#98A2B3]
                    "
                  >
                    Explore
                  </p>

                  <h3
                    className="
                      mt-1
                      text-[17px]
                      font-semibold
                      text-[#101828]
                    "
                  >
                    What are you looking for?
                  </h3>

                  <div className="mt-4 grid grid-cols-3 gap-2.5">
                    {CATEGORIES.map((category) => {
                      const Icon = category.icon;

                      return (
                        <button
                          key={category.id}
                          type="button"
                          onClick={() =>
                            handleCategory(category)
                          }
                          className="
                            group
                            flex
                            min-h-[102px]
                            flex-col
                            items-center
                            justify-center
                            gap-3
                            rounded-2xl
                            border
                            border-[#EAECF0]
                            bg-white
                            px-2
                            py-4
                            text-center
                            shadow-[0_2px_8px_rgba(16,24,40,0.025)]
                            transition
                            hover:border-[#D0D5DD]
                            hover:bg-[#FAFAF8]
                            active:scale-[0.98]
                            focus-visible:outline-none
                            focus-visible:ring-2
                            focus-visible:ring-[#E8A846]
                          "
                        >
                          <span
                            className="
                              flex
                              h-11
                              w-11
                              items-center
                              justify-center
                              rounded-xl
                              bg-[#F7F4EC]
                              text-[#023020]
                            "
                          >
                            <Icon
                              className="h-5 w-5"
                              strokeWidth={1.7}
                            />
                          </span>

                          <span
                            className="
                              text-[11px]
                              font-semibold
                              leading-tight
                              text-[#344054]
                            "
                          >
                            {category.name}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                </section>
              </>
            )}
          </div>
        </div>
      )}
    </>
  );
}