"use client"

import { useMemo, useState } from "react"
import type React from "react"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { cn } from "@/lib/utils"
import { CheckCircle, Phone, MessageCircle } from "lucide-react"

export function Booking() {
  const [submitted, setSubmitted] = useState(false)
  const [form, setForm] = useState({
    pujaType: "",
    pin: "",
    date: "",
    name: "",
    phone: "",
    email: "",
    notes: "",
  })
  const [errors, setErrors] = useState<Record<string, string>>({})

  const estimate = useMemo(() => {
    const base: Record<string, number> = {
      grihapravesh: 4999,
      satyanarayan: 3999,
      wedding: 24999,
      shraddh: 5999,
    }
    const basePrice = base[form.pujaType] ?? 0
    const travel = form.pin.length === 6 ? 250 : 0
    return basePrice + travel
  }, [form.pujaType, form.pin])

  const getPujaTypeName = (value: string) =>
    ({
      grihapravesh: "Grihapravesh",
      satyanarayan: "Satyanarayan Puja",
      wedding: "Wedding",
      shraddh: "Shraddh",
    }[value] || value)

  const handleChange = (key: string, value: string) => {
    setForm((prev) => ({ ...prev, [key]: value }))
  }

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const nextErrors: Record<string, string> = {}
    if (!form.pujaType) nextErrors.pujaType = "Please select a puja type"
    if (!form.date) nextErrors.date = "Please select a preferred date"
    if (!/^\d{6}$/.test(form.pin)) nextErrors.pin = "Enter a valid 6-digit PIN code"
    if (!form.name) nextErrors.name = "Please enter your full name"
    if (!form.phone) nextErrors.phone = "Please enter your phone number"
    if (!form.email) nextErrors.email = "Please enter your email"
    setErrors(nextErrors)

    if (Object.keys(nextErrors).length === 0) {
      const message = `*New Puja Booking Request*\n\n*Puja Type:* ${getPujaTypeName(form.pujaType)}\n*Preferred Date:* ${form.date}\n*Pin Code:* ${form.pin}\n*Full Name:* ${form.name}\n*Phone:* ${form.phone}\n*Email:* ${form.email}\n*Special Requirements:* ${form.notes || "None"}\n\n*Estimated Total:* ₹${estimate.toLocaleString("en-IN")}\n\nPlease confirm availability and next steps.`
      const encodedMessage = encodeURIComponent(message)
      const whatsappUrl = `https://wa.me/910000000000?text=${encodedMessage}`
      window.open(whatsappUrl, "_blank")
      setSubmitted(true)
    }
  }

  return (
    <section id="booking" aria-labelledby="booking-title" className="bg-background py-16 px-4">
      <div className="mx-auto max-w-6xl">
        <header className="text-center mb-10">
          <p className="uppercase tracking-wider text-xs text-muted-foreground font-medium mb-2">
            Sacred Booking • Simple & Hassle-Free
          </p>
          <h2 id="booking-title" className="text-3xl md:text-4xl font-serif font-bold text-foreground">
            Get Instant Quote & Confirm Booking
          </h2>
          <p className="text-sm text-muted-foreground mt-2 max-w-2xl mx-auto">
            Fill out the form below to receive an instant quote and secure your puja booking with our certified pandits.
          </p>
        </header>

        <div className="grid lg:grid-cols-2 gap-8">
          {/* LEFT — FORM */}
          <form
            onSubmit={onSubmit}
            noValidate
            className="bg-card rounded-2xl border p-6 shadow-sm space-y-5 transition-all duration-300 hover:shadow-md"
          >
            <div>
              <Label htmlFor="pujaType">Puja Type</Label>
              <Select onValueChange={(v) => handleChange("pujaType", v)} value={form.pujaType}>
                <SelectTrigger id="pujaType" className="h-10 mt-1">
                  <SelectValue placeholder="Select puja type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="grihapravesh">Grihapravesh</SelectItem>
                  <SelectItem value="satyanarayan">Satyanarayan Puja</SelectItem>
                  <SelectItem value="wedding">Wedding</SelectItem>
                  <SelectItem value="shraddh">Shraddh</SelectItem>
                </SelectContent>
              </Select>
              {errors.pujaType && <p className="text-xs text-destructive mt-1">{errors.pujaType}</p>}
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="date">Preferred Date</Label>
                <Input
                  id="date"
                  type="date"
                  className="h-10 mt-1"
                  value={form.date}
                  onChange={(e) => handleChange("date", e.target.value)}
                />
                {errors.date && <p className="text-xs text-destructive mt-1">{errors.date}</p>}
              </div>
              <div>
                <Label htmlFor="pin">Pin Code</Label>
                <Input
                  id="pin"
                  inputMode="numeric"
                  maxLength={6}
                  placeholder="110001"
                  className="h-10 mt-1"
                  value={form.pin}
                  onChange={(e) => handleChange("pin", e.target.value.replace(/\D/g, "").slice(0, 6))}
                />
                {errors.pin ? (
                  <p className="text-xs text-destructive mt-1">{errors.pin}</p>
                ) : (
                  <p className="text-xs text-muted-foreground">Used to estimate travel cost (if any).</p>
                )}
              </div>
            </div>

            <div className="grid sm:grid-cols-2 gap-4">
              <div>
                <Label htmlFor="name">Full Name</Label>
                <Input
                  id="name"
                  className="h-10 mt-1"
                  value={form.name}
                  onChange={(e) => handleChange("name", e.target.value)}
                />
                {errors.name && <p className="text-xs text-destructive mt-1">{errors.name}</p>}
              </div>
              <div>
                <Label htmlFor="phone">Phone</Label>
                <Input
                  id="phone"
                  type="tel"
                  placeholder="+91 XXXXX XXXXX"
                  className="h-10 mt-1"
                  value={form.phone}
                  onChange={(e) => handleChange("phone", e.target.value)}
                />
                {errors.phone && <p className="text-xs text-destructive mt-1">{errors.phone}</p>}
              </div>
            </div>

            <div>
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                className="h-10 mt-1"
                value={form.email}
                onChange={(e) => handleChange("email", e.target.value)}
              />
              {errors.email && <p className="text-xs text-destructive mt-1">{errors.email}</p>}
            </div>

            <div>
              <Label htmlFor="notes">Special Requirements</Label>
              <Textarea
                id="notes"
                placeholder="Any preferences or details..."
                className="min-h-[100px] text-sm mt-1"
                value={form.notes}
                onChange={(e) => handleChange("notes", e.target.value)}
              />
            </div>

            <Button
              type="submit"
              disabled={submitted}
              className={cn(
                "w-full py-5 text-sm font-medium rounded-lg transition-all",
                submitted
                  ? "bg-green-600 hover:bg-green-700"
                  : "bg-[#0E3F2E] hover:bg-[#0E3F2E]/90 shadow-sm"
              )}
            >
              {submitted ? (
                <span className="flex items-center justify-center">
                  <CheckCircle className="w-4 h-4 mr-2 animate-pulse" />
                  Booking Confirmed
                </span>
              ) : (
                "Get Instant Quote & Book Now"
              )}
            </Button>
          </form>

          {/* RIGHT — SUMMARY */}
          <aside className="bg-muted/30 rounded-2xl border p-6 h-fit space-y-6">
            <div>
              <h3 className="text-xl font-serif font-semibold text-foreground mb-2">Estimated Quote</h3>
              <p className="text-xs uppercase tracking-wider text-muted-foreground">
                Instant Estimate • Real-Time Pricing
              </p>
            </div>

            {form.pujaType ? (
              <div className="rounded-lg border bg-card p-5 mb-6">
                <div className="space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span>Puja Type</span>
                    <span>{getPujaTypeName(form.pujaType)}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Base Price</span>
                    <span>₹{estimate.toLocaleString("en-IN")}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Travel Cost</span>
                    <span>₹{form.pin.length === 6 ? "250" : "0"}</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Samagri</span>
                    <span>Included</span>
                  </div>
                  <div className="flex justify-between">
                    <span>Pandit Service</span>
                    <span>Included</span>
                  </div>
                  <hr className="my-3 border-gray-200" />
                  <div className="flex justify-between font-semibold">
                    <span>Estimated Total</span>
                    <span className="text-lg text-primary">₹{estimate.toLocaleString("en-IN")}</span>
                  </div>
                </div>
                <p className="text-xs text-muted-foreground mt-3">
                  Final quote may vary based on specifics and samagri.
                </p>
              </div>
            ) : (
              <p className="text-muted-foreground text-sm">
                Select a puja and enter your PIN to view an instant estimate.
              </p>
            )}

            <div>
              <h3 className="text-xl font-serif font-semibold mb-2">Booking Guarantee</h3>
              <ul className="space-y-2 text-sm text-muted-foreground">
                {["Certified Vedic Scholars", "Complete Samagri Included", "Transparent Pricing", "Same-Day Booking (if available)"].map(
                  (item) => (
                    <li key={item} className="flex items-center gap-2">
                      <CheckCircle className="w-4 h-4 text-green-600 flex-shrink-0" />
                      {item}
                    </li>
                  )
                )}
              </ul>
            </div>

            <div className="space-y-3">
              <a
                className="flex items-center justify-center gap-2 w-full py-2.5 bg-white border rounded-lg hover:bg-gray-50 transition"
                href="tel:+910000000000"
              >
                <Phone className="w-4 h-4" />
                Call Us
              </a>
              <a
                className="flex items-center justify-center gap-2 w-full py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                href="https://wa.me/910000000000"
                target="_blank"
                rel="noopener noreferrer"
              >
                <MessageCircle className="w-4 h-4" />
                WhatsApp Quick Connect
              </a>
            </div>
          </aside>
        </div>
      </div>
    </section>
  )
}
