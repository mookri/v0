import Image from "next/image"
import Link from "next/link"
import { notFound } from "next/navigation"
import { Button } from "@/components/ui/button"

const DATA: Record<
  string,
  { title: string; description: string; price: string; imgQuery: string; highlights: string[] }
> = {
  "griha-pravesh": {
    title: "Griha Pravesh Puja",
    description:
      "An auspicious ceremony performed before entering a new home to invoke blessings, prosperity, and harmony. Conducted by certified Pandits with proper Vidhi and Muhurat guidance.",
    price: "₹2,500 – ₹5,500",
    imgQuery: "griha%20pravesh%20puja%20mandap%20home%20entrance",
    highlights: ["Muhurat selection guidance", "All samagri support", "On-time service", "Post-puja clean-up"],
  },
  "satyanarayan-katha": {
    title: "Satyanarayan Puja",
    description:
      "A devotional ritual dedicated to Lord Vishnu for prosperity, peace, and fulfillment of wishes. Includes Katha, Aarti, and Prasad distribution.",
    price: "₹2,000 – ₹4,500",
    imgQuery: "satyanarayan%20katha%20devotional%20ritual",
    highlights: ["Professional Pandit", "Bhog & Aarti", "Simple setup", "Transparent pricing"],
  },
  "ganesh-puja": {
    title: "Ganesh Puja",
    description:
      "Performed to seek blessings from Lord Ganesh for removal of obstacles and success. Ideal before new beginnings like business openings, exams, or housewarming.",
    price: "₹1,800 – ₹3,800",
    imgQuery: "ganesh%20puja%20traditional%20setup",
    highlights: ["Clean, quick setup", "Proper mantra chanting", "Auspicious timing", "Guided Aarti"],
  },
  rudrabhishek: {
    title: "Rudrabhishek",
    description:
      "A powerful abhishek of Lord Shiva performed to bring peace, protection, and prosperity. Includes Rudra chanting and Abhishekam with sacred offerings.",
    price: "₹3,000 – ₹7,000",
    imgQuery: "rudrabhishek%20temple%20abhishek%20shivling",
    highlights: ["Experienced Pandits", "Samagri covered", "Pure ingredients", "Detailed Vidhi"],
  },
  shraddh: {
    title: "Shraddh Ceremony",
    description: "Honor your ancestors with authentic rituals performed as per Vedic customs and regional traditions.",
    price: "₹5,000 – ₹10,000",
    imgQuery: "shraddh%20ceremony%20traditional%20ritual",
    highlights: ["Pitr Tarpan", "Qualified priests", "Guidance and support", "Respectful conduct"],
  },
  "hindu-wedding": {
    title: "Hindu Wedding Ceremony",
    description:
      "Complete traditional wedding rituals with Vedic mantras, mandap, and proper vidhi performed by experienced Pandits.",
    price: "₹24,999 – ₹75,000",
    imgQuery: "hindu%20wedding%20mandap%20ritual%20sacred%20fire",
    highlights: ["Muhurat selection", "Full ceremony flow", "Mantra guidance", "Professional setup"],
  },
}

export default function PujaDetailsPage({ params }: { params: { slug: string } }) {
  const puja = DATA[params.slug]
  if (!puja) return notFound()

  return (
    <main className="mx-auto w-full max-w-5xl px-4 py-8 sm:py-12">
      <div className="grid gap-6 sm:grid-cols-2">
        <div className="relative h-56 w-full overflow-hidden rounded-md sm:h-80">
          <Image
            src={`/.jpg?height=640&width=960&query=${puja.imgQuery}`}
            alt={`${puja.title} visual`}
            fill
            className="object-cover"
            sizes="(max-width: 640px) 100vw, 50vw"
            priority
          />
        </div>

        <div>
          <h1 className="text-pretty text-2xl font-semibold sm:text-3xl">{puja.title}</h1>
          <p className="mt-2 text-muted-foreground">{puja.description}</p>
          <p className="mt-3 text-sm font-medium">Typical range: {puja.price}</p>

          <ul className="mt-4 grid list-disc gap-2 pl-5 text-sm marker:text-primary">
            {puja.highlights.map((h) => (
              <li key={h}>{h}</li>
            ))}
          </ul>

          <div className="mt-6 flex flex-col gap-2 sm:flex-row">
            <a href="/#booking" className="sm:flex-1">
              <Button className="w-full bg-primary text-primary-foreground hover:opacity-90">Book this Puja</Button>
            </a>
            <Link href="/" className="sm:flex-1">
              <Button variant="outline" className="w-full bg-transparent">
                Back to Home
              </Button>
            </Link>
          </div>
        </div>
      </div>
    </main>
  )
}
