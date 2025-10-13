import { Button } from "@/components/ui/button"
import Link from "next/link"
import Image from "next/image"

const items = [
  {
    slug: "griha-pravesh",
    title: "Grihapravesh Puja",
    desc: "Begin your new chapter with divine blessings",
    price: "₹4,999",
    tag: "Most Booked",
    imgQuery: "griha%20pravesh%20home%20ritual%20mandap",
  },
  {
    slug: "hindu-wedding",
    title: "Hindu Wedding Ceremony",
    desc: "Complete traditional wedding rituals",
    price: "₹24,999",
    tag: "Popular",
    imgQuery: "hindu%20wedding%20ceremony%20mandap",
  },
  {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Puja",
    desc: "Seek Lord Vishnu's grace and prosperity",
    price: "₹3,999",
    tag: undefined,
    imgQuery: "satyanarayan%20katha%20ritual",
  },
  {
    slug: "shraddh",
    title: "Shraddh Ceremony",
    desc: "Honor your ancestors with authentic rituals",
    price: "₹5,999",
    tag: undefined,
    imgQuery: "shraddh%20ceremony%20ritual",
  },
]

export function FeaturedPujas() {
  return (
    <section aria-labelledby="featured-pujas" className="bg-muted/50">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <h2 id="featured-pujas" className="font-serif text-2xl md:text-3xl">
          Featured Pujas
        </h2>
        <div className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          {items.map((it) => (
            <div
              key={it.title}
              className="group overflow-hidden rounded-lg border bg-card transition-transform hover:-translate-y-1"
            >
              <Link href={`/pujas/${it.slug}`} className="block">
                <div className="relative h-40 w-full">
                  <Image
                    src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                    alt={`${it.title} representative image`}
                    fill
                    className="object-cover"
                    sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                    priority={it.tag === "Most Booked"}
                  />
                  {it.tag ? (
                    <span className="absolute left-2 top-2 rounded bg-secondary px-2 py-1 text-xs text-secondary-foreground">
                      {it.tag}
                    </span>
                  ) : null}
                </div>
                <div className="p-4">
                  <h3 className="font-serif text-lg">{it.title}</h3>
                  <p className="mt-1 text-sm text-muted-foreground">{it.desc}</p>
                  <p className="mt-2 text-sm">
                    Starting from <span className="font-medium">{it.price}</span>
                  </p>
                </div>
              </Link>
              <div className="flex items-center gap-3 px-4 pb-4">
                <Link href="#booking" className="flex-1">
                  <Button className="w-full bg-primary text-primary-foreground" size="sm">
                    Book Now
                  </Button>
                </Link>
                <Link href={`/pujas/${it.slug}`} className="shrink-0 text-sm hover:text-primary">
                  Learn More →
                </Link>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-6 text-center">
          <Button variant="link">View All 25+ Rituals</Button>
        </div>
      </div>
    </section>
  )
}
