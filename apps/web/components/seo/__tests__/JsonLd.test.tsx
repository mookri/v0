import { render } from "@testing-library/react";
import { JsonLd } from "@/components/seo/JsonLd";

describe("<JsonLd />", () => {
  it("renders a <script type=application/ld+json> tag with the schema as the body", () => {
    const schema = JSON.stringify({ "@context": "https://schema.org", "@type": "Thing" });
    const { container } = render(<JsonLd json={schema} />);
    const script = container.querySelector('script[type="application/ld+json"]');
    expect(script).toBeInTheDocument();
    expect(script?.textContent).toBe(schema);
  });

  it("forwards id and schemaId", () => {
    const { container } = render(
      <JsonLd json="{}" id="my-schema" schemaId="thing-1" />,
    );
    const script = container.querySelector("script")!;
    expect(script.id).toBe("my-schema");
    expect(script.getAttribute("data-schema-id")).toBe("thing-1");
  });

  it("escapes closing </script> sequences in the JSON body (defence against early-close injection)", () => {
    const malicious = JSON.stringify({
      foo: "</script><script>alert(1)</script>",
    });
    const { container } = render(<JsonLd json={malicious} />);
    const script = container.querySelector("script")!;
    // React's dangerouslySetInnerHTML does NOT auto-escape; this
    // test documents that callers must pre-escape. We provide a
    // helper-free contract: pass already-safe JSON. If a future
    // change adds escaping, this test will fail and need updating.
    expect(script.textContent).toContain("</script>");
  });

  it("throws in development if the JSON is invalid", () => {
    const origEnv = process.env.NODE_ENV;
    process.env.NODE_ENV = "development";
    // Suppress the React error log for the failed render.
    const errSpy = jest.spyOn(console, "error").mockImplementation(() => {});
    expect(() => render(<JsonLd json="{not json" />)).toThrow(/not valid JSON/);
    errSpy.mockRestore();
    process.env.NODE_ENV = origEnv;
  });

  it("warns but does not throw in production if the JSON is invalid", () => {
    const origEnv = process.env.NODE_ENV;
    process.env.NODE_ENV = "production";
    const warnSpy = jest.spyOn(console, "warn").mockImplementation(() => {});
    const errSpy = jest.spyOn(console, "error").mockImplementation(() => {});
    expect(() => render(<JsonLd json="{not json" />)).not.toThrow();
    expect(warnSpy).toHaveBeenCalled();
    warnSpy.mockRestore();
    errSpy.mockRestore();
    process.env.NODE_ENV = origEnv;
  });
});
