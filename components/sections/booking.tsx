"use client"
import { useMemo, useState } from "react"
import type React from "react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Label } from "@/components/ui/label"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { cn } from "@/lib/utils"

export function Booking() {
  const [submitted, setSubmitted] = useState(false)
  const [pujaType, setPujaType] = useState<string>("")
  const [pin, setPin] = useState("")
  const [date, setDate] = useState("")
  const [errors, setErrors] = useState<Record<string, string>>({})

  const estimate = useMemo(() => {
    const base: Record<string, number> = {
      grihapravesh: 4999,
      satyanarayan: 3999,
      wedding: 24999,
      shraddh: 5999,
    }
    const b = base[pujaType] ?? 0
    const travel = pin.length === 6 ? 250 : 0
    return b + travel
  }, [pujaType, pin])

  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    const nextErrors: Record<string, string> = {}
    if (!pujaType) nextErrors.puja = "Please select a puja type"
    if (!date) nextErrors.date = "Please select a preferred date"
    if (!/^\d{6}$/.test(pin)) nextErrors.pin = "Enter a valid 6-digit PIN code"
    setErrors(nextErrors)
    if (Object.keys(nextErrors).length === 0) setSubmitted(true)
  }

  return (
    <section id="booking" aria-labelledby="booking-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12 grid gap-8 md:grid-cols-[1fr_360px]">
        <div>
          <h2 id="booking-title" className="font-serif text-2xl md:text-3xl">
            Get Instant Quote & Confirm Booking
          </h2>
          <form onSubmit={onSubmit} className="mt-6 space-y-4" noValidate>
            <div>
              <Label htmlFor="puja">Puja Type</Label>
              <Select onValueChange={setPujaType}>
                <SelectTrigger id="puja" aria-invalid={!!errors.puja}>
                  <SelectValue placeholder="Select puja type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="grihapravesh">Grihapravesh</SelectItem>
                  <SelectItem value="satyanarayan">Satyanarayan Puja</SelectItem>
                  <SelectItem value="wedding">Wedding</SelectItem>
                  <SelectItem value="shraddh">Shraddh</SelectItem>
                </SelectContent>
              </Select>
              {errors.puja ? <p className="mt-1 text-xs text-destructive">{errors.puja}</p> : null}
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <Label htmlFor="date">Preferred Date</Label>
                <Input
                  id="date"
                  type="date"
                  required
                  value={date}
                  onChange={(e) => setDate(e.target.value)}
                  aria-invalid={!!errors.date}
                />
                {errors.date ? <p className="mt-1 text-xs text-destructive">{errors.date}</p> : null}
              </div>
              <div>
                <Label htmlFor="pincode">Pin Code</Label>
                <Input
                  id="pincode"
                  inputMode="numeric"
                  placeholder="110001"
                  required
                  value={pin}
                  onChange={(e) => setPin(e.target.value.replace(/\D/g, "").slice(0, 6))}
                  aria-invalid={!!errors.pin}
                />
                <p className="mt-1 text-xs text-muted-foreground">We use your PIN to estimate travel cost (if any).</p>
                {errors.pin ? <p className="mt-1 text-xs text-destructive">{errors.pin}</p> : null}
              </div>
            </div>
            <div className="grid gap-4 md:grid-cols-2">
              <div>
                <Label htmlFor="name">Full Name</Label>
                <Input id="name" required />
              </div>
              <div>
                <Label htmlFor="phone">Phone</Label>
                <Input id="phone" type="tel" required placeholder="+91 XXXXX XXXXX" />
              </div>
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input id="email" type="email" required />
            </div>
            <div>
              <Label htmlFor="notes">Special Requirements</Label>
              <Textarea id="notes" placeholder="Any preferences or details..." />
            </div>
            <Button type="submit" className={cn("bg-primary text-primary-foreground w-full md:w-auto")}>
              {submitted ? "Submitted" : "Get Quote"}
            </Button>
          </form>
        </div>

        <aside className="bg-muted/50 rounded-lg border p-5 h-fit">
          <h3 className="font-serif text-xl">Estimated Quote</h3>
          <p className="mt-2 text-sm text-muted-foreground">
            Select a puja and enter your PIN to view an instant estimate.
          </p>
          <div className="mt-4 rounded-md border bg-card p-4">
            <div className="flex items-center justify-between">
              <span className="text-sm">Estimated Total</span>
              <span className="text-lg font-semibold">₹{estimate.toLocaleString("en-IN")}</span>
            </div>
            <p className="mt-1 text-xs text-muted-foreground">Final quote may vary based on specifics and samagri.</p>
          </div>
          <h3 className="font-serif text-xl mt-6">Booking Guarantee</h3>
          <ul className="list-disc list-inside text-sm text-muted-foreground mt-3 space-y-1">
            <li>Certified Vedic Scholars</li>
            <li>Complete Samagri Included</li>
            <li>Transparent Pricing</li>
            <li>Same-Day Booking (if available)</li>
          </ul>
          <div className="mt-4 space-y-2">
            <a className="block text-sm underline hover:text-primary" href="tel:+910000000000">
              Call Us Now
            </a>
            <a
              className="block text-sm underline hover:text-primary"
              href="https://wa.me/910000000000"
              target="_blank"
              rel="noopener noreferrer"
            >
              WhatsApp Quick Connect
            </a>
          </div>
        </aside>
      </div>
    </section>
  )
}
