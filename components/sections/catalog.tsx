import { Button } from "@/components/ui/button"

export function Catalog() {
  const items = Array.from({ length: 8 }).map((_, i) => ({
    title: `Ritual ${i + 1}`,
    price: "From ₹3,999",
  }))
  return (
    <section id="catalog" aria-labelledby="catalog-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <div className="flex items-center justify-between">
          <h2 id="catalog-title" className="font-serif text-2xl md:text-3xl">
            Puja Catalog
          </h2>
          <div className="flex gap-2 sticky top-14 z-20 bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60 p-1 rounded-md border">
            <Button variant="outline" size="sm">
              Home Rituals
            </Button>
            <Button variant="outline" size="sm">
              Life Milestones
            </Button>
            <Button variant="outline" size="sm">
              Festival
            </Button>
            <Button variant="outline" size="sm">
              Corporate
            </Button>
            <Button variant="outline" size="sm">
              Special
            </Button>
          </div>
        </div>
        <div className="grid md:grid-cols-4 gap-6 mt-6">
          {items.map((it, idx) => (
            <div key={idx} className="rounded-lg border bg-card p-4">
              <img src="/puja-ritual-thumbnail.jpg" alt="Puja ritual thumbnail" className="rounded-md border" />
              <h3 className="font-serif text-lg mt-3">{it.title}</h3>
              <p className="text-sm text-muted-foreground">{it.price}</p>
              <Button className="mt-3 bg-primary text-primary-foreground" size="sm">
                Book Now
              </Button>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
