import Link from "next/link"

export function BlogPreview() {
  const posts = [
    ["Complete Grihapravesh Puja Guide", "8 min read"],
    ["Choosing the Right Pandit for Your Wedding", "6 min read"],
    ["Understanding Shraddh: Vedic Traditions", "7 min read"],
  ]
  return (
    <section id="blog" aria-labelledby="blog-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12">
        <div className="flex items-end justify-between">
          <h2 id="blog-title" className="font-serif text-2xl md:text-3xl">
            Knowledge Blog
          </h2>
          <Link href="#" className="text-sm hover:text-primary">
            Explore Spiritual Knowledge Base →
          </Link>
        </div>
        <div className="grid md:grid-cols-3 gap-6 mt-6">
          {posts.map(([title, readTime]) => (
            <article key={title} className="rounded-lg border bg-card p-4">
              <img src="/hindu-rituals-knowledge-blog.jpg" alt={`Cover image for: ${title}`} className="rounded-md border" />
              <h3 className="font-serif text-lg mt-3">{title}</h3>
              <p className="text-sm text-muted-foreground">{readTime}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
