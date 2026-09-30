import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Service Guarantee Policy",
  description: "Navdhya's commitment to quality service delivery. Our Service Guarantee ensures exceptional ritual experiences with verified pandits, punctuality, and customer satisfaction.",
  path: "/service-guarantee",
  keywords: ["service guarantee", "quality assurance", "ritual satisfaction", "pandit promise", "Navdhya guarantee"]
})

// Schema markup for the service guarantee page
const SERVICE_GUARANTEE_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Service Guarantee Policy - Navdhya",
  description: "Navdhya's commitment to quality service delivery. Our Service Guarantee ensures exceptional ritual experiences with verified pandits, punctuality, and customer satisfaction.",
  path: "/service-guarantee",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Service Guarantee Policy", url: "https://www.navdhya.com/service-guarantee" }
  ]
})

export default function ServiceGuaranteePage() {
  const serviceGuaranteeSchema = JSON.stringify(SERVICE_GUARANTEE_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: serviceGuaranteeSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="guarantee-heading">
          <h1 id="guarantee-heading" className="font-serif text-3xl">
            Service Guarantee Policy
          </h1>
          <p className="mt-3 text-muted-foreground">
            At Navdhya, we are committed to providing exceptional spiritual services that meet the highest standards of quality, authenticity, and customer satisfaction. Our Service Guarantee Policy outlines the promises we make to our customers and the standards we hold ourselves to.
          </p>
        </section>

        <section aria-labelledby="verified-pandit-promise">
          <h2 id="verified-pandit-promise" className="font-serif text-2xl">
            Verified Pandit Promise
          </h2>
          <p className="mt-3 text-muted-foreground">
            Every pandit in our network undergoes a rigorous verification process to ensure they meet our quality standards:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>All pandits are certified Vedic scholars with formal education in Hindu scriptures</li>
            <li>Minimum 5 years of practical experience in performing rituals</li>
            <li>Thorough background checks and police verification</li>
            <li>Regular performance evaluations based on customer feedback</li>
            <li>Ongoing training to stay updated with traditional practices</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            If you are not satisfied with the qualifications or professionalism of a pandit assigned to your booking, we will replace them at no additional cost or provide a full refund.
          </p>
        </section>

        <section aria-labelledby="punctuality-guarantee">
          <h2 id="punctuality-guarantee" className="font-serif text-2xl">
            Punctuality Guarantee
          </h2>
          <p className="mt-3 text-muted-foreground">
            We understand the importance of timing in spiritual rituals and ceremonies. Our punctuality guarantee ensures:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Pandits arrive on time as per the scheduled appointment</li>
            <li>For home pujas, pandits arrive within 15 minutes of the scheduled time</li>
            <li>For temple services, pandits are present and prepared 30 minutes prior</li>
            <li>Online consultations begin at the exact scheduled time</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            If a pandit is more than 30 minutes late without prior communication, we will:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Offer a 25% discount on the service cost</li>
            <li>Reschedule the service at no additional charge</li>
            <li>Provide a full refund if the delay significantly impacts the purpose of the ritual</li>
          </ul>
        </section>

        <section aria-labelledby="ritual-satisfaction-policy">
          <h2 id="ritual-satisfaction-policy" className="font-serif text-2xl">
            Ritual Satisfaction Policy
          </h2>
          <p className="mt-3 text-muted-foreground">
            We guarantee that all rituals and services performed through Navdhya will be conducted with:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Complete adherence to traditional Vedic procedures</li>
            <li>Proper use of authentic mantras and chants</li>
            <li>Appropriate sacred materials and offerings</li>
            <li>Respect for your specific requirements and beliefs</li>
          </ul>
          <p className="mt-3 text-muted-foreground">
            If you are not satisfied with the quality or execution of a ritual:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Contact our customer support within 48 hours of service completion</li>
            <li>Provide detailed feedback about the issues encountered</li>
            <li>We will investigate and respond within 3 business days</li>
            <li>Appropriate compensation or corrective action will be provided</li>
          </ul>
        </section>

        <section aria-labelledby="quality-assurance">
          <h2 id="quality-assurance" className="font-serif text-2xl">
            Quality Assurance Measures
          </h2>
          <p className="mt-3 text-muted-foreground">
            We maintain high standards through continuous quality monitoring:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Customer Feedback System
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We collect detailed feedback after every service to identify areas for improvement and recognize exceptional performance.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Regular Audits
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our quality team conducts regular audits of pandit performance, customer satisfaction, and service delivery processes.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Continuous Training
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We provide ongoing training to our pandits on best practices, customer service, and evolving religious traditions.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="service-standards">
          <h2 id="service-standards" className="font-serif text-2xl">
            Service Standards
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our commitment to excellence includes:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Transparent pricing with no hidden charges</li>
            <li>Clear communication about service details and expectations</li>
            <li>Respect for all religious beliefs and practices</li>
            <li>Professional conduct and appropriate attire</li>
            <li>Cleanliness and respect for customer premises</li>
            <li>Confidentiality of personal and religious information</li>
          </ul>
        </section>

        <section aria-labelledby="customer-support">
          <h2 id="customer-support" className="font-serif text-2xl">
            Customer Support
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our dedicated customer support team is available to assist you:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>24/7 availability for urgent concerns</li>
            <li>Monday-Sunday, 9:00 AM - 9:00 PM IST for general inquiries</li>
            <li>Multiple contact options: phone, email, and WhatsApp</li>
            <li>Quick response time: within 2 hours for urgent issues</li>
            <li>Comprehensive issue resolution within 24-48 hours</li>
          </ul>
        </section>

        <section aria-labelledby="exceptions">
          <h2 id="exceptions" className="font-serif text-2xl">
            Exceptions and Limitations
          </h2>
          <p className="mt-3 text-muted-foreground">
            Our Service Guarantee is subject to the following conditions:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Force majeure events (natural disasters, government restrictions, etc.)</li>
            <li>Customer failure to provide accurate information or requirements</li>
            <li>Unavailability of specific materials due to supply chain issues</li>
            <li>Acts of God or other unforeseeable circumstances</li>
            <li>Customer cancellation less than 48 hours before the scheduled service</li>
          </ul>
        </section>
      </main>
    </>
  )
}