import { servicesMetadata } from "@/lib/services-metadata"
import dynamic from "next/dynamic"

// Dynamically import the client component
const ServicesClient = dynamic(() => import("./client"), {
  loading: () => <div className="min-h-screen flex items-center justify-center">Loading...</div>
})

export default function ServicesPage() {
  return <ServicesClient />
}

export { servicesMetadata as metadata }