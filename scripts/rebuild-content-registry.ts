import fs from 'fs';
import path from 'path';

const root = path.join(process.cwd(), 'apps/web');
const pagesDir = path.join(root, 'content/pages');
const registryPath = path.join(root, 'content/registry.json');

const clusters = {
  brand: { label: "Inside the Brand", basePath: "explore" },
  pujas: { label: "Delhi NCR Puja Services", basePath: "pujas" },
  services: { label: "Puja Services", basePath: "services" },
  ceremonies: { label: "Hindu Ceremonies", basePath: "ceremonies" },
  "ritual-guides": { label: "Ritual Guides", basePath: "ritual-guides" },
  "pandit-services": { label: "Pandit Services", basePath: "pandit-services" },
  "festival-rituals": { label: "Festival Rituals", basePath: "festival-rituals" },
  "spiritual-guides": { label: "Spiritual Guides", basePath: "spiritual-guides" },
  faq: { label: "Frequently Asked Questions", basePath: "faq" },
  blog: { label: "Navdhya Journal", basePath: "blog" },
  compare: { label: "Delhi NCR Comparisons", basePath: "compare" },
  centers: { label: "Service Centers", basePath: "centers" },
  astrology: { label: "Astrology Guides", basePath: "astrology" },
  vastu: { label: "Vastu Guides", basePath: "vastu" },
};

const pages: Record<string, { file: string; cluster: string }> = {};

if (fs.existsSync(pagesDir)) {
  const files = fs.readdirSync(pagesDir)
    .filter(file => file.endsWith('.json'))
    .sort();

  for (const file of files) {
    const filePath = path.join(pagesDir, file);
    try {
      const content = fs.readFileSync(filePath, 'utf-8');
      const page = JSON.parse(content);

      if (!page.slug || !page.cluster) {
        continue;
      }

      pages[page.slug] = {
        file,
        cluster: page.cluster,
      };
    } catch (err) {
      console.error(`Error processing file ${file}:`, err);
    }
  }
} else {
  console.warn(`Warning: Directory not found at ${pagesDir}`);
}

const registry = {
  clusters,
  pages,
};

fs.writeFileSync(registryPath, JSON.stringify(registry, null, 2), { encoding: 'utf-8' });
console.log(`Registry rebuilt with ${Object.keys(pages).length} pages.`);
