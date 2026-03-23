// lib/content-loader.ts
import fs from 'fs';
import path from 'path';

/**
 * MNC-Grade Content Loader
 * Resolves content from the registry and filesystem for high-performance production builds.
 */
export async function getContentPage(slug: string) {
  const root = process.cwd();
  const registryPath = path.join(root, 'content/registry.json');
  const contentDir = path.join(root, 'content/pages');

  try {
    if (!fs.existsSync(registryPath)) return null;

    const registry = JSON.parse(fs.readFileSync(registryPath, 'utf-8'));
    const pageEntry = registry.pages[slug];

    if (!pageEntry) return null;

    const filePath = path.join(contentDir, pageEntry.file);
    if (!fs.existsSync(filePath)) return null;

    return JSON.parse(fs.readFileSync(filePath, 'utf-8'));
  } catch (error) {
    console.error(`[ContentLoader] Error loading page ${slug}:`, error);
    return null;
  }
}

export async function getAllSlugs() {
  const root = process.cwd();
  const registryPath = path.join(root, 'content/registry.json');

  try {
    if (!fs.existsSync(registryPath)) return [];
    const registry = JSON.parse(fs.readFileSync(registryPath, 'utf-8'));
    return Object.keys(registry.pages);
  } catch (error) {
    return [];
  }
}
