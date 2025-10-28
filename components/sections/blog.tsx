import Link from "next/link"

export function BlogPreview() {
  const posts = [
    ["Complete Grihapravesh Puja Guide", "8 min read"],
    ["Choosing the Right Pandit for Your Wedding", "6 min read"],
    ["Understanding Shraddh: Vedic Traditions", "7 min read"],
  ]
  return (
    <section id="blog" aria-labelledby="blog-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <div className="pb-4 text-center md:text-left">
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
            className="text-center md:text-left"
            >
              Spiritual Insights - Ancient Wisdom
            </p>
            <h2 
              id="blog-title"
              className="font-serif text-2xl md:text-3xl lg:text-3xl font-bold tracking-tight text-center md:text-left mb-3 relative inline-block"
              style={{ color: '#111827' }}
            >
              Knowledge Blog
            </h2>
          </div>
        </div>
        <div className="grid md:grid-cols-3 gap-6 mt-6 w-full">
          {posts.map(([title, readTime]) => (
            <article key={title} className="rounded-lg border bg-card p-4 flex flex-col">
              <img src="/hindu-rituals-knowledge-blog.jpg" alt={`Cover image for: ${title}`} className="rounded-md border w-full h-auto" />
              <h3 className="font-serif text-lg mt-3">{title}</h3>
              <p className="text-sm text-muted-foreground mt-auto">{readTime}</p>
            </article>
          ))}
        </div>
        <div className="mt-8 text-center">
          <Link href="#" className="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium rounded-full bg-[#0E3F2E] text-white hover:bg-[#0E3F2E]/90 transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-lg">
            Explore Spiritual Knowledge Base <span className="transition-transform duration-300 ease-in-out group-hover:translate-x-1">→</span>
          </Link>
        </div>
      </div>
    </section>
  )
}