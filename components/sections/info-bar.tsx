export function InfoBar() {
  return (
    <section aria-label="Quick contact and service coverage" className="bg-accent text-accent-foreground">
      <div className="container mx-auto px-4 py-8 flex justify-between overflow-x-hidden w-full">
        <div className="flex flex-col">
          <h3 className="font-serif text-lg">Need Help Choosing?</h3>
          <p className="text-sm mt-1">
            Call us at{" "}
            <a href="tel:+910000000000" className="underline">
              +91 00000 00000
            </a>
          </p>
        </div>
        <div className="flex flex-col">
          <h3 className="font-serif text-lg">We Serve 50+ Cities</h3>
          <p className="text-sm mt-1">Pan-India service coverage</p>
        </div>
        <div className="flex flex-col">
          <h3 className="font-serif text-lg">Quick Connect</h3>
          <p className="text-sm mt-1">
            <a className="underline" href="mailto:hello@navdhya.example">
              Email
            </a>{" "}
            •{" "}
            <a className="underline" href="https://wa.me/910000000000" target="_blank" rel="noopener noreferrer">
              WhatsApp
            </a>{" "}
            •{" "}
            <a className="underline" href="#booking">
              Callback
            </a>
          </p>
        </div>
      </div>
    </section>
  )
}
