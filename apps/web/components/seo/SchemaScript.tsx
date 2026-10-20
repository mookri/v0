import Script from 'next/script';

interface SchemaScriptProps {
  schemas: Record<string, unknown>[];
}

/**
 * Injects JSON-LD structured data scripts into the page.
 *
 * - First schema is injected with `beforeInteractive` for fastest Google pickup.
 * - Remaining schemas use `afterInteractive` to avoid render-blocking.
 * - Defensively filters out any null/undefined entries in case caller
 *   passes a partially-populated array.
 */
export function SchemaScript({ schemas }: SchemaScriptProps) {
  // Guard: filter nullish entries (should never happen after generator.ts fix,
  // but this provides a second line of defence)
  const valid = (schemas ?? []).filter(
    (s): s is Record<string, unknown> => s !== null && s !== undefined,
  );

  if (valid.length === 0) return null;

  return (
    <>
      {valid.map((schema, i) => {
        let jsonString: string;
        try {
          jsonString = JSON.stringify(schema);
        } catch {
          // Circular reference or non-serialisable value — skip this entry
          console.warn(`[SchemaScript] Failed to serialise schema at index ${i}`, schema);
          return null;
        }

        return (
          <Script
            key={`ld-${i}`}
            id={`ld-${i}`}
            type="application/ld+json"
            strategy={i === 0 ? 'beforeInteractive' : 'afterInteractive'}
            dangerouslySetInnerHTML={{ __html: jsonString }}
          />
        );
      })}
    </>
  );
}