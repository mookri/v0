import type { Locale } from "./constants";

/**
 * Translation client. Two provider implementations, selected at runtime:
 *   - DeepL: cheaper, more idiomatic, supports hi-IN, mr-IN (PR 14a)
 *   - Claude (Anthropic): better at scriptural vocabulary (mantras, sanskrit
 *     terms), used as a re-translation pass for the top 200 strings
 *
 * Both clients no-op when the corresponding env var is missing, so the
 * pipeline can be developed and tested without a live API key.
 */

export interface TranslationRequest {
  text: string;
  source: Locale;
  target: Locale;
  /** Optional hint for context — improves Claude's output for short strings. */
  context?: string;
}

export interface TranslationResponse {
  text: string;
  provider: "deepl" | "claude" | "passthrough";
  /** Detected/used source language code. */
  source: string;
  target: string;
}

export type TranslationClient = (req: TranslationRequest) => Promise<TranslationResponse>;

/** Provider env-var keys (without the NEXT_PUBLIC_ prefix). */
export const DEEPL_API_KEY_ENV = "DEEPL_API_KEY";
export const ANTHROPIC_API_KEY_ENV = "ANTHROPIC_API_KEY";

/**
 * DeepL translation client. POST to https://api-free.deepl.com/v2/translate
 * (or https://api.deepl.com/v2/translate for the Pro tier).
 *
 * No-op when DEEPL_API_KEY is not set — returns the source text with
 * provider: 'passthrough' so the pipeline can dry-run.
 */
export const deeplClient: TranslationClient = async (req) => {
  const key = process.env[DEEPL_API_KEY_ENV];
  if (!key) {
    return passthroughResponse(req);
  }
  const url = key.endsWith(":fx")
    ? "https://api-free.deepl.com/v2/translate"
    : "https://api.deepl.com/v2/translate";

  const params = new URLSearchParams();
  params.append("text", req.text);
  params.append("source_lang", req.source.toUpperCase());
  params.append("target_lang", req.target.toUpperCase());

  const res = await fetch(url, {
    method: "POST",
    headers: {
      Authorization: `DeepL-Auth-Key ${key}`,
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: params.toString(),
  });
  if (!res.ok) {
    throw new Error(`DeepL ${res.status}: ${await res.text()}`);
  }
  const json = (await res.json()) as { translations: { text: string; detected_source_language: string }[] };
  const first = json.translations[0];
  if (!first) throw new Error("DeepL: empty response");
  return {
    text: first.text,
    provider: "deepl",
    source: first.detected_source_language,
    target: req.target,
  };
};

/**
 * Claude translation client. Used as a re-translation pass for the top
 * 200 strings where DeepL misses domain context (Sanskrit, ritual terms).
 */
export const claudeClient: TranslationClient = async (req) => {
  const key = process.env[ANTHROPIC_API_KEY_ENV];
  if (!key) {
    return passthroughResponse(req);
  }
  const prompt = [
    `Translate the following UI string from ${req.source} to ${req.target}.`,
    req.context ? `Context: ${req.context}` : "",
    "Preserve any ICU placeholders (e.g. {count}, {price}) verbatim.",
    "Return ONLY the translated text, no quotes or commentary.",
    "",
    req.text,
  ].filter(Boolean).join("\n");

  const res = await fetch("https://api.anthropic.com/v1/messages", {
    method: "POST",
    headers: {
      "x-api-key": key,
      "anthropic-version": "2023-06-01",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      model: "claude-3-5-haiku-20241022",
      max_tokens: 512,
      messages: [{ role: "user", content: prompt }],
    }),
  });
  if (!res.ok) {
    throw new Error(`Anthropic ${res.status}: ${await res.text()}`);
  }
  const json = (await res.json()) as { content: { type: string; text: string }[] };
  const text = json.content.find((c) => c.type === "text")?.text ?? "";
  return {
    text: text.trim(),
    provider: "claude",
    source: req.source,
    target: req.target,
  };
};

function passthroughResponse(req: TranslationRequest): TranslationResponse {
  return { text: req.text, provider: "passthrough", source: req.source, target: req.target };
}

/**
 * Select a provider. Defaults to DeepL; falls back to passthrough when
 * its env var is missing.
 */
export function getDefaultClient(): TranslationClient {
  if (process.env[ANTHROPIC_API_KEY_ENV]) return claudeClient;
  if (process.env[DEEPL_API_KEY_ENV]) return deeplClient;
  return async (req) => passthroughResponse(req);
}
