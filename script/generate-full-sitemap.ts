import * as glob from 'glob';
import fs from 'fs';
import path from 'path';
import { format } from 'date-fns';

// Configurable options
const BASE_URL = 'https://www.navdhya.com';
const EXCLUDED_PATTERNS = [
  'app/api/**',
  'app/_*/**',
  'app/**/layout.tsx',
  'app/**/loading.tsx',
  'app/**/error.tsx',
  'app/**/not-found.tsx',
  'app/**/route.ts',
  'app/**/route.tsx',
  'app/**/*.test.tsx',
];

type SitemapEntry = {
  url: string;
  lastModified?: string;
  changeFrequency?: 'always' | 'hourly' | 'daily' | 'weekly' | 'monthly' | 'yearly' | 'never';
  priority?: number;
};

// Enhanced normalization: remove 'app/', leading 'src/', and trailing '/page.tsx' or '\page.tsx'
const normalizePathForUrl = (pagePath: string): string => {
  let result = pagePath
    .replace(/^src[\\\/]/, '')
    .replace(/^app[\\\/]/, '/')
    .replace(/[\\\/]page\.tsx$/, '')
    .replace(/\\/g, '/');

  // Handle Next.js parallel routes (remove parentheses but keep the content as part of the path)
  // Convert app/(legal)/privacy-policy to /privacy-policy (not /legal/privacy-policy)
  result = result.replace(/\/\(([^)]+)\)\//g, '/'); // Remove parentheses but keep directory structure
  result = result.replace(/\/\(([^)]+)\)$/g, ''); // Remove parentheses at the end

  // Handle dynamic segments [param] -> :param
  result = result.replace(/\[([^\]]+)\]/g, ':$1');

  // Handle catch-all routes [...param] -> *
  result = result.replace(/\[\.\.\.[^\]]+\]/g, '*');

  // Handle optional catch-all routes [[...param]] -> *
  result = result.replace(/\[\[\.\.\.[^\]]+\]\]/g, '*');

  // Ensure root path is just '/'
  if (result === '') {
    return '/';
  }

  // Fix paths that start with double slashes
  if (result.startsWith('//')) {
    result = result.substring(1);
  }

  return result;
};

const getLastModifiedDate = (filePath: string): string => {
  try {
    const stats = fs.statSync(filePath);
    return format(stats.mtime, 'yyyy-MM-dd');
  } catch (e) {
    return format(new Date(), 'yyyy-MM-dd');
  }
};

const calculatePriority = (path: string): number => {
  if (path === '/') return 1.0;
  
  // Special priorities for important pages
  if (path === '/services' || path === '/pujas') return 0.9;
  if (path === '/about' || path === '/contact') return 0.8;
  if (path.includes('policy') || path.includes('terms') || path.includes('refund') || path.includes('cancellation')) return 0.6;
  
  // Decrease priority as path depth increases
  const depth = path.split('/').filter(Boolean).length;
  return Math.max(0.1, Math.min(0.9, 1.0 - (depth * 0.1)));
};

const guessChangeFrequency = (path: string): SitemapEntry['changeFrequency'] => {
  if (path === '/') return 'daily';
  if (path.includes('blog') || path.includes('news') || path.includes('posts')) return 'daily';
  if (path.includes('pujas') || path.includes('services')) return 'weekly';
  if (path.includes('about') || path.includes('contact')) return 'monthly';
  if (path.includes('policy') || path.includes('terms') || path.includes('refund') || path.includes('cancellation')) return 'yearly';
  return 'weekly';
};

const generateSitemapEntryXml = (entry: SitemapEntry): string => {
  let xml = `  <url>\n    <loc>${entry.url}</loc>\n`;
  
  if (entry.lastModified) {
    xml += `    <lastmod>${entry.lastModified}</lastmod>\n`;
  }
  
  if (entry.changeFrequency) {
    xml += `    <changefreq>${entry.changeFrequency}</changefreq>\n`;
  }
  
  if (entry.priority !== undefined) {
    xml += `    <priority>${entry.priority.toFixed(1)}</priority>\n`;
  }
  
  xml += '  </url>\n';
  return xml;
};

const generateSitemapXml = (entries: SitemapEntry[]): string => {
  return `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${entries.map((entry: SitemapEntry) => generateSitemapEntryXml(entry)).join('')}</urlset>`;
};

// Main function to generate sitemap
const generateSitemap = () => {
  console.log('🔍 Discovering pages...');
  
  // Find all page.tsx files
  const pageFiles = glob.sync('app/**/page.tsx', {
    ignore: EXCLUDED_PATTERNS,
  });
  
  console.log(`📄 Found ${pageFiles.length} page files`);
  
  // Convert to sitemap entries
  const entries: SitemapEntry[] = [];
  
  // Add static pages
  for (const file of pageFiles) {
    const normalizedPath = normalizePathForUrl(file);
    
    // Skip dynamic routes for now (we'll handle them separately)
    if (normalizedPath.includes(':') || normalizedPath.includes('*')) {
      continue;
    }
    
    const url = `${BASE_URL}${normalizedPath}`;
    const filePath = path.join(process.cwd(), file);
    const lastModified = getLastModifiedDate(filePath);
    const changeFrequency = guessChangeFrequency(normalizedPath);
    const priority = calculatePriority(normalizedPath);
    
    entries.push({
      url,
      lastModified,
      changeFrequency,
      priority
    });
  }
  
  // Add specific dynamic routes
  // Puja slugs
  const pujaSlugs = [
    'griha-pravesh',
    'satyanarayan-katha',
    'lakshmi-ganesh-puja',
    'hindu-wedding',
    'baby-naming',
    'annaprashan',
    'vastu-shanti',
    'navagraha-puja'
  ];
  
  for (const slug of pujaSlugs) {
    entries.push({
      url: `${BASE_URL}/pujas/${slug}`,
      lastModified: format(new Date(), 'yyyy-MM-dd'),
      changeFrequency: 'weekly',
      priority: 0.8
    });
  }
  
  console.log(`📝 Processing ${entries.length} sitemap entries`);
  
  // Generate sitemap
  const sitemapXml = generateSitemapXml(entries);
  
  // Write to public directory
  const publicDir = path.join(process.cwd(), 'public');
  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
  }
  
  fs.writeFileSync(path.join(publicDir, 'sitemap.xml'), sitemapXml);
  console.log('✅ Sitemap generated successfully at public/sitemap.xml');
};

generateSitemap();