# Navdhya Ritual Services

*Automatically synced with your [v0.app](https://v0.app) deployments*

[![Deployed on Vercel](https://img.shields.io/badge/Deployed%20on-Vercel-black?style=for-the-badge&logo=vercel)](https://vercel.com/kitretsusaisamas-projects/v0-navdhya-ritual-services)
[![Built with v0](https://img.shields.io/badge/Built%20with-v0.app-black?style=for-the-badge)](https://v0.app/chat/projects/3K9jfTCbG8U)

## Overview

Navdhya is a premier spiritual wellness platform connecting families in Delhi NCR with certified Vedic pandits for authentic puja services, astrology consultation, and spiritual guidance. This Next.js 15 application provides a comprehensive solution for booking and managing spiritual services.

## Features

### Robust Loading System
- Global loading context with ref-counting to handle multiple concurrent operations
- Progress bar with smooth animation during navigation
- Preloader with minimum visibility time to prevent flickering
- Automatic loading state during page transitions

### MNC-Grade Design Standards
- Responsive design that works across all device sizes
- Consistent styling using Tailwind CSS
- Modern UI components with proper spacing and typography
- Accessibility considerations for all interactive elements

### Comprehensive Service Catalog
- Detailed puja service pages with descriptions and pricing
- Booking system for scheduling services
- Astrology consultation services
- Filterable service catalog with sorting options

### SEO & Performance
- Proper metadata configuration for all pages
- Structured data markup for rich search results
- Optimized images and assets
- Fast loading times with automatic code splitting

## Project Structure

```
app/                    # Next.js App Router pages
├── (legal)/           # Legal policy pages
├── about/             # About us page
├── booking/           # Service booking page
├── consultation/      # Astrology consultation page
├── contact/           # Contact page
├── pujas/             # Puja service catalog
├── services/          # Service overview pages
├── test-loading/      # Loading system test page
components/            # Reusable UI components
├── sections/          # Page section components
├── ui/                # Primitive UI components
context/               # React context providers
hooks/                 # Custom React hooks
lib/                   # Utility functions and configurations
public/                # Static assets
```

## Key Components

### Loading System
- `context/LoadingContext.tsx` - Global loading state management
- `hooks/useNavigationLoader.tsx` - Automatic loading during navigation
- `components/ui/ProgressBar.tsx` - Visual progress indicator
- `components/ui/Preloader.tsx` - Full-screen loading overlay
- `components/NavigationLoader.tsx` - Combined navigation loading component

### UI Components
- `components/site-header.tsx` - Main navigation header
- `components/site-footer.tsx` - Footer with site information
- `components/sections/hero.tsx` - Homepage hero carousel
- `components/sections/featured-pujas.tsx` - Featured services section

## Deployment

Your project is live at:

**[https://vercel.com/kitretsusaisamas-projects/v0-navdhya-ritual-services](https://vercel.com/kitretsusaisamas-projects/v0-navdhya-ritual-services)**

## Build your app

Continue building your app on:

**[https://v0.app/chat/projects/3K9jfTCbG8U](https://v0.app/chat/projects/3K9jfTCbG8U)**

## How It Works

1. Create and modify your project using [v0.app](https://v0.app)
2. Deploy your chats from the v0 interface
3. Changes are automatically pushed to this repository
4. Vercel deploys the latest version from this repository