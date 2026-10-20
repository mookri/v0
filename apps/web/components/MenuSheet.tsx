"use client";

import React, { useEffect, useRef, useState } from "react";
import Link from "next/link";
import { MENU_GROUPS } from "@/lib/menuData";
import { X, Search, ChevronDown } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

/**
 * MenuSheet: full-screen bottom sheet menu with search, grouped items, accordion.
 * - mobile-first
 * - accessible (escape to close, click outside to close)
 */

type MenuSheetProps = {
  open: boolean;
  onClose: () => void;
};

function useOnClickOutside(ref: React.RefObject<HTMLElement>, handler: () => void) {
  useEffect(() => {
    const listener = (e: MouseEvent | TouchEvent) => {
      const el = ref.current;
      if (!el || el.contains(e.target as Node)) return;
      handler();
    };
    document.addEventListener("mousedown", listener);
    document.addEventListener("touchstart", listener);
    return () => {
      document.removeEventListener("mousedown", listener);
      document.removeEventListener("touchstart", listener);
    };
  }, [ref, handler]);
}

export const MenuSheet: React.FC<MenuSheetProps> = ({ open, onClose }) => {
  const sheetRef = useRef<HTMLDivElement>(null);
  const [query, setQuery] = useState("");
  const [expandedGroups, setExpandedGroups] = useState<Record<string, boolean>>({});

  useOnClickOutside(sheetRef as React.RefObject<HTMLElement>, () => {
    // don't auto-close if not open
    if (open) onClose();
  });

  useEffect(() => {
    const handler = (e: KeyboardEvent) => {
      if (e.key === "Escape") onClose();
    };
    document.addEventListener("keydown", handler);
    return () => document.removeEventListener("keydown", handler);
  }, [onClose]);

  useEffect(() => {
    if (open) {
      setQuery("");
      // expand top group by default
      setExpandedGroups({ [MENU_GROUPS[0].group]: true });
    }
  }, [open]);

  const filteredGroups = MENU_GROUPS.map((group) => {
    const filteredItems = group.items.filter((it) =>
      it.label.toLowerCase().includes(query.toLowerCase())
    );
    return { ...group, items: filteredItems };
  }).filter((g) => g.items.length > 0);

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          key="menu-sheet"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-sm"
        >
          <motion.div
            ref={sheetRef}
            initial={{ y: "100%" }}
            animate={{ y: 0 }}
            exit={{ y: "100%" }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="fixed bottom-0 left-0 right-0 max-h-[92vh] bg-white rounded-t-2xl shadow-2xl overflow-hidden border-t-4 border-[#D4AF37]"
            role="dialog"
            aria-modal="true"
          >
            <div className="px-4 py-3 flex items-center justify-between border-b border-gray-100">
              <div className="flex items-center gap-3">
                <div className="bg-[#0E3F2E] text-white rounded-lg p-2">
                  {/* small logo area — replace with image if available */}
                  <span className="sr-only">Navdhya</span>
                </div>
                <div>
                  <h3 className="text-[#0E3F2E] font-bold text-xl tracking-tight">Navdhya Menu</h3>
                  <p className="text-xs text-gray-500">Find pujas, pandits, & services</p>
                </div>
              </div>

              <button
                aria-label="Close menu"
                onClick={onClose}
                className="p-2 rounded-full hover:bg-gray-100 transition-colors duration-200"
              >
                <X className="h-5 w-5 text-gray-600" />
              </button>
            </div>

            {/* Search */}
            <div className="px-4 py-3 border-b border-gray-100 bg-gray-50">
              <label className="relative block">
                <span className="sr-only">Search menu</span>
                <input
                  value={query}
                  onChange={(e) => setQuery(e.target.value)}
                  placeholder="Search pujas, pandits, topics..."
                  className="w-full pr-10 text-sm py-3 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/30 focus:border-[#0E3F2E] shadow-sm"
                />
                <Search className="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
              </label>
            </div>

            {/* Content */}
            <div className="px-4 py-3 overflow-y-auto max-h-[60vh]">
              {filteredGroups.length === 0 && (
                <div className="py-12 text-center text-sm text-gray-500">
                  <Search className="h-12 w-12 mx-auto text-gray-300 mb-3" />
                  <p className="font-medium">No results found</p>
                  <p className="text-xs mt-1">Try different search terms</p>
                </div>
              )}

              {filteredGroups.map((group) => (
                <div key={group.group} className="mb-4">
                  <button
                    onClick={() =>
                      setExpandedGroups((s) => ({ ...s, [group.group]: !s[group.group] }))
                    }
                    className="w-full flex items-center justify-between py-3 hover:bg-gray-50 rounded-lg px-2 transition-colors duration-200"
                    aria-expanded={!!expandedGroups[group.group]}
                  >
                    <div>
                      <h4 className="text-[#0E3F2E] font-bold text-lg">{group.group}</h4>
                      <p className="text-xs text-gray-500">
                        {group.items.length} item{group.items.length > 1 ? "s" : ""}
                      </p>
                    </div>
                    <ChevronDown
                      className={`h-5 w-5 text-gray-500 transition-transform duration-300 ${
                        expandedGroups[group.group] ? "rotate-180" : ""
                      }`}
                    />
                  </button>

                  {expandedGroups[group.group] && (
                    <motion.div
                      initial={{ opacity: 0, height: 0 }}
                      animate={{ opacity: 1, height: "auto" }}
                      exit={{ opacity: 0, height: 0 }}
                      transition={{ duration: 0.3 }}
                      className="mt-2"
                    >
                      <div className="space-y-1">
                        {group.items.map((item) => (
                          <Link
                            key={item.label}
                            href={item.href}
                            onClick={() => onClose()}
                            className="flex items-center gap-3 py-3 px-3 rounded-lg hover:bg-gray-50 transition-all duration-200 border border-transparent hover:border-gray-200"
                          >
                            {item.icon ? (
                              <div className="bg-[#0E3F2E]/10 p-2 rounded-lg">
                                <item.icon className="h-5 w-5 text-[#D4AF37]" />
                              </div>
                            ) : (
                              <div className="h-5 w-5" />
                            )}
                            <div>
                              <div className="font-semibold text-[#0E3F2E]">
                                {item.label}
                              </div>
                              <div className="text-xs text-gray-500">Explore</div>
                            </div>
                          </Link>
                        ))}
                      </div>
                    </motion.div>
                  )}
                </div>
              ))}
            </div>

            {/* footer CTA */}
            <div className="px-4 py-4 border-t border-gray-100 bg-gray-50">
              <div className="flex flex-col sm:flex-row gap-3">
                <Link
                  href="/pujas/quick-book"
                  className="flex-1 bg-[#0E3F2E] text-white rounded-lg py-3 text-center font-bold hover:bg-[#0C3726] transition-colors duration-300 shadow-md"
                  onClick={() => onClose()}
                >
                  Quick Book Puja
                </Link>
                <Link
                  href="/contact"
                  className="flex-1 border-2 border-[#0E3F2E] text-[#0E3F2E] rounded-lg py-3 text-center font-bold hover:bg-[#0E3F2E]/5 transition-colors duration-300"
                  onClick={() => onClose()}
                >
                  Contact / WhatsApp
                </Link>
              </div>
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default MenuSheet;