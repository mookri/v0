'use client'

import React from 'react'
import { MapPin, Clock } from "lucide-react"
import ContactInfo from './contact-info'

const ContactMap = () => {
  return (
    <div className="py-16 md:py-24 px-4 sm:px-6 lg:px-8 bg-muted/50">
      <div className="mx-auto w-full max-w-7xl">
        <div className="text-center mb-12 md:mb-16">
          <h2 className="text-3xl md:text-4xl font-bold tracking-tight text-foreground">
            Visit Our <span className="text-primary">Office</span>
          </h2>
          <p className="mt-4 text-lg text-muted-foreground max-w-2xl mx-auto">
            We're located in the heart of New Delhi, ready to assist you with your spiritual needs.
          </p>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
          {/* Map Container */}
          <div className="lg:col-span-2 bg-card border border-border rounded-xl overflow-hidden shadow-sm">
            <div className="w-full h-96 bg-muted relative">
              {/* Placeholder for map - in a real app, this would be an actual map component */}
              <div className="absolute inset-0 flex items-center justify-center">
                <div className="text-center">
                  <MapPin className="w-12 h-12 text-primary mx-auto mb-4" />
                  <h3 className="text-xl font-semibold text-foreground mb-2">Navdhya Office</h3>
                  <p className="text-muted-foreground">Pandav Nagar, New Delhi</p>
                  <p className="text-muted-foreground mt-1">110092, India</p>
                </div>
              </div>
              
              {/* Map overlay with branding */}
              <div className="absolute top-4 left-4 bg-primary text-primary-foreground px-3 py-2 rounded-lg font-medium text-sm">
                Navdhya
              </div>
            </div>
          </div>

          {/* Contact Information */}
          <div className="bg-card border border-border rounded-xl p-6 md:p-8 h-fit">
            <h3 className="text-xl font-semibold mb-6">Our Location</h3>
            <ContactInfo />
            
            <div className="mt-8 pt-6 border-t border-border">
              <h4 className="font-medium text-foreground mb-3">Directions</h4>
              <p className="text-muted-foreground text-sm">
                Our office is conveniently located in Pandav Nagar, easily accessible by public transport. 
                Free parking is available for visitors.
              </p>
              <div className="mt-4 flex items-center text-sm text-muted-foreground">
                <Clock className="w-4 h-4 mr-2" />
                <span>Nearest Metro: Laxmi Nagar (1.2 km)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ContactMap