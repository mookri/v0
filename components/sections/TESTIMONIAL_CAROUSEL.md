# Testimonial Carousel Component

## Overview
The Testimonial Carousel is a minimal, sleek, and responsive component that displays customer testimonials in a horizontal scrolling carousel with infinite looping.

## Features
- Minimalist design with clean typography
- Fully responsive layout
- Infinite looping carousel
- Auto-scrolling with manual controls
- Dot navigation indicators
- Responsive breakpoints for all device sizes
- Smooth CSS transitions

## Design Elements
- Title: "What Families Say About Us"
- Subtitle: "Real experiences from our valued customers who have trusted us with their spiritual ceremonies"
- Left sidebar with rating summary (Excellent, 5-star rating, review count)
- Right carousel area with testimonial cards
- Emerald green star ratings
- Subtle shadows and borders for depth
- Verified user badges
- Navigation arrows (hidden on mobile)
- Dot indicators for slide position

## Testimonials
- 15 unique testimonials with varied content
- Infinite looping by duplicating testimonials
- Each testimonial includes:
  - Title (max 20 characters)
  - Content (max 3 lines)
  - Author name
  - Time ago
  - 5-star rating

## Responsive Behavior
- Mobile (under 640px): 1.2 testimonials per view
- Tablet (640px - 1024px): 2 testimonials per view
- Desktop (1024px and above): 4 testimonials per view

## Technical Implementation
- Uses CSS transforms for smooth sliding animations
- Dynamic calculation of card widths and gaps
- Infinite loop by rendering testimonials 5 times
- Auto-play with 3-second intervals
- Manual navigation controls
- Responsive design with Tailwind CSS
- Properly sized elements matching the reference design

## Customization
The component can be customized by modifying:
- Auto-play interval
- Number of testimonials per view at different breakpoints
- Styling through Tailwind CSS classes
- Testimonial data in the testimonials array

## Usage
The component is used in the Testimonials section on the homepage and can be imported and used in other parts of the application as needed.