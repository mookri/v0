import type { AdPlacement } from "@/components/ad-network/core/types";
import type {
  OpportunityDesignIntent,
  PlacementOpportunity,
  PlacementPlan,
  StructuredContentBlock,
  TocItem,
} from "./types";

const TOKEN_RE = /(<h[23][^>]*>[\s\S]*?<\/h[23]>)|(<p[\s\S]*?<\/p>)|(<ul[\s\S]*?<\/ul>)|(<ol[\s\S]*?<\/ol>)|(<img[\s\S]*?>)/gi;

function stripHtml(html: string): string {
  return html.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
}

function buildId(text: string): string {
  return text
    .toLowerCase()
    .replace(/[^\w\s-]/g, "")
    .replace(/\s+/g, "-")
    .slice(0, 60);
}

function reservedSizeForPlacement(placement: AdPlacement) {
  if (placement.startsWith("sidebar")) {
    return { width: 300, height: 250, aspectRatio: "6:5" as const };
  }
  return { width: "fluid" as const, height: 180, aspectRatio: "4:3" as const };
}

type InlineOpportunityProfile = {
  designIntent: OpportunityDesignIntent;
  preferredFamilies: PlacementOpportunity["preferredFamilies"];
  preferredTemplates: PlacementOpportunity["preferredTemplates"];
  reservedSize: PlacementOpportunity["reservedSize"];
};

const ARTICLE_INLINE_PROFILES: Record<"editorial" | "compact" | "immersive", InlineOpportunityProfile> = {
  editorial: {
    designIntent: "editorial",
    preferredFamilies: ["native", "sponsored", "display"],
    preferredTemplates: ["split", "poster"],
    reservedSize: { width: "fluid", height: 196, aspectRatio: "16:7" },
  },
  compact: {
    designIntent: "compact",
    preferredFamilies: ["display", "sponsored", "fallback", "native"],
    preferredTemplates: ["ribbon", "ticker", "split"],
    reservedSize: { width: "fluid", height: 132, aspectRatio: "12:3" },
  },
  immersive: {
    designIntent: "immersive",
    preferredFamilies: ["story", "carousel", "interactive", "native", "commerce"],
    preferredTemplates: ["story", "carousel", "interactive", "poster"],
    reservedSize: { width: "fluid", height: 284, aspectRatio: "16:9" },
  },
};

function depthBucketForIndex(index: number, total: number): PlacementOpportunity["depthBucket"] {
  const ratio = total === 0 ? 0 : index / total;
  if (ratio <= 0.33) return "above_fold";
  if (ratio <= 0.7) return "mid";
  return "deep";
}

export function createCompatibilityOpportunity(slotId: string, placement: AdPlacement): PlacementOpportunity {
  const articleProfile =
    placement === "article_inline_1"
      ? ARTICLE_INLINE_PROFILES.editorial
      : placement === "article_inline_2"
        ? ARTICLE_INLINE_PROFILES.compact
        : placement === "article_inline_3"
          ? ARTICLE_INLINE_PROFILES.immersive
          : undefined;

  return {
    slotId,
    surface: placement.startsWith("sidebar") ? "sidebar" : placement === "homepage_hero" || placement === "article_top" ? "hero" : "article",
    placementHints: [placement],
    anchorId: slotId,
    anchorIndex: 0,
    reservedSize: articleProfile?.reservedSize ?? reservedSizeForPlacement(placement),
    depthBucket: placement.startsWith("sidebar") ? "above_fold" : "mid",
    lazy: placement !== "article_top" && placement !== "homepage_hero",
    designIntent: articleProfile?.designIntent,
    preferredFamilies: articleProfile?.preferredFamilies,
    preferredTemplates: articleProfile?.preferredTemplates,
  };
}

function buildBlocks(html: string): { blocks: StructuredContentBlock[]; toc: TocItem[] } {
  const blocks: StructuredContentBlock[] = [];
  const toc: TocItem[] = [];
  let match: RegExpExecArray | null;
  let paragraphIndex = 0;
  let afterHeading = false;

  while ((match = TOKEN_RE.exec(html)) !== null) {
    const blockHtml = match[0];

    if (match[1]) {
      const headingMatch = blockHtml.match(/^<h([23])[^>]*>([\s\S]*?)<\/h\1>$/i);
      const level = Number(headingMatch?.[1] ?? 2) as 2 | 3;
      const text = stripHtml(headingMatch?.[2] ?? blockHtml);
      const id = buildId(text);
      toc.push({ level, text, id });
      blocks.push({
        id,
        type: "heading",
        html: blockHtml.replace(/^<h([23])([^>]*)>/i, `<h$1$2 id="${id}">`),
        text,
        wordCount: text.split(/\s+/).filter(Boolean).length,
        paragraphIndex,
        headingLevel: level,
        isAfterHeading: false,
      });
      afterHeading = true;
      continue;
    }

    const text = stripHtml(blockHtml);
    if (!text) continue;

    const type = match[2] ? "paragraph" : match[5] ? "media" : "list";
    if (type === "paragraph") {
      paragraphIndex += 1;
    }

    blocks.push({
      id: `${type}-${blocks.length}`,
      type,
      html: blockHtml,
      text,
      wordCount: text.split(/\s+/).filter(Boolean).length,
      paragraphIndex,
      isAfterHeading: afterHeading,
    });

    afterHeading = false;
  }

  return { blocks, toc };
}

