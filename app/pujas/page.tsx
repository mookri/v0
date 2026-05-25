import { seoManager } from "@/lib/seo-manager"
import PujaCatalogClient from "./client"
import { LayoutOrchestrator } from "@/components/dlo-x/LayoutOrchestrator"
import { ErrorBoundary } from "@/components/dlo-x/ErrorBoundary"
import { getDefaultContext } from "@/lib/context-resolver"

export const metadata = seoManager.generateMetadata({
  title: "Book Pandit Online in Delhi NCR | Puja Services, Havan, Weddings, Griha Pravesh",
  description:
    "Book pandit online in Delhi NCR with Navdhya for griha pravesh, havan, satyanarayan katha, wedding rituals, vastu shanti, baby ceremonies, and business pujas.",
  path: "/pujas",
  keywords: [
    "book pandit online delhi",
    "pandit booking delhi ncr",
    "home puja services delhi",
    "puja services delhi ncr",
    "book pandit delhi",
    "griha pravesh puja delhi",
    "havan pandit booking",
    "wedding pandit delhi",
  ],
})

const PUJA_CATALOG_SCHEMA = {
  "@context": "https://schema.org",
  "@graph": [
    seoManager.generateSchema({
      type: "CollectionPage",
      name: "Puja Services Catalog - Navdhya",
      description:
        "Comprehensive catalog of authentic Hindu puja services and spiritual rituals performed by certified Vedic pandits in Delhi NCR.",
      path: "/pujas",
      breadcrumbs: [
        { name: "Home", url: "https://www.navdhya.com" },
        { name: "Puja Services", url: "https://www.navdhya.com/pujas" },
      ],
    }),
    {
      "@type": "BreadcrumbList",
      "@id": "https://www.navdhya.com/pujas#breadcrumb",
      itemListElement: [
        { "@type": "ListItem", position: 1, name: "Home", item: "https://www.navdhya.com" },
        { "@type": "ListItem", position: 2, name: "Puja Services", item: "https://www.navdhya.com/pujas" },
      ],
    },
    {
      "@type": "ItemList",
      "@id": "https://www.navdhya.com/pujas#itemlist",
      name: "Popular puja services in Delhi NCR",
      itemListElement: [
        { "@type": "ListItem", position: 1, url: "https://www.navdhya.com/pujas/griha-pravesh", name: "Griha Pravesh Puja" },
        { "@type": "ListItem", position: 2, url: "https://www.navdhya.com/pujas/satyanarayan-katha", name: "Satyanarayan Puja" },
        { "@type": "ListItem", position: 3, url: "https://www.navdhya.com/pujas/lakshmi-ganesh-puja", name: "Lakshmi Ganesh Puja" },
        { "@type": "ListItem", position: 4, url: "https://www.navdhya.com/pujas/hindu-wedding", name: "Hindu Wedding Ceremony" },
        { "@type": "ListItem", position: 5, url: "https://www.navdhya.com/pujas/baby-naming", name: "Baby Naming Ceremony" },
        { "@type": "ListItem", position: 6, url: "https://www.navdhya.com/pujas/annaprashan", name: "Annaprashan Ceremony" },
        { "@type": "ListItem", position: 7, url: "https://www.navdhya.com/pujas/office-opening", name: "Office Opening Puja" },
        { "@type": "ListItem", position: 8, url: "https://www.navdhya.com/pujas/vastu-shanti", name: "Vastu Shanti Puja" },
        { "@type": "ListItem", position: 9, url: "https://www.navdhya.com/pujas/navagraha-puja", name: "Navagraha Puja" },
        { "@type": "ListItem", position: 10, url: "https://www.navdhya.com/pujas/havan", name: "Havan and Yagya" },
        { "@type": "ListItem", position: 11, url: "https://www.navdhya.com/pujas/mundan", name: "Mundan Sanskar" },
        { "@type": "ListItem", position: 12, url: "https://www.navdhya.com/pujas/bhoomi-pujan", name: "Bhoomi Pujan" },
      ],
    },
  ],
}

export default async function PujaCatalogPage() {
  const context = getDefaultContext("/pujas")

  return (
    <ErrorBoundary>
      <LayoutOrchestrator context={context}>
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(PUJA_CATALOG_SCHEMA) }}
        />
        <PujaCatalogClient />
      </LayoutOrchestrator>
    </ErrorBoundary>
  )
}
