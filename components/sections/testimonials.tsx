import { TestimonialCarousel } from "@/components/sections/testimonial-carousel";

export function Testimonials() {
  return (
    <section aria-labelledby="testimonials-title" className="bg-muted/50 py-16">
      <div className="mx-auto max-w-6xl px-4 w-full">
        <TestimonialCarousel />
      </div>
    </section>
  );
}