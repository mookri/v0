import { createHash } from "crypto";
import type { MetadataRoute } from "next";

/**
 * Generates a deterministic, weak ETag for a sitemap chunk.
 * Weak because the XML serialisation may vary without semantic change.
 *
 * W/"<sha256[:12]>-<builtAt>"
 */
export function buildETag(
  chunk: MetadataRoute.Sitemap,
  builtAt: number,
): string {
  const fingerprint = createHash("sha256")
    .update(JSON.stringify(chunk))
    .digest("hex")
    .slice(0, 12);

  return `W/"${fingerprint}-${builtAt}"`;
}

/**
 * Returns true when the client's If-None-Match header matches our ETag,
 * meaning the response has not changed and a 304 can be sent.
 */
export function isETagMatch(
  ifNoneMatch: string | null,
  etag: string,
): boolean {
  if (!ifNoneMatch) return false;
  // The header may contain a comma-separated list of ETags.
  return ifNoneMatch.split(",").some((t) => t.trim() === etag);
}