function scoreParagraphOpportunity(block: StructuredContentBlock, totalParagraphs: number): number {
  const densityScore = Math.min(1, block.wordCount / 110);
  const depthScore = block.paragraphIndex / Math.max(1, totalParagraphs);
  const headingPenalty = block.isAfterHeading ? 0.18 : 0;
  return densityScore * 0.45 + depthScore * 0.25 + (1 - headingPenalty) * 0.3;
}

function inlineProfileForPosition(position: number, totalInlineAds: number): InlineOpportunityProfile {
  if (totalInlineAds <= 1) return ARTICLE_INLINE_PROFILES.editorial;
  if (totalInlineAds === 2) {
    return position === 1 ? ARTICLE_INLINE_PROFILES.editorial : ARTICLE_INLINE_PROFILES.immersive;
  }

  if (position === 1) return ARTICLE_INLINE_PROFILES.editorial;
  if (position === 2) return ARTICLE_INLINE_PROFILES.compact;
  return ARTICLE_INLINE_PROFILES.immersive;
}

function targetInlineAds(totalParagraphs: number, wordCount: number): number {
  if (wordCount >= 1350 || totalParagraphs >= 8) return 3;
  if (wordCount >= 650 || totalParagraphs >= 5) return 2;
  return 1;
}

function minimumParagraphGap(totalInlineAds: number, totalParagraphs: number): number {
  if (totalInlineAds >= 3) return totalParagraphs >= 9 ? 3 : 2;
  if (totalParagraphs >= 7) return 3;
  return 2;
}

export class PlacementEngine {
  buildArticlePlan(html: string): PlacementPlan {
    const { blocks, toc } = buildBlocks(html);
    const paragraphBlocks = blocks.filter((block) => block.type === "paragraph");
    const totalParagraphs = paragraphBlocks.length;
    const wordCount = blocks.reduce((sum, block) => sum + block.wordCount, 0);
    const maxInlineAds = targetInlineAds(totalParagraphs, wordCount);
    const coreCandidates = paragraphBlocks
      .filter((block) => block.paragraphIndex > 1 && block.paragraphIndex < totalParagraphs);
    const paragraphGap = minimumParagraphGap(maxInlineAds, totalParagraphs);

    const strictCandidates = coreCandidates
      .filter((block) => block.wordCount >= 55)
      .map((block) => ({
        block,
        score: scoreParagraphOpportunity(block, totalParagraphs),
      }));

    const fallbackCandidates = coreCandidates
      .filter((block) => block.wordCount >= 42)
      .map((block) => ({
        block,
        score: scoreParagraphOpportunity(block, totalParagraphs) - 0.06,
      }));

    const candidates = [...strictCandidates, ...fallbackCandidates]
      .filter((candidate, index, list) => list.findIndex((entry) => entry.block.id === candidate.block.id) === index)
      .sort((left, right) => right.score - left.score);

    const selectedCandidates: Array<{
      block: StructuredContentBlock;
      score: number;
    }> = [];
    const selectedParagraphs: number[] = [];

    for (const candidate of candidates) {
      const tooClose = selectedParagraphs.some(
        (selected) => Math.abs(selected - candidate.block.paragraphIndex) < paragraphGap,
      );
      if (tooClose) continue;

      selectedCandidates.push(candidate);
      selectedParagraphs.push(candidate.block.paragraphIndex);

      if (selectedCandidates.length >= maxInlineAds) break;
    }

    selectedCandidates.sort((left, right) => left.block.paragraphIndex - right.block.paragraphIndex);

    const opportunities: PlacementOpportunity[] = selectedCandidates.map((candidate, index) => {
      const position = index + 1;
      const placement = `article_inline_${position}` as AdPlacement;
      const profile = inlineProfileForPosition(position, maxInlineAds);
      const depthBucket = depthBucketForIndex(candidate.block.paragraphIndex, totalParagraphs);
      const isPrimaryInline = position <= 2;

      return {
        slotId: `${placement}-${candidate.block.paragraphIndex}`,
        surface: "article",
        placementHints: [placement],
        anchorId: candidate.block.id,
        anchorIndex: blocks.findIndex((block) => block.id === candidate.block.id),
        reservedSize: profile.reservedSize,
        depthBucket,
        lazy: !isPrimaryInline && depthBucket === "deep",
        designIntent: profile.designIntent,
        preferredFamilies: profile.preferredFamilies,
        preferredTemplates: profile.preferredTemplates,
        score: candidate.score,
        paragraphIndex: candidate.block.paragraphIndex,
      };
    });

    opportunities.push({
      slotId: "sidebar-top",
      surface: "sidebar",
      placementHints: ["sidebar_top"],
      anchorId: "sidebar-top",
      anchorIndex: 0,
      reservedSize: reservedSizeForPlacement("sidebar_top"),
      depthBucket: "above_fold",
      lazy: false,
      designIntent: "trust",
      preferredFamilies: ["sidebar", "display", "sponsored"],
      preferredTemplates: ["poster", "story", "split"],
      score: 1,
    });

    return {
      blocks,
      toc,
      opportunities,
      wordCount,
    };
  }
}
