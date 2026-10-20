'use client'
import React, { useState } from 'react';
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { User, Phone, Mail, MessageCircle, Building } from "lucide-react";
import ContactInfo from './contact-info';

const ContactForm = () => {
  const [formData, setFormData] = useState({
    firstName: '',
    lastName: '',
    company: '',
    phone: '',
    email: '',
    subject: '',
    message: ''
  });

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [submitStatus, setSubmitStatus] = useState<'success' | 'error' | null>(null);

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSubmitting(true);
    setSubmitStatus(null);
    
    try {
      // Simulate API call
      await new Promise(resolve => setTimeout(resolve, 1500));
      
      // Reset form
      setFormData({
        firstName: '',
        lastName: '',
        company: '',
        phone: '',
        email: '',
        subject: '',
        message: ''
      });
      
      setSubmitStatus('success');
    } catch (error) {
      setSubmitStatus('error');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="py-16 md:py-24 px-4 sm:px-6 lg:px-8">
      <div className="mx-auto w-full max-w-7xl">
        <div className="flex flex-col items-center gap-12 md:gap-16">
          {/* Header Section */}
          <div className="text-center max-w-3xl">
            <h2 className="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight tracking-tight capitalize font-serif text-foreground">
              Connect with <span className="text-primary">Navdhya</span>
            </h2>
            <p className="mt-4 text-lg text-muted-foreground max-w-2xl mx-auto">
              Reach out to our team for spiritual services, partnerships, or inquiries. 
              We're here to help you on your spiritual journey.
            </p>
          </div>

          <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 md:gap-12 w-full max-w-6xl">
            {/* Contact Information Panel */}
            <div className="lg:col-span-1 bg-card border border-border rounded-xl p-6 md:p-8 h-fit">
              <h3 className="text-xl font-semibold mb-6">Contact Information</h3>
              <ContactInfo />
            </div>

            {/* Contact Form */}
            <div className="lg:col-span-2">
              <div className="bg-card border border-border rounded-xl p-6 md:p-8">
                <form 
                  onSubmit={handleSubmit}
                  className="space-y-6"
                >
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <Label htmlFor="firstName" className="flex items-center gap-2 text-sm font-medium">
                        <User className="w-4 h-4" />
                        First Name *
                      </Label>
                      <Input 
                        id="firstName"
                        name="firstName"
                        maxLength={256}
                        value={formData.firstName}
                        onChange={handleChange}
                        placeholder="Enter your first name"
                        required
                        className="h-12"
                      />
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="lastName" className="flex items-center gap-2 text-sm font-medium">
                        <User className="w-4 h-4" />
                        Last Name *
                      </Label>
                      <Input 
                        id="lastName"
                        name="lastName"
                        maxLength={256}
                        value={formData.lastName}
                        onChange={handleChange}
                        placeholder="Enter your last name"
                        required
                        className="h-12"
                      />
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="space-y-2">
                      <Label htmlFor="company" className="flex items-center gap-2 text-sm font-medium">
                        <Building className="w-4 h-4" />
                        Company / Organization
                      </Label>
                      <Input 
                        id="company"
                        name="company"
                        maxLength={256}
                        value={formData.company}
                        onChange={handleChange}
                        placeholder="Enter your company name"
                        className="h-12"
                      />
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="phone" className="flex items-center gap-2 text-sm font-medium">
                        <Phone className="w-4 h-4" />
                        Phone Number *
                      </Label>
                      <Input 
                        id="phone"
                        name="phone"
                        maxLength={256}
                        value={formData.phone}
                        onChange={handleChange}
                        placeholder="Enter your phone number"
                        type="tel"
                        required
                        className="h-12"
                      />
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="email" className="flex items-center gap-2 text-sm font-medium">
                      <Mail className="w-4 h-4" />
                      Email Address *
                    </Label>
                    <Input 
                      id="email"
                      name="email"
                      maxLength={256}
                      value={formData.email}
                      onChange={handleChange}
                      placeholder="Enter your email address"
                      type="email"
                      required
                      className="h-12"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="subject" className="flex items-center gap-2 text-sm font-medium">
                      <MessageCircle className="w-4 h-4" />
                      Subject *
                    </Label>
                    <Input 
                      id="subject"
                      name="subject"
                      maxLength={256}
                      value={formData.subject}
                      onChange={handleChange}
                      placeholder="What would you like to discuss?"
                      required
                      className="h-12"
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="message" className="flex items-center gap-2 text-sm font-medium">
                      <MessageCircle className="w-4 h-4" />
                      Your Message *
                    </Label>
                    <Textarea 
                      id="message"
                      name="message"
                      maxLength={5000}
                      value={formData.message}
                      onChange={handleChange}
                      placeholder="Share your spiritual needs, questions, or partnership opportunities..."
                      required
                      className="min-h-[160px]"
                    />
                  </div>

                  <div className="pt-4">
                    <Button
                      type="submit"
                      disabled={isSubmitting}
                      className="w-full md:w-auto px-8 py-3 text-base font-medium transition-all hover:shadow-lg"
                    >
                      {isSubmitting ? (
                        <span className="flex items-center gap-2">
                          <span className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent"></span>
                          Sending Message...
                        </span>
                      ) : 'Send Message'}
                    </Button>
                  </div>
                </form>

                {submitStatus === 'success' && (
                  <div 
                    role="region" 
                    aria-label="Form success"
                    className="bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-900 w-full p-5 mt-6 rounded-lg"
                  >
                    <div className="flex items-start gap-3">
                      <div className="mt-0.5 p-1 bg-green-100 dark:bg-green-900/50 rounded-full">
                        <svg className="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                      </div>
                      <div>
                        <h4 className="font-medium text-green-800 dark:text-green-200">Message Sent Successfully!</h4>
                        <p className="text-green-700 dark:text-green-300 mt-1">
                          Thank you for reaching out. Our team will get back to you within 24 hours.
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                {submitStatus === 'error' && (
                  <div 
                    role="region" 
                    aria-label="Form failure"
                    className="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 w-full p-5 mt-6 rounded-lg"
                  >
                    <div className="flex items-start gap-3">
                      <div className="mt-0.5 p-1 bg-red-100 dark:bg-red-900/50 rounded-full">
                        <svg className="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                      </div>
                      <div>
                        <h4 className="font-medium text-red-800 dark:text-red-200">Error Sending Message</h4>
                        <p className="text-red-700 dark:text-red-300 mt-1">
                          Something went wrong while sending your message. Please try again or contact us directly.
                        </p>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ContactForm;