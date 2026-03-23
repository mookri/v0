interface SchemaMarkupProps {
  schemas: Record<string, unknown>[];
}

function serializeSchema(schema: Record<string, unknown>): string {
  return JSON.stringify(schema).replace(/</g, "\\u003c");
}

export function SchemaMarkup({ schemas }: SchemaMarkupProps) {
  const valid = (schemas ?? []).filter(
    (schema): schema is Record<string, unknown> => schema !== null && schema !== undefined,
  );

  if (valid.length === 0) return null;

  return (
    <>
      {valid.map((schema, index) => (
        <script
          key={`ld-${index}`}
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: serializeSchema(schema) }}
        />
      ))}
    </>
  );
}
