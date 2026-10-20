// lib/seo/robots.ts
// robots.txt policy.
//
// Allow major search engines plus AI answer engines (GPTBot, ClaudeBot,
// PerplexityBot, CCBot, etc.) so they can ingest and cite our content
// in AI Overviews and assistant answers.
//
// The list of AI user-agents is based on the published documentation of
// each vendor as of 2026-06. Update when new bots are announced.

import type { MetadataRoute } from "next";
import { SITE_URL } from "@/lib/seo/site";

const AI_BOT_USER_AGENTS = [
  "GPTBot",
  "ChatGPT-User",
  "OAI-SearchBot",
  "ClaudeBot",
  "Claude-Web",
  "Claude-SearchBot",
  "PerplexityBot",
  "Perplexity-User",
  "Google-Extended",
  "CCBot",
  "Amazonbot",
  "Applebot-Extended",
  "Meta-ExternalAgent",
  "MistralAI-User",
  "Cohere-AI",
  "DuckAssistBot",
  "YouBot",
] as const;

const SENSITIVE_PATHS = [
  "/api/",
  "/auth/",
  "/dashboard/",
  "/drafts/",
  "/admin/",
  "/platform/",
  "/user/",
  "/pandit/",
  "/vendor/",
  "/manager/",
  "/_next/",
  "/webpack-hmr",
] as const;

export function buildRobotsPolicy(): MetadataRoute.Robots {
  const baseRule = {
    userAgent: "*",
    allow: ["/"],
    disallow: [...SENSITIVE_PATHS, "/favicon/"],
  };

  const aiBotRules = AI_BOT_USER_AGENTS.map((userAgent) => ({
    userAgent,
    allow: ["/", "/llms.txt", "/llms-full.txt"],
    disallow: [...SENSITIVE_PATHS],
  }));

  return {
    rules: [baseRule, ...aiBotRules],
    sitemap: [
      `${SITE_URL}/sitemap.xml`,
      `${SITE_URL}/llms.txt`,
      `${SITE_URL}/llms-full.txt`,
    ],
    host: SITE_URL,
  };
}
