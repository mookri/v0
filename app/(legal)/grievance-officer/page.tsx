import { seoManager } from "@/lib/seo-manager"
import Script from "next/script"

// Add page-specific metadata
export const metadata = seoManager.generateMetadata({
  title: "Contact & Grievance Officer",
  description: "Contact information for Navdhya's Grievance Officer as mandated under Indian IT Rules, 2021. Reach out for complaints, concerns, or assistance with our services.",
  path: "/grievance-officer",
  keywords: ["grievance officer", "contact us", "customer complaints", "IT Rules 2021", "Navdhya support", "customer service"]
})

// Schema markup for the grievance officer page
const GRIEVANCE_OFFICER_SCHEMA = seoManager.generateSchema({
  type: "WebPage",
  name: "Contact & Grievance Officer - Navdhya",
  description: "Contact information for Navdhya's Grievance Officer as mandated under Indian IT Rules, 2021. Reach out for complaints, concerns, or assistance with our services.",
  path: "/grievance-officer",
  breadcrumbs: [
    { name: "Home", url: "https://www.navdhya.com" },
    { name: "Legal", url: "https://www.navdhya.com/legal" },
    { name: "Contact & Grievance Officer", url: "https://www.navdhya.com/grievance-officer" }
  ]
})

export default function GrievanceOfficerPage() {
  const grievanceOfficerSchema = JSON.stringify(GRIEVANCE_OFFICER_SCHEMA)
  
  return (
    <>
      <Script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: grievanceOfficerSchema }}
      />
      <main className="mx-auto max-w-6xl px-4 py-12 space-y-10 overflow-x-hidden w-full">
        <section aria-labelledby="grievance-heading">
          <h1 id="grievance-heading" className="font-serif text-3xl">
            Contact & Grievance Officer
          </h1>
          <p className="mt-3 text-muted-foreground">
            As mandated under the Information Technology (Intermediary Guidelines and Digital Media Ethics Code) Rules, 2021, Navdhya has appointed a Grievance Officer to address complaints and concerns from users of our platform.
          </p>
        </section>

        <section aria-labelledby="grievance-officer-info">
          <h2 id="grievance-officer-info" className="font-serif text-2xl">
            Grievance Officer Information
          </h2>
          <div className="mt-3 p-6 bg-white rounded-lg shadow-sm border border-gray-100">
            <div className="flex flex-col md:flex-row gap-6">
              <div className="md:w-2/3">
                <h3 className="font-serif text-xl text-[#0E3F2E]">
                  Mr. Rajesh Kumar
                </h3>
                <p className="mt-2 text-muted-foreground">
                  Grievance Officer
                </p>
                <div className="mt-4 space-y-3">
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-[#0E3F2E] mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span className="text-muted-foreground">
                      grievance@navdhya.com
                    </span>
                  </div>
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-[#0E3F2E] mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    <span className="text-muted-foreground">
                      +91-7289000377
                    </span>
                  </div>
                  <div className="flex items-start">
                    <svg className="w-5 h-5 text-[#0E3F2E] mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span className="text-muted-foreground">
                      Navdhya Ritual Services<br />
                      Pandav Nagar<br />
                      Delhi 110092<br />
                      India
                    </span>
                  </div>
                </div>
              </div>
              <div className="md:w-1/3 flex items-center justify-center">
                <div className="bg-gray-200 border-2 border-dashed rounded-xl w-32 h-32 flex items-center justify-center text-gray-500">
                  Officer Photo
                </div>
              </div>
            </div>
          </div>
        </section>

        <section aria-labelledby="role-responsibilities">
          <h2 id="role-responsibilities" className="font-serif text-2xl">
            Role and Responsibilities
          </h2>
          <p className="mt-3 text-muted-foreground">
            The Grievance Officer is responsible for:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Receiving and acknowledging user complaints within 24 hours</li>
            <li>Investigating and resolving grievances in a fair and timely manner</li>
            <li>Ensuring compliance with all applicable laws and regulations</li>
            <li>Maintaining confidentiality of user information and complaints</li>
            <li>Providing regular reports on grievance trends and resolutions</li>
            <li>Coordinating with relevant departments to address complex issues</li>
          </ul>
        </section>

        <section aria-labelledby="grievance-process">
          <h2 id="grievance-process" className="font-serif text-2xl">
            Grievance Redressal Process
          </h2>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Step 1: Submit Your Complaint
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                You can submit your complaint through any of the following channels:
              </p>
              <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
                <li>Email: grievance@navdhya.com</li>
                <li>Phone: +91-7289000377</li>
                <li>WhatsApp: +91-7289000377</li>
                <li>Registered postal address provided above</li>
                <li>Through our website's contact form</li>
              </ul>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Step 2: Acknowledgment
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We will acknowledge receipt of your complaint within 24 hours and provide you with a ticket number for tracking purposes.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Step 3: Investigation
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Our team will investigate your complaint thoroughly, which may involve contacting relevant parties and reviewing documentation.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Step 4: Resolution
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We aim to resolve all grievances within 72 hours of receipt. Complex cases may require additional time, and you will be informed of any delays.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Step 5: Communication of Resolution
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                We will communicate the resolution to you through your preferred communication channel and request confirmation of satisfaction.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="types-of-grievances">
          <h2 id="types-of-grievances" className="font-serif text-2xl">
            Types of Grievances
          </h2>
          <p className="mt-3 text-muted-foreground">
            We handle various types of grievances, including but not limited to:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Service quality issues and complaints about pandits</li>
            <li>Billing and payment disputes</li>
            <li>Privacy and data protection concerns</li>
            <li>Website functionality and accessibility issues</li>
            <li>Content-related complaints</li>
            <li>Account access and security issues</li>
            <li>Refund and cancellation disputes</li>
            <li>General customer service concerns</li>
          </ul>
        </section>

        <section aria-labelledby="working-hours">
          <h2 id="working-hours" className="font-serif text-2xl">
            Working Hours
          </h2>
          <p className="mt-3 text-muted-foreground">
            The Grievance Officer is available during the following hours:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>Monday to Sunday: 9:00 AM to 9:00 PM IST</li>
            <li>Emergency grievances: 24/7 availability for urgent matters</li>
            <li>Response time: Within 24 hours for acknowledgment</li>
            <li>Resolution time: Within 72 hours for most grievances</li>
          </ul>
        </section>

        <section aria-labelledby="escalation">
          <h2 id="escalation" className="font-serif text-2xl">
            Escalation Process
          </h2>
          <p className="mt-3 text-muted-foreground">
            If you are not satisfied with the resolution provided by the Grievance Officer, you may escalate your complaint:
          </p>
          <div className="mt-3 space-y-4">
            <div>
              <h3 className="font-serif text-xl">
                Level 1: Senior Management
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                Escalate to our Senior Management Team by emailing senior.management@navdhya.com or calling +91-7289000377.
              </p>
            </div>
            <div>
              <h3 className="font-serif text-xl">
                Level 2: External Authorities
              </h3>
              <p className="mt-3 text-muted-foreground text-sm">
                For unresolved grievances, you may approach consumer forums or regulatory authorities as per applicable laws.
              </p>
            </div>
          </div>
        </section>

        <section aria-labelledby="confidentiality">
          <h2 id="confidentiality" className="font-serif text-2xl">
            Confidentiality
          </h2>
          <p className="mt-3 text-muted-foreground">
            We maintain strict confidentiality of all grievances and personal information shared during the grievance process. Information is only disclosed:
          </p>
          <ul className="mt-3 text-sm text-muted-foreground list-disc list-inside">
            <li>With your explicit consent</li>
            <li>As required by law or regulatory authorities</li>
            <li>To resolve the specific grievance (on a need-to-know basis)</li>
            <li>For internal training and process improvement purposes (in anonymized form)</li>
          </ul>
        </section>

        <section aria-labelledby="additional-contact">
          <h2 id="additional-contact" className="font-serif text-2xl">
            Additional Contact Information
          </h2>
          <p className="mt-3 text-muted-foreground">
            For general inquiries and support, you may also contact:
          </p>
          <div className="mt-3 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
              <h3 className="font-serif text-lg text-[#0E3F2E]">
                Customer Support
              </h3>
              <div className="mt-3 space-y-2">
                <p className="text-muted-foreground">
                  Email: help@navdhya.com
                </p>
                <p className="text-muted-foreground">
                  Phone: +91-7289000377
                </p>
                <p className="text-muted-foreground">
                  Hours: 9:00 AM - 9:00 PM IST
                </p>
              </div>
            </div>
            <div className="p-4 bg-white rounded-lg shadow-sm border border-gray-100">
              <h3 className="font-serif text-lg text-[#0E3F2E]">
                Technical Support
              </h3>
              <div className="mt-3 space-y-2">
                <p className="text-muted-foreground">
                  Email: techsupport@navdhya.com
                </p>
                <p className="text-muted-foreground">
                  Hours: 24/7
                </p>
              </div>
            </div>
          </div>
        </section>
      </main>
    </>
  )
}