import fs from "fs";
import path from "path";
import { buildSitemapEntries, generateSitemapXml } from "@/lib/seo/sitemap";

function generateSitemap() {
  const entries = buildSitemapEntries();
  const sitemapXml = generateSitemapXml(entries);
  const publicDir = path.join(process.cwd(), "public");
  const outputPath = path.join(publicDir, "sitemap-generated.xml");

  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
  }

  fs.writeFileSync(outputPath, sitemapXml, "utf8");
  console.log(`Sitemap snapshot generated with ${entries.length} URLs at ${outputPath}`);
}

generateSitemap();
