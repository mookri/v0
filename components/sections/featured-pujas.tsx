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
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358800/Whisk_12d1d962f45c56a89444ef5b3cc2f267dr_fzoivd.jpg"
  },
  {
    slug: "lakshmi-puja",
    title: "Lakshmi Puja",
    desc: "Invoke Goddess Lakshmi for wealth and prosperity",
    price: "₹2,999",
    tag: "Popular",
    imgQuery: "lakshmi%20puja%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358803/Whisk_0f5f669b41ae7caa0134b73a006a3c5edr_1_sb2pdr.jpg"
  },
  {
    slug: "satyanarayan-katha",
    title: "Satyanarayan Puja",
    desc: "Seek Lord Vishnu's grace and prosperity",
    price: "₹3,999",
    tag: undefined,
    imgQuery: "satyanarayan%20katha%20ritual",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358798/Whisk_7a093de03fbc3bab93949b075483d783dr_zwnihl.jpg"
  },
  {
    slug: "ganesh-puja",
    title: "Ganesh Puja",
    desc: "Remove obstacles and invite success",
    price: "₹1,999",
    tag: "Quick Setup",
    imgQuery: "ganesh%20puja%20traditional%20setup",
    imageUrl: "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358805/Whisk_3a4f5d6e7c8b9a0c1d2e3f4a5b6c7d8e_ganesh_puja.jpg"
  },
]

export function FeaturedPujas() {
  return (
    <section aria-labelledby="featured-pujas" className="bg-muted/50">
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <div className="text-center mb-10">
          <h2 id="featured-pujas" className="font-serif text-2xl md:text-3xl">  
            Featured Pujas
            <span className="absolute bottom-0 left-0 w-full h-1 bg-primary/20 rounded-full"></span>
          </h2>
          <p className="text-muted-foreground mt-4 text-sm md:text-base max-w-2xl mx-auto font-sans">
            Explore our most popular rituals performed by certified pandits
          </p>
        </div>
        <div className="mt-6 grid gap-6 grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 w-full">
          {items.map((it) => (
            <div
              key={it.title}
              className="group overflow-hidden rounded-lg border bg-card transition-transform hover:-translate-y-1"
            >
              <Link href={`/pujas/${it.slug}`} className="block">
                <div className="relative w-full aspect-[4/3]">
                  {it.imageUrl ? (
                    <Image
                      src={it.imageUrl}
                      alt={`${it.title} representative image`}
                      fill
                      className="object-cover"
                      sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                      priority={it.tag === "Most Booked"}
                    />
                  ) : (
                    <Image
                      src={`/.jpg?key=s7bpr&height=320&width=480&query=${it.imgQuery}`}
                      alt={`${it.title} representative image`}
                      fill
                      className="object-cover"
                      sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
                      priority={it.tag === "Most Booked"}
                    />
                  )}
                  {it.tag ? (
                    <span className="absolute left-2 top-2 rounded bg-secondary px-2 py-1 text-xs text-secondary-foreground max-w-[calc(100%-1rem)] overflow-hidden">
                      {it.tag}
                    </span>
                  ) : null}
                </div>
                <div className="p-4 flex flex-col h-full">
                  <h3 className="font-sans text-lg">{it.title}</h3>
                  <p className="mt-1 text-sm text-muted-foreground flex-grow font-sans">{it.desc}</p>
                  <p className="mt-2 text-sm font-sans">
                    Starting from <span className="font-medium">{it.price}</span>
                  </p>
                </div>
              </Link>
              <div className="flex items-center gap-3 px-4 pb-4">
                <Link href="#booking" className="flex-1">
                  <Button className="w-full bg-primary text-primary-foreground font-sans" size="sm">
                    Book Now
                  </Button>
                </Link>
                <Link href={`/pujas/${it.slug}`} className="shrink-0 text-sm hover:text-primary font-sans hidden sm:block">
                  Learn More →
                </Link>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-6 text-center">
       <Button className="w-sm bg-primary text-primary-foreground font-sans" size="sm">View All 25+ Rituals</Button>
        </div>
      </div>
    </section>
  )
}