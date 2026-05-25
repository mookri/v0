import Link from "next/link"

const posts = [
  {
    title: "Complete Griha Pravesh Puja Guide",
    readTime: "8 min read",
    href: "/ceremonies/griha-pravesh-puja-vidhi",
  },
  {
    title: "Choosing the Right Pandit for Your Wedding",
    readTime: "6 min read",
    href: "/pandit-services/pandit-wedding-delhi",
  },
  {
    title: "Understanding Shradh: Vedic Traditions",
    readTime: "7 min read",
    href: "/ceremonies/shradh-ceremony-guide",
  },
  {
    title: "Navratri Celebrations and Rituals",
    readTime: "5 min read",
    href: "/festival-rituals/navratri-festival-ritual-guide",
  },
]

export function BlogPreview() {
  return (
    <section id="blog" aria-labelledby="blog-title" className="bg-background">
      <div className="mx-auto max-w-6xl px-4 py-12 overflow-x-hidden w-full">
        <div className="pb-4 text-center md:text-left">
          <div className="inline-block">
            <p
              style={{
                color: "#6B7280",
                letterSpacing: "1px",
                lineHeight: "12px",
                textTransform: "uppercase",
                fontWeight: 500,
                fontSize: "10px",
                marginBottom: "12px",
                fontFamily: "'DM Sans', sans-serif",
              }}
              className="text-center md:text-left"
            >
              Spiritual Insights - Ancient Wisdom
            </p>
            <h2
              id="blog-title"
              className="font-serif text-2xl md:text-3xl lg:text-3xl font-bold tracking-tight text-center md:text-left mb-3 relative inline-block"
              style={{ color: "#111827" }}
            >
              Knowledge Blog
            </h2>
          </div>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-6 w-full">
          {posts.map((post) => (
            <article key={post.href} className="rounded-lg border bg-card p-2 flex flex-col">
              <div className="relative h-40 w-full overflow-hidden rounded-md border">
                <img
                  src="/hindu-rituals-knowledge-blog.jpg"
                  alt={`Cover image for ${post.title}`}
                  className="object-cover w-full h-full"
                />
              </div>
              <h3 className="font-serif text-sm mt-2">{post.title}</h3>
              <p className="text-xs text-muted-foreground mt-auto">{post.readTime}</p>
              <div className="mt-2">
                <Link
                  href={post.href}
                  className="inline-flex items-center justify-center gap-2 px-3 py-1.5 text-xs font-medium rounded-lg bg-gradient-to-r from-[#0E3F2E] to-[#155d42] text-white hover:from-[#155d42] hover:to-[#0E3F2E] transition-all duration-300 ease-in-out transform hover:scale-105 hover:shadow-md group w-full"
                >
                  Explore
                  <span className="transition-transform duration-300 ease-in-out group-hover:translate-x-1">→</span>
                </Link>
              </div>
            </article>
          ))}
        </div>
        <div className="mt-8 text-center">
          <Link
            href="/resources/blog"
            className="inline-flex items-center justify-center gap-2 px-6 py-3 text-sm font-medium rounded-full bg-gradient-to-r from-[#0E3F2E] to-[#155d42] text-white hover:from-[#155d42] hover:to-[#0E3F2E] transition-all duration-500 ease-in-out transform hover:scale-105 hover:shadow-lg group"
          >
            Explore Spiritual Knowledge Base
            <span className="animate-looping-arrow">→</span>
          </Link>
        </div>
      </div>
    </section>
  )
}
