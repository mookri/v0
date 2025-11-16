import { seoManager } from "@/lib/seo-manager"
import EventsClient from "./client"
import Script from "next/script"

// Schema markup for the events page
const EVENTS_SCHEMA = seoManager.generateSchema({
  type: "CollectionPage",
  name: "Upcoming Events - Navdhya",
  description: "Calendar-based spiritual events and gatherings. Join our community for workshops, discourses, and cultural celebrations.",
  path: "/community/events",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Community", url: "https://www.navdhya.com/community" },
    { name: "Upcoming Events", url: "https://www.navdhya.com/community/events" }
  ]
})

export async function generateMetadata() {
  return seoManager.generateMetadata({
    path: "/community/events",
    title: "Upcoming Events - Navdhya",
    description: "Calendar-based spiritual events and gatherings. Join our community for workshops, discourses, and cultural celebrations.",
    keywords: ["spiritual events", "community gatherings", "workshops", "discourses"]
  })
}

export default function EventsPage() {
  const eventsSchema = JSON.stringify(EVENTS_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: eventsSchema }}
      />
      <EventsClient eventsSchema={eventsSchema} />
    </>
  )
}