import type { MetadataRoute } from "next";
import { buildRobotsPolicy } from "@/lib/seo/robots";

export default function robots(): MetadataRoute.Robots {
  return buildRobotsPolicy();
}
