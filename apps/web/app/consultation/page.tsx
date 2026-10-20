import { Button } from "@/components/ui/button"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Calendar, Clock, BookOpen, Star } from "lucide-react"
import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"
import Link from "next/link"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { resolveContext } from "@/lib/context-resolver"

export const metadata = seoManager.generateMetadata({
  title: "Astrology Consultation & Vedic Guidance",
  description: "Get Vedic astrology guidance from Navdhya for kundli analysis, marriage compatibility, career questions, remedies, and auspicious timing support.",
  path: "/consultation",
  keywords: [
    "Vedic Astrology Delhi",
    "Kundli Analysis",
    "Marriage Compatibility",
    "Career Astrology",
    "Remedial Solutions",
    "Astrology Consultation",
  ],
})

const CONSULTATION_SCHEMA = seoManager.generateSchema({
  type: "Service",
  name: "Astrology Consultation",
  description: "Expert Vedic astrology guidance from certified pandits in Delhi",
  path: "/consultation",
})

const consultationTypes = [
  {
    id: "kundli",
    name: "Kundli Analysis",
    description: "Detailed analysis of your birth chart for life insights.",
    duration: "60-90 minutes",
    price: "Rs. 1,500",
  },
  {
    id: "compatibility",
    name: "Marriage Compatibility",
    description: "Review compatibility, family concerns, and long-term harmony.",
    duration: "90-120 minutes",
    price: "Rs. 2,500",
  },
  {
    id: "career",
    name: "Career Guidance",
    description: "Use your chart to think through career direction and timing.",
    duration: "60-90 minutes",
    price: "Rs. 1,800",
  },
  {
    id: "remedial",
    name: "Remedial Solutions",
    description: "Get practical remedies matched to your question and chart.",
    duration: "45-60 minutes",
    price: "Rs. 1,200",
  },
  {
    id: "muhurat",
    name: "Muhurat Selection",
    description: "Choose an auspicious date and time for important milestones.",
    duration: "30-45 minutes",
    price: "Rs. 1,000",
  },
]

const expertiseAreas = [
  "Career & Business",
  "Marriage & Relationships",
  "Health & Well-being",
  "Education & Learning",
  "Finance & Wealth",
  "Spiritual Growth",
  "Family Matters",
  "Legal Issues",
]

