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
        <div className="text-center mb-10">
          <h2 id="booking-title" className="font-serif text-3xl md:text-4xl font-bold text-foreground">
            Get Instant Quote & Confirm Booking
          </h2>
          <p className="text-muted-foreground mt-3 max-w-2xl mx-auto">
            Fill out the form below to receive an instant quote and secure your puja booking with our certified pandits.
          </p>
        </div>
        
        <div className="grid gap-8 lg:grid-cols-2">
          <div className="bg-card rounded-xl border p-6 md:p-8 shadow-sm">
            <form onSubmit={onSubmit} className="space-y-6" noValidate>
              <div className="space-y-2">
                <Label htmlFor="puja" className="text-base font-medium">Puja Type</Label>
                <Select onValueChange={setPujaType}>
                  <SelectTrigger id="puja" aria-invalid={!!errors.puja} className="h-12">
                    <SelectValue placeholder="Select puja type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="grihapravesh">Grihapravesh</SelectItem>
                    <SelectItem value="satyanarayan">Satyanarayan Puja</SelectItem>
                    <SelectItem value="wedding">Wedding</SelectItem>
                    <SelectItem value="shraddh">Shraddh</SelectItem>
                  </SelectContent>
                </Select>
                {errors.puja ? <p className="mt-1 text-sm text-destructive">{errors.puja}</p> : null}
              </div>
              
              <div className="grid gap-6 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="date" className="text-base font-medium">Preferred Date</Label>
                  <Input
                    id="date"
                    type="date"
                    required
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                    aria-invalid={!!errors.date}
                    className="h-12"
                  />
                  {errors.date ? <p className="mt-1 text-sm text-destructive">{errors.date}</p> : null}
                </div>
                <div className="space-y-2">
                  <Label htmlFor="pincode" className="text-base font-medium">Pin Code</Label>
                  <Input
                    id="pincode"
                    inputMode="numeric"
                    placeholder="110001"
                    required
                    value={pin}
                    onChange={(e) => setPin(e.target.value.replace(/\D/g, "").slice(0, 6))}
                    aria-invalid={!!errors.pin}
                    className="h-12"
                  />
                  <p className="text-sm text-muted-foreground">We use your PIN to estimate travel cost (if any).</p>
                  {errors.pin ? <p className="mt-1 text-sm text-destructive">{errors.pin}</p> : null}
                </div>
              </div>
              
              <div className="grid gap-6 sm:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name" className="text-base font-medium">Full Name</Label>
                  <Input id="name" required className="h-12" />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="phone" className="text-base font-medium">Phone</Label>
                  <Input id="phone" type="tel" required placeholder="+91 XXXXX XXXXX" className="h-12" />
                </div>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="email" className="text-base font-medium">Email</Label>
                <Input id="email" type="email" required className="h-12" />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="notes" className="text-base font-medium">Special Requirements</Label>
                <Textarea id="notes" placeholder="Any preferences or details..." className="min-h-[120px]" />
              </div>
              
              <Button 
                type="submit" 
                className={cn("w-full py-6 text-base font-medium transition-all", submitted ? "bg-green-600 hover:bg-green-700" : "bg-primary hover:bg-primary/90")}
                disabled={submitted}
              >
                {submitted ? (
                  <span className="flex items-center justify-center">
                    <CheckCircle className="w-5 h-5 mr-2" />
                    Booking Confirmed!
                  </span>
                ) : (
                  "Get Instant Quote & Book Now"
                )}
              </Button>
            </form>
          </div>

          <div className="bg-muted/50 rounded-xl border p-6 md:p-8 h-fit">
            <h3 className="font-serif text-2xl font-bold mb-6">Estimated Quote</h3>
            <p className="text-muted-foreground mb-6">
              Select a puja and enter your PIN to view an instant estimate.
            </p>
            
            <div className="rounded-lg border bg-card p-6 mb-8">
              <div className="flex items-center justify-between mb-2">
                <span className="text-base font-medium">Estimated Total</span>
                <span className="text-2xl font-bold text-primary">₹{estimate.toLocaleString("en-IN")}</span>
              </div>
              <p className="text-sm text-muted-foreground">Final quote may vary based on specifics and samagri.</p>
            </div>
            
            <h3 className="font-serif text-2xl font-bold mb-6">Booking Guarantee</h3>
            <ul className="space-y-3 mb-8">
              <li className="flex items-start">
                <CheckCircle className="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground">Certified Vedic Scholars</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground">Complete Samagri Included</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground">Transparent Pricing</span>
              </li>
              <li className="flex items-start">
                <CheckCircle className="w-5 h-5 text-green-600 mr-3 mt-0.5 flex-shrink-0" />
                <span className="text-muted-foreground">Same-Day Booking (if available)</span>
              </li>
            </ul>
            
            <div className="space-y-4">
              <a 
                className="flex items-center justify-center gap-2 w-full py-3 px-4 bg-white border border-gray-300 rounded-lg text-foreground hover:bg-gray-50 transition-colors"
                href="tel:+910000000000"
              >
                <Phone className="w-5 h-5" />
                <span>Call Us Now</span>
              </a>
              <a
                className="flex items-center justify-center gap-2 w-full py-3 px-4 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                href="https://wa.me/910000000000"
                target="_blank"
                rel="noopener noreferrer"
              >
                <MessageCircle className="w-5 h-5" />
                <span>WhatsApp Quick Connect</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}