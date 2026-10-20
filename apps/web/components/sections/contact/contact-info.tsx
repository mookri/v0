'use client'

import React from 'react'
import { MapPin, Clock, Phone, Mail } from "lucide-react"

const ContactInfo = ({ className = "" }: { className?: string }) => {
  return (
    <div className={`space-y-6 ${className}`}>
      <div className="flex items-start gap-4">
        <div className="mt-1 p-2 bg-primary/10 rounded-lg">
          <MapPin className="w-5 h-5 text-primary" />
        </div>
        <div>
          <h4 className="font-medium text-foreground">Address</h4>
          <p className="text-muted-foreground">
            Pandav Nagar<br />
            New Delhi, 110092<br />
            India
          </p>
        </div>
      </div>
      
      <div className="flex items-start gap-4">
        <div className="mt-1 p-2 bg-primary/10 rounded-lg">
          <Clock className="w-5 h-5 text-primary" />
        </div>
        <div>
          <h4 className="font-medium text-foreground">Business Hours</h4>
          <p className="text-muted-foreground">
            Monday - Sunday<br />
            9:00 AM - 8:00 PM
          </p>
        </div>
      </div>
      
      <div className="flex items-start gap-4">
        <div className="mt-1 p-2 bg-primary/10 rounded-lg">
          <Phone className="w-5 h-5 text-primary" />
        </div>
        <div>
          <h4 className="font-medium text-foreground">Phone</h4>
          <a href="tel:+917289000377" className="text-muted-foreground hover:text-primary transition-colors">
            +91-7289000377
          </a>
          <p className="text-sm text-muted-foreground mt-1">Mon-Sun: 9:00 AM - 8:00 PM</p>
        </div>
      </div>
      
      <div className="flex items-start gap-4">
        <div className="mt-1 p-2 bg-primary/10 rounded-lg">
          <Mail className="w-5 h-5 text-primary" />
        </div>
        <div>
          <h4 className="font-medium text-foreground">Email</h4>
          <a href="mailto:help@navdhya.com" className="text-muted-foreground hover:text-primary transition-colors">
            help@navdhya.com
          </a>
          <p className="text-sm text-muted-foreground mt-1">We respond within 24 hours</p>
        </div>
      </div>
    </div>
  )
}

export default ContactInfo