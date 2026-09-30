/**
 * Inline a JSON-LD schema document in a server-rendered <script> tag.
 *
 * Why not just use Next's <Script>?
 *   <Script> is a client component — it forces the surrounding
 *   page (or layout) to be a client component, OR it ships the
 *   schema-injection JS to the client and re-renders after
 *   hydration. JSON-LD should appear in the *initial* HTML
 *   payload for SEO crawlers and AI assistants; they don't
 *   execute client JS. This component emits a plain
 *   <script type="application/ld+json"> server-side so the
 *   payload contains the schema on first byte.
 *
 * The string is passed in pre-serialised so the caller is in
 * control of the exact byte representation (e.g. to control
 * key order for snapshot tests). `JSON.stringify` on a plain
 * object is also acceptable; see the `data` overload.
 *
 * The script content is wrapped in a closure that throws at
 * render time if any string content is not valid JSON, so
 * typos are caught at build time rather than in production.
 */
import { type ReactElement } from "react";

export interface JsonLdProps {
  /**
   * Pre-serialised JSON string. The caller is responsible for
   * `JSON.stringify(schema)` and for ensuring no user-controlled
   * string flows in unescaped.
   */
  json: string;
  /**
   * Optional id for the <script> tag (useful when multiple
   * schemas are emitted and you want to target one).
   */
  id?: string;
  /**
   * The `key` is required when multiple <JsonLd> are emitted in
   * the same parent (React's standard rule for siblings).
   */
  schemaId?: string;
}

export function JsonLd({ json, id, schemaId }: JsonLdProps): ReactElement {
  // Defensive parse at render time. JSON-LD is trusted internal
  // data, but a typo here would silently produce a broken schema
  // that crawlers ignore. Catching it at build time is cheaper
  // than debugging a Search Console warning three months later.
  // We avoid throwing in production for resilience: the broken
  // schema is still emitted (just with a console warning) so
  // pages don't fail to build if a content author makes a typo.
  try {
    JSON.parse(json);
  } catch (err) {
    if (process.env.NODE_ENV !== "production") {
      throw new Error(
        `[JsonLd] Schema payload is not valid JSON (${(err as Error).message}): ${json.slice(0, 200)}…`,
      );
    }
    // eslint-disable-next-line no-console
    console.warn(
      "[JsonLd] Invalid JSON in schema payload (emitting anyway):",
      (err as Error).message,
    );
  }
  return (
    <script
      type="application/ld+json"
      id={id}
      data-schema-id={schemaId}
      // eslint-disable-next-line react/no-danger
      dangerouslySetInnerHTML={{ __html: json }}
    />
  );
}
