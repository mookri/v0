"use client"

export default function WhatsAppFloat() {
  return (
    <a
      href="https://wa.me/0000000000"
      target="_blank"
      rel="noopener noreferrer"
      aria-label="Chat on WhatsApp"
      className="fixed bottom-5 right-5 z-50 rounded-full bg-green-500 text-white px-4 py-3 shadow-lg hover:bg-green-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-green-500 md:bottom-8 md:right-8 max-w-[calc(100vw-40px)] overflow-hidden whitespace-nowrap"
    >
      WhatsApp
    </a>
  )
}