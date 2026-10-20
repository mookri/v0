import { render, screen } from "@testing-library/react";
import { StatusBadge, KNOWN_STATUSES } from "@/components/dashboard/StatusBadge";

describe("StatusBadge", () => {
  describe("variant derivation", () => {
    test("confirmed maps to success", () => {
      const { container } = render(<StatusBadge status="confirmed" />);
      const span = container.querySelector("span")!;
      expect(span.className).toContain("badge-success");
      expect(span.getAttribute("data-variant")).toBe("success");
      expect(span.getAttribute("data-status")).toBe("confirmed");
    });

    test("pending maps to warning", () => {
      const { container } = render(<StatusBadge status="pending" />);
      expect(container.querySelector("span")!.className).toContain("badge-warning");
    });

    test("new maps to info", () => {
      const { container } = render(<StatusBadge status="new" />);
      expect(container.querySelector("span")!.className).toContain("badge-info");
    });

    test("cancelled maps to danger", () => {
      const { container } = render(<StatusBadge status="cancelled" />);
      expect(container.querySelector("span")!.className).toContain("badge-danger");
    });

    test("draft maps to neutral", () => {
      const { container } = render(<StatusBadge status="draft" />);
      expect(container.querySelector("span")!.className).toContain("badge-neutral");
    });
  });

  describe("variant override (F-FE-09 fix)", () => {
    test("pending can be forced to info", () => {
      const { container } = render(
        <StatusBadge status="pending" variant="info" />,
      );
      const span = container.querySelector("span")!;
      expect(span.className).toContain("badge-info");
      expect(span.className).not.toContain("badge-warning");
      expect(span.getAttribute("data-variant")).toBe("info");
    });

    test("approved can be forced to neutral", () => {
      const { container } = render(
        <StatusBadge status="approved" variant="neutral" />,
      );
      expect(container.querySelector("span")!.className).toContain("badge-neutral");
    });
  });

  describe("unknown status", () => {
    test("unknown string falls back to neutral", () => {
      const { container } = render(<StatusBadge status="banana" />);
      const span = container.querySelector("span")!;
      expect(span.className).toContain("badge-neutral");
      expect(span.getAttribute("data-status")).toBe("banana");
    });

    test("empty string is gracefully rendered", () => {
      const { container } = render(<StatusBadge status="" />);
      const span = container.querySelector("span")!;
      expect(span.className).toContain("badge-neutral");
      expect(span.textContent).toBe("");
    });
  });

  describe("label formatting", () => {
    test("capitalizes first letter", () => {
      render(<StatusBadge status="confirmed" />);
      expect(screen.getByText("Confirmed")).toBeInTheDocument();
    });

    test("splits on dashes and underscores", () => {
      render(<StatusBadge status="out-for-delivery" />);
      expect(screen.getByText("Out For Delivery")).toBeInTheDocument();
    });

    test("title-cases each word", () => {
      render(<StatusBadge status="in_review" />);
      expect(screen.getByText("In Review")).toBeInTheDocument();
    });
  });

  describe("size", () => {
    test("sm size applies tighter padding/text", () => {
      const { container } = render(
        <StatusBadge status="pending" size="sm" />,
      );
      const cls = container.querySelector("span")!.className;
      expect(cls).toContain("text-[0.625rem]");
    });

    test("md size (default) applies standard padding", () => {
      const { container } = render(<StatusBadge status="pending" />);
      const cls = container.querySelector("span")!.className;
      expect(cls).toContain("text-xs");
    });
  });

  describe("icon", () => {
    test("renders icon before label when provided", () => {
      render(
        <StatusBadge
          status="confirmed"
          icon={<span data-testid="icon">✓</span>}
        />,
      );
      expect(screen.getByTestId("icon")).toBeInTheDocument();
      expect(screen.getByText("Confirmed")).toBeInTheDocument();
    });

    test("no icon wrapper when not provided", () => {
      const { container } = render(<StatusBadge status="confirmed" />);
      expect(container.querySelector("[data-testid='icon']")).toBeNull();
    });
  });

  describe("className merge", () => {
    test("appends user className", () => {
      const { container } = render(
        <StatusBadge status="confirmed" className="ml-2" />,
      );
      const cls = container.querySelector("span")!.className;
      expect(cls).toContain("badge-success");
      expect(cls).toContain("ml-2");
    });
  });

  describe("KNOWN_STATUSES", () => {
    test("contains 24 curated statuses", () => {
      expect(KNOWN_STATUSES.length).toBe(24);
    });
    test("no duplicates", () => {
      const set = new Set(KNOWN_STATUSES);
      expect(set.size).toBe(KNOWN_STATUSES.length);
    });
  });
});
