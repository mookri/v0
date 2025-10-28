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
      <div className="mx-auto max-w-7xl px-4 py-12 overflow-x-hidden w-full">
        <div className="pb-4 text-center">
          <div className="inline-block">
            <p style={{
              color: '#6B7280',
              letterSpacing: '1px',
              lineHeight: '12px',
              textTransform: 'uppercase',
              fontWeight: 500,
              fontSize: '10px',
              marginBottom: '12px',
              fontFamily: "'DM Sans', sans-serif"
            }}
            className="text-center"
            >
              Sacred Booking - Simple & Hassle-Free
            </p>
            <h2 
              id="booking-title"
              className="font-serif text-2xl md:text-3xl lg:text-3xl font-bold tracking-tight text-center mb-3 relative inline-block"
              style={{ color: '#111827' }}
            >
              Get Instant Quote & Confirm Booking
            </h2>
          </div>
        </div>
        <p className="text-muted-foreground mt-3 max-w-2xl mx-auto text-center text-sm">
          Fill out the form below to receive an instant quote and secure your puja booking with our certified pandits.
        </p>
        
        <div className="grid gap-6 lg:grid-cols-2 mt-8">
          <div className="bg-card rounded-lg border p-5 shadow-sm">
            <form onSubmit={onSubmit} className="space-y-4" noValidate>
              <div className="space-y-2">
                <Label htmlFor="puja" className="text-sm font-medium">Puja Type</Label>
                <Select onValueChange={setPujaType}>
                  <SelectTrigger id="puja" aria-invalid={!!errors.puja} className="h-10">
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
              
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="date" className="text-sm font-medium">Preferred Date</Label>
                  <Input
                    id="date"
                    type="date"
                    required
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                    aria-invalid={!!errors.date}
                    className="h-10"
                  />
                  {errors.date ? <p className="mt-1 text-xs text-destructive">{errors.date}</p> : null}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pincode" className="text-sm font-medium">Pin Code</Label>
                  <Input
                    id="pincode"
                    inputMode="numeric"
                    placeholder="110001"
                    required
                    value={pin}
                    onChange={(e) => setPin(e.target.value.replace(/\D/g, "").slice(0, 6))}
                    aria-invalid={!!errors.pin}
                    className="h-10"
                  />
                  <p className="text-xs text-muted-foreground">We use your PIN to estimate travel cost (if any).</p>
                  {errors.pin ? <p className="mt-1 text-xs text-destructive">{errors.pin}</p> : null}
                </div>
              </div>
              
              <div className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name" className="text-sm font-medium">Full Name</Label>
                  <Input id="name" required className="h-10" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="phone" className="text-sm font-medium">Phone</Label>
                  <Input id="phone" type="tel" required placeholder="+91 XXXXX XXXXX" className="h-10" />
                </div>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="email" className="text-sm font-medium">Email</Label>
                <Input id="email" type="email" required className="h-10" />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="notes" className="text-sm font-medium">Special Requirements</Label>
                <Textarea id="notes" placeholder="Any preferences or details..." className="min-h-[100px] text-sm" />
              </div>
              
              <Button 
                type="submit" 
                className={cn("w-full py-5 text-sm font-medium transition-all rounded-lg", submitted ? "bg-green-600 hover:bg-green-700" : "bg-[#0E3F2E] hover:bg-[#0E3F2E]/90")}
                disabled={submitted}
              >
                {submitted ? (
                  <span className="flex items-center justify-center">
                    <CheckCircle className="w-4 h-4 mr-2" />
                    Booking Confirmed!
                  </span>
                ) : (
                  "Get Instant Quote & Book Now"
                )}
              </Button>
            </form>
          </div>

          <div className="bg-muted/30 rounded-lg border p-5 h-fit">
            <div className="pb-4">
              <div className="inline-block">
                <p style={{
                  color: '#6B7280',
                  letterSpacing: '1px',
                  lineHeight: '12px',
                  textTransform: 'uppercase',
                  fontWeight: 500,
                  fontSize: '10px',
                  marginBottom: '12px',
                  fontFamily: "'DM Sans', sans-serif"
                }}
                className="text-left"
                >
                  Instant Estimate - Real-Time Pricing
                </p>
                <h3 
                  className="font-serif text-xl md:text-2xl font-bold tracking-tight text-left mb-3 relative inline-block"
                  style={{ color: '#111827' }}
                >
                  Estimated Quote
                </h3>
              </div>
            </div>
            <p className="text-muted-foreground mb-5 text-sm">
              Select a puja and enter your PIN to view an instant estimate.
            </p>
            
            <div className="rounded-lg border bg-card p-5 mb-6">
              <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-medium">Estimated Total</span>
                <span className="text-xl font-bold text-primary">₹{estimate.toLocaleString("en-IN")}</span>
              </div>
              <p className="text-xs text-muted-foreground">Final quote may vary based on specifics and samagri.</p>
            </div>
            
            <div className="pb-4">
              <div className="inline-block">
                <p style={{
                  color: '#6B7280',
                  letterSpacing: '1px',
                  lineHeight: '12px',
                  textTransform: 'uppercase',
                  fontWeight: 500,
                  fontSize: '10px',
                  marginBottom: '12px',
                  fontFamily: "'DM Sans', sans-serif"
                }}
                className="text-left"
                >
                  Our Sacred Promise
                </p>
                <h3 
                  className="font-serif text-xl md:text-2xl font-bold tracking-tight text-left mb-3 relative inline-block"
                  style={{ color: '#111827' }}
                >
                  Booking Guarantee
                </h3>
              </div>
            </div>
            <ul className="space-y-3 mb-6">
              <li className="flex items-start">
                <CheckCircle className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground text-sm">Certified Vedic Scholars</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground text-sm">Complete Samagri Included</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground text-sm">Transparent Pricing</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-4 h-4 text-green-600 mr-2 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground text-sm">Same-Day Booking (if available)</span>
              </li>
            </ul>
            
            <div className="space-y-3">
              <a 
                className="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-white border border-gray-300 rounded-lg text-foreground hover:bg-gray-50 transition-colors text-sm"
                href="tel:+910000000000"
              >
                <Phone className="w-4 h-4" />
                <span>Call Us Now</span>
              </a>
              <a
                className="flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm"
                href="https://wa.me/910000000000"
                target="_blank"
                rel="noopener noreferrer"
              >
                <MessageCircle className="w-4 h-4" />
                <span>WhatsApp Quick Connect</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}