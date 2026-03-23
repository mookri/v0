import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/seo/site";

export function buildRobotsPolicy(): MetadataRoute.Robots {
  return {
    rules: [
      {
        userAgent: "*",
        allow: ["/", "/favicon/", "/favicon/site.webmanifest"],
        disallow: [
          "/api/",
          "/auth/",
          "/drafts/",
          "/admin/",
          "/_next/webpack-hmr",
        ],
      },
      {
        userAgent: "Googlebot",
        allow: "/",
        disallow: ["/api/", "/auth/", "/drafts/", "/admin/"],
      },
      {
        userAgent: "Bingbot",
        allow: "/",
        disallow: ["/api/", "/auth/", "/drafts/", "/admin/"],
      },
    ],
    sitemap: [`${SITE_URL}/sitemap.xml`],
    host: SITE_URL,
  };
}
