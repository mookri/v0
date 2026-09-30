export function InfoBar() {
  return (
    <section aria-label="Quick contact and service coverage" className="text-accent-foreground bg-primary">
      <div className="container mx-auto grid grid-cols-1 md:grid-cols-3 overflow-x-hidden w-full">
        <div className="flex flex-col items-center sm:items-start p-4" style={{ backgroundColor: '#d97706' }}>
          <h3 className="font-serif text-base sm:text-lg font-medium tracking-tight text-center sm:text-left text-white">Need Help Choosing?</h3>
          <p className="text-xs sm:text-sm mt-1 text-center sm:text-left text-white/90">
            Call us at{" "}
            <a href="tel:+917289000377" className="underline hover:text-white/80 transition-colors duration-200">
              +91-7289000377
            </a>
          </p>
        </div>
        <div className="flex flex-col items-center p-4" style={{ backgroundColor: '#0E3F2E' }}>
          <h3 className="font-serif text-base sm:text-lg font-medium tracking-tight text-center text-white">We Serve 50+ Cities</h3>
          <p className="text-xs sm:text-sm mt-1 text-center sm:text-left text-white/90">Pan-India service coverage</p>
        </div>
        <div className="flex flex-col items-center sm:items-end p-4 bg-primary">
          <h3 className="font-serif text-base sm:text-lg font-medium tracking-tight text-center sm:text-left text-white">Quick Connect</h3>
          <p className="text-xs sm:text-sm mt-1 text-center sm:text-left text-white/90">
            <a className="underline hover:text-white/80 transition-colors duration-200" href="mailto:help@navdhya.com">
              Email
            </a>{" "}
            •{" "}
            <a className="underline hover:text-white/80 transition-colors duration-200" href="https://wa.me/917289000377" target="_blank" rel="noopener noreferrer">
              WhatsApp
            </a>{" "}
            •{" "}
            <a className="underline hover:text-white/80 transition-colors duration-200" href="#booking">
              Callback
            </a>
          </p>
        </div>
      </div>
    </section>
  )
}