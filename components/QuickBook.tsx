"use client";

import React, { useEffect, useRef, useState } from "react";
import { motion, AnimatePresence } from "framer-motion";
import { X, Calendar, Clock, User, CheckCircle } from "lucide-react";

/**
 * QuickBook: 3-step modal: Choose -> Schedule -> Checkout
 * - Local mock submit (replace submit handler with your API)
 * - Lightweight validation
 */

type QuickBookProps = {
  open: boolean;
  onClose: () => void;
  prefill?: { pujaSlug?: string };
};

type PujaChoice = {
  id: string;
  title: string;
  durationMin: number;
  basePrice: number;
  short: string;
};

const SAMPLE_PUJAS: PujaChoice[] = [
  {
    id: "home-simple",
    title: "Home Puja - Simple",
    durationMin: 30,
    basePrice: 499,
    short: "A short home puja with basic samagri.",
  },
  {
    id: "home-premium",
    title: "Home Puja - Premium",
    durationMin: 60,
    basePrice: 1199,
    short: "Premium pandit + samagri & recording.",
  },
  {
    id: "temple-beej",
    title: "Temple Puja - Beej",
    durationMin: 90,
    basePrice: 2499,
    short: "Full temple puja performed by certified pandit.",
  },
];

export const QuickBook: React.FC<QuickBookProps> = ({ open, onClose, prefill }) => {
  const modalRef = useRef<HTMLDivElement>(null);
  const [step, setStep] = useState(1);
  const [choiceId, setChoiceId] = useState<string | null>(prefill?.pujaSlug ?? SAMPLE_PUJAS[0].id);
  const [date, setDate] = useState<string>("");
  const [time, setTime] = useState<string>("");
  const [name, setName] = useState<string>("");
  const [mobile, setMobile] = useState<string>("");
  const [includeSamagri, setIncludeSamagri] = useState<boolean>(true);
  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState<{ id: string; message: string } | null>(null);

  useEffect(() => {
    if (open) {
      setStep(1);
      setSuccess(null);
      // Prefill with today's date
      const today = new Date();
      const formattedDate = today.toISOString().split('T')[0];
      setDate(formattedDate);
      
      // Prefill with a default time
      setTime("10:00");
    }
  }, [open]);

  useEffect(() => {
    // Clicking outside closes
    const handler = (e: MouseEvent) => {
      if (!modalRef.current) return;
      if (!modalRef.current.contains(e.target as Node)) onClose();
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, [onClose]);

  const selectedPuja = SAMPLE_PUJAS.find((p) => p.id === choiceId) ?? SAMPLE_PUJAS[0];

  function validateStep() {
    if (step === 1) return !!choiceId;
    if (step === 2) return !!date && !!time;
    if (step === 3) return name.trim().length > 1 && /^\+?\d{7,15}$/.test(mobile);
    return false;
  }

  async function submitBooking() {
    if (!validateStep()) return;
    setLoading(true);
    try {
      // Fake API - replace with real API call
      await new Promise((r) => setTimeout(r, 900));
      const bookingId = `NV-${Date.now().toString().slice(-6)}`;
      setSuccess({ id: bookingId, message: "Booking confirmed — pandit will call you shortly." });
      setStep(4);
    } catch (err) {
      alert("Something went wrong. Please try again.");
    } finally {
      setLoading(false);
    }
  }

  const price =
    selectedPuja.basePrice + (includeSamagri ? Math.round(selectedPuja.basePrice * 0.35) : 0);

  return (
    <AnimatePresence>
      {open && (
        <motion.div
          className="fixed inset-0 z-[9999] bg-black/40 backdrop-blur-sm flex items-end md:items-center justify-center p-4"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
        >
          <motion.div
            ref={modalRef}
            initial={{ y: "100%" }}
            animate={{ y: 0 }}
            exit={{ y: "100%" }}
            transition={{ type: "spring", stiffness: 300, damping: 30 }}
            className="w-full md:max-w-xl bg-white rounded-t-2xl md:rounded-2xl shadow-2xl overflow-hidden border-t-4 border-[#D4AF37]"
            role="dialog"
            aria-modal="true"
          >
            <div className="flex items-center justify-between p-4 border-b border-gray-100 bg-gray-50">
              <div>
                <h3 className="text-[#0E3F2E] font-bold text-xl">Quick Book</h3>
                <p className="text-xs text-gray-500">Fast 3-step booking flow</p>
              </div>
              <div className="flex items-center gap-2">
                <div className="text-sm font-bold text-[#0E3F2E] bg-[#D4AF37]/20 px-2 py-1 rounded-full">
                  Step {step <= 3 ? step : 3}/3
                </div>
                <button className="p-2 rounded-full hover:bg-gray-200 transition-colors" onClick={onClose} aria-label="Close">
                  <X className="h-5 w-5 text-gray-600" />
                </button>
              </div>
            </div>

            {/* Steps */}
            {step === 1 && (
              <div className="p-4">
                <div className="text-sm font-bold text-[#0E3F2E] mb-3 flex items-center gap-2">
                  <span className="bg-[#0E3F2E] text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">1</span>
                  Choose Puja
                </div>
                <div className="grid grid-cols-1 gap-3">
                  {SAMPLE_PUJAS.map((p) => (
                    <button
                      key={p.id}
                      onClick={() => setChoiceId(p.id)}
                      className={`w-full text-left p-4 rounded-xl border-2 transition-all duration-300 ${
                        choiceId === p.id
                          ? "border-[#D4AF37] bg-[#FEFBEA] shadow-md"
                          : "border-gray-200 hover:border-[#0E3F2E]/30 hover:shadow-sm"
                      }`}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <div className="font-bold text-[#0E3F2E]">{p.title}</div>
                          <div className="text-xs text-gray-600 mt-1">{p.short}</div>
                          <div className="flex items-center gap-2 mt-2">
                            <div className="flex items-center gap-1 text-xs text-gray-500">
                              <Clock className="h-3 w-3" />
                              <span>{p.durationMin} min</span>
                            </div>
                          </div>
                        </div>
                        <div className="text-lg font-bold text-[#0E3F2E]">₹{p.basePrice}</div>
                      </div>
                    </button>
                  ))}
                </div>

                <div className="mt-4 flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-xl">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={includeSamagri}
                      onChange={() => setIncludeSamagri((s) => !s)}
                      className="w-5 h-5 rounded text-[#0E3F2E] focus:ring-[#0E3F2E]"
                    />
                    <span className="text-sm font-medium">Include samagri (+35%)</span>
                  </label>
                  <div className="text-lg font-bold text-[#0E3F2E]">₹{price}</div>
                </div>

                <div className="mt-4 flex gap-3">
                  <button
                    onClick={() => setStep(2)}
                    disabled={!validateStep()}
                    className={`flex-1 py-3 rounded-xl text-white font-bold transition-all duration-300 ${
                      validateStep() ? "bg-[#0E3F2E] hover:bg-[#0C3726] shadow-md" : "bg-gray-300 cursor-not-allowed"
                    }`}
                  >
                    Continue
                  </button>
                  <button onClick={onClose} className="py-3 px-4 rounded-xl border-2 border-gray-300 font-bold hover:bg-gray-50 transition-colors">
                    Cancel
                  </button>
                </div>
              </div>
            )}

            {step === 2 && (
              <div className="p-4">
                <div className="text-sm font-bold text-[#0E3F2E] mb-3 flex items-center gap-2">
                  <span className="bg-[#0E3F2E] text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">2</span>
                  Schedule Puja
                </div>

                <div className="grid grid-cols-1 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-2">Select Date</label>
                    <div className="relative">
                      <Calendar className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input
                        type="date"
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                        min={new Date().toISOString().split('T')[0]}
                        className="w-full py-3 pl-10 pr-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/30 focus:border-[#0E3F2E]"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-2">Select Time</label>
                    <div className="relative">
                      <Clock className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input
                        type="time"
                        value={time}
                        onChange={(e) => setTime(e.target.value)}
                        className="w-full py-3 pl-10 pr-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/30 focus:border-[#0E3F2E]"
                      />
                    </div>
                  </div>
                </div>

                <div className="mt-6 flex gap-3">
                  <button
                    onClick={() => setStep(1)}
                    className="flex-1 py-3 rounded-xl border-2 border-gray-300 font-bold hover:bg-gray-50 transition-colors"
                  >
                    Back
                  </button>
                  <button
                    onClick={() => setStep(3)}
                    className={`flex-1 py-3 rounded-xl text-white font-bold transition-all duration-300 ${
                      date && time ? "bg-[#0E3F2E] hover:bg-[#0C3726] shadow-md" : "bg-gray-300 cursor-not-allowed"
                    }`}
                    disabled={!date || !time}
                  >
                    Continue
                  </button>
                </div>
              </div>
            )}

            {step === 3 && (
              <div className="p-4">
                <div className="text-sm font-bold text-[#0E3F2E] mb-3 flex items-center gap-2">
                  <span className="bg-[#0E3F2E] text-white rounded-full w-6 h-6 flex items-center justify-center text-xs">3</span>
                  Contact & Payment
                </div>

                <div className="grid grid-cols-1 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-2">Full Name</label>
                    <div className="relative">
                      <User className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                      <input
                        value={name}
                        onChange={(e) => setName(e.target.value)}
                        placeholder="Enter your full name"
                        className="w-full py-3 pl-10 pr-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/30 focus:border-[#0E3F2E]"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-xs font-bold text-gray-700 mb-2">Mobile Number</label>
                    <input
                      value={mobile}
                      onChange={(e) => setMobile(e.target.value)}
                      placeholder="+91 00000 00000"
                      className="w-full py-3 px-4 rounded-xl border-2 border-gray-200 focus:outline-none focus:ring-2 focus:ring-[#0E3F2E]/30 focus:border-[#0E3F2E]"
                    />
                  </div>
                </div>

                <div className="mt-4 border-2 border-gray-200 rounded-xl p-4 bg-gray-50">
                  <div className="flex items-center justify-between mb-3">
                    <div className="text-sm font-bold text-gray-700">Booking Summary</div>
                    <div className="text-sm font-bold text-[#0E3F2E]">{selectedPuja.title}</div>
                  </div>
                  <div className="flex items-center justify-between text-sm mb-2">
                    <div>Samagri</div>
                    <div className="font-medium">{includeSamagri ? "Included" : "Not included"}</div>
                  </div>
                  <div className="flex items-center justify-between text-lg font-bold text-[#0E3F2E] mt-3 pt-3 border-t border-gray-200">
                    <div>Total Amount</div>
                    <div>₹{price}</div>
                  </div>
                </div>

                <div className="mt-6 flex gap-3">
                  <button onClick={() => setStep(2)} className="flex-1 py-3 rounded-xl border-2 border-gray-300 font-bold hover:bg-gray-50 transition-colors">
                    Back
                  </button>
                  <button
                    onClick={submitBooking}
                    disabled={!validateStep() || loading}
                    className={`flex-1 py-3 rounded-xl text-white font-bold transition-all duration-300 ${
                      validateStep() ? "bg-[#D4AF37] text-[#0E3F2E] hover:bg-[#C9A02B] shadow-md" : "bg-gray-300 cursor-not-allowed"
                    }`}
                  >
                    {loading ? "Processing..." : `Pay & Book ₹${price}`}
                  </button>
                </div>
              </div>
            )}

            {step === 4 && success && (
              <div className="p-8 text-center">
                <div className="mb-4">
                  <CheckCircle className="h-16 w-16 text-[#0E3F2E] mx-auto" />
                </div>
                <div className="font-bold text-[#0E3F2E] text-2xl mb-2">Booking Confirmed!</div>
                <div className="text-gray-600 mb-6">{success.message}</div>
                <div className="text-xs text-gray-500 mb-6 bg-gray-100 inline-block px-3 py-1 rounded-full">
                  Booking ID: {success.id}
                </div>
                <div className="flex flex-col sm:flex-row gap-3">
                  <button
                    onClick={() => {
                      onClose();
                      setStep(1);
                    }}
                    className="flex-1 py-3 rounded-xl bg-[#0E3F2E] text-white font-bold hover:bg-[#0C3726] transition-colors shadow-md"
                  >
                    Done
                  </button>
                  <button
                    onClick={() => {
                      // For demonstration: go to bookings
                      window.location.href = "/account/bookings";
                    }}
                    className="flex-1 py-3 rounded-xl border-2 border-[#0E3F2E] text-[#0E3F2E] font-bold hover:bg-[#0E3F2E]/5 transition-colors"
                  >
                    View Bookings
                  </button>
                </div>
              </div>
            )}
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

export default QuickBook;