function ConsultationContent() {
  const consultationSchema = JSON.stringify(CONSULTATION_SCHEMA)

  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: consultationSchema }}
      />
      <main className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
        <div className="mb-12 text-center">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1.5 text-sm font-medium text-primary">
            <Star className="h-4 w-4" />
            <span>Expert Guidance</span>
          </div>
          <h1 className="mb-4 text-3xl font-bold tracking-tight md:text-4xl">
            Vedic <span className="text-primary">Astrology Consultation</span>
          </h1>
          <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
            Get clear, practical guidance from certified Vedic astrologers for relationships, career choices, remedies, and auspicious timing.
          </p>
          <p className="mx-auto mt-4 max-w-2xl text-sm text-muted-foreground">
            This page is best for kundli analysis, compatibility reviews, remedies, and muhurat advice. If you already know the ritual you need, go straight to{' '}
            <Link href="/book" className="text-primary hover:underline">
              book a puja
            </Link>
            . If you want to understand the process first, read{' '}
            <Link href="/explore/how-navdhya-works" className="text-primary hover:underline">
              how Navdhya works
            </Link>
            .
          </p>
        </div>

        <div className="grid gap-8 md:grid-cols-3">
          <div className="md:col-span-2">
            <div className="rounded-xl border border-border bg-card p-6 md:p-8">
              <form className="space-y-6">
                <div>
                  <h2 className="mb-4 text-xl font-semibold">Personal Information</h2>
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="name">Full Name *</Label>
                      <Input id="name" placeholder="Enter your full name" required />
                    </div>
                    <div>
                      <Label htmlFor="phone">Phone Number *</Label>
                      <div className="relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                          <span className="text-sm text-muted-foreground">+91</span>
                        </div>
                        <Input id="phone" placeholder="Enter 10-digit number" className="pl-12" required />
                      </div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <Label htmlFor="email">Email Address</Label>
                    <Input id="email" type="email" placeholder="Enter your email" />
                  </div>
                </div>

                <div>
                  <h2 className="mb-4 text-xl font-semibold">Birth Details</h2>
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="date-of-birth">Date of Birth *</Label>
                      <div className="relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input id="date-of-birth" type="date" className="pl-10" required />
                      </div>
                    </div>
                    <div>
                      <Label htmlFor="time-of-birth">Time of Birth</Label>
                      <div className="relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                          <Clock className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input id="time-of-birth" type="time" className="pl-10" />
                      </div>
                    </div>
                  </div>
                  <div className="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="place-of-birth">Place of Birth</Label>
                      <Input id="place-of-birth" placeholder="City, State" />
                    </div>
                    <div>
                      <Label htmlFor="gender">Gender</Label>
                      <Select>
                        <SelectTrigger id="gender">
                          <SelectValue placeholder="Select gender" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="male">Male</SelectItem>
                          <SelectItem value="female">Female</SelectItem>
                          <SelectItem value="other">Other</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  </div>
                </div>

                <div>
                  <h2 className="mb-4 text-xl font-semibold">Consultation Details</h2>
                  <div className="grid gap-4 md:grid-cols-2">
                    <div>
                      <Label htmlFor="consultation-type">Consultation Type *</Label>
                      <Select>
                        <SelectTrigger id="consultation-type">
                          <SelectValue placeholder="Select consultation type" />
                        </SelectTrigger>
                        <SelectContent>
                          {consultationTypes.map((type) => (
                            <SelectItem key={type.id} value={type.id}>
                              {type.name} ({type.price})
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                    <div>
                      <Label htmlFor="preferred-date">Preferred Date *</Label>
                      <div className="relative">
                        <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                          <Calendar className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <Input id="preferred-date" type="date" className="pl-10" required />
                      </div>
                    </div>
                  </div>

                  <div className="mt-4">
                    <Label htmlFor="expertise-area">Area of Expertise</Label>
                    <Select>
                      <SelectTrigger id="expertise-area">
                        <SelectValue placeholder="Select area of expertise" />
                      </SelectTrigger>
                      <SelectContent>
                        {expertiseAreas.map((area) => (
                          <SelectItem key={area} value={area}>
                            {area}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="mt-4">
                    <Label htmlFor="questions">Questions or Concerns</Label>
                    <Textarea
                      id="questions"
                      placeholder="Please share the questions you want covered during the consultation."
                      rows={4}
                    />
                  </div>
                </div>

                <div className="pt-4">
                  <Button type="submit" className="w-full bg-primary py-6 text-primary-foreground hover:bg-primary/90">
                    Submit Consultation Request
                  </Button>
                </div>
              </form>
            </div>
          </div>

          <div>
            <div className="sticky top-6 rounded-xl border border-border bg-card p-6">
              <h2 className="mb-4 text-xl font-semibold">Consultation Types</h2>

              <div className="mb-6 space-y-4">
                {consultationTypes.map((type) => (
                  <div key={type.id} className="rounded-lg border border-border p-4">
                    <div className="mb-2 flex items-start justify-between gap-3">
                      <h3 className="font-medium">{type.name}</h3>
                      <span className="font-bold text-primary">{type.price}</span>
                    </div>
                    <p className="mb-2 text-sm text-muted-foreground">{type.description}</p>
                    <p className="text-xs text-muted-foreground">{type.duration}</p>
                  </div>
                ))}
              </div>

              <div className="rounded-lg bg-primary/5 p-4">
                <h3 className="mb-2 flex items-center gap-2 font-medium">
                  <BookOpen className="h-4 w-4 text-primary" />
                  What to Expect
                </h3>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li>Certified Vedic astrologer guidance</li>
                  <li>Practical next steps and remedies</li>
                  <li>Written follow-up or summary when needed</li>
                  <li>Support if you want to continue to booking</li>
                </ul>
              </div>

              <div className="mt-6 border-t border-border pt-6">
                <h3 className="mb-3 font-medium">Need Help?</h3>
                <p className="mb-4 text-sm text-muted-foreground">
                  Our team can help you choose the right consultation or move you directly to a puja booking.
                </p>
                <div className="space-y-3">
                  <Button asChild variant="outline" className="w-full">
                    <Link href="/contact">Contact Us</Link>
                  </Button>
                  <Button asChild variant="ghost" className="w-full">
                    <Link href="/book">Book a Puja</Link>
                  </Button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </>
  )
}

export default async function ConsultationPage() {
  const context = await resolveContext("/consultation")

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <ConsultationContent />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
