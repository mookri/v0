import { render, screen } from "@testing-library/react";
import AutoAdRenderer from "@/components/ad-network/AutoAdRenderer";
import type { Ad } from "@/components/ad-network/core/types";

const ad: Ad = {
  id: "auto-render-ad",
  type: "native",
  title: "Navdhya Priest Network",
  description: "Background-checked and tradition-certified pandits for home and online ceremonies.",
  ctaText: "Explore",
  ctaUrl: "/services",
  sponsorName: "Navdhya",
  verified: true,
  layouts: ["medium_rectangle", "native_in_content", "inline_text", "carousel_story"],
  placements: ["sidebar_top", "article_inline_1", "feed_sponsored_story"],
  creative: {
    imageUrl: "/traditional-puja-ceremony-setup.jpg",
    thumbnails: [
      "/traditional-puja-ceremony-setup.jpg",
      "/traditional-puja-ceremony-mandap.jpg",
      "/hindu-puja-ceremony-peaceful-home-setting.jpg",
    ],
    accentColor: "#f2cc66",
  },
};

describe("AutoAdRenderer", () => {
  it("renders poster-style layouts with sponsor metadata and CTA", () => {
    render(<AutoAdRenderer ad={ad} layout="medium_rectangle" />);

    expect(screen.getByText("Navdhya Priest Network")).toBeInTheDocument();
    expect(screen.getByText("Explore")).toBeInTheDocument();
    expect(screen.getByText("Navdhya")).toBeInTheDocument();
  });

  it("renders split editorial layouts for in-content units", () => {
    render(<AutoAdRenderer ad={ad} layout="native_in_content" />);

    expect(screen.getByText("Background-checked and tradition-certified pandits for home and online ceremonies.")).toBeInTheDocument();
    expect(screen.getByText("Verified")).toBeInTheDocument();
  });

  it("renders carousel layouts with repeated frames", () => {
    render(<AutoAdRenderer ad={ad} layout="carousel_story" />);

    expect(screen.getAllByRole("img").length).toBeGreaterThanOrEqual(3);
  });
});
