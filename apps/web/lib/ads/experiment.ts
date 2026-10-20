//lib/ads/experiment.ts
export function getVariant(key: string): "A" | "B" {
    if (typeof window === "undefined") return "A";

    const stored = localStorage.getItem(`exp_${key}`);
    if (stored) return stored as "A" | "B";

    const variant = Math.random() > 0.5 ? "A" : "B";
    localStorage.setItem(`exp_${key}`, variant);

    return variant;
}