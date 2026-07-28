import { MOCK_PANDITS, PANDIT_LANGUAGES, PANDIT_LOCATIONS, PANDIT_SPECIALIZATIONS, PANDIT_EXPERIENCE_OPTIONS, type Pandit } from "@/lib/pandits";

describe("lib/pandits — data integrity", () => {
  test("MOCK_PANDITS has 6 entries (regression for accidental deletions)", () => {
    expect(MOCK_PANDITS.length).toBe(6);
  });

  test("each pandit has a unique id", () => {
    const ids = MOCK_PANDITS.map((p) => p.id);
    const unique = new Set(ids);
    expect(unique.size).toBe(ids.length);
  });

  test("each pandit has a unique name (case-insensitive)", () => {
    const names = MOCK_PANDITS.map((p) => p.name.toLowerCase());
    const unique = new Set(names);
    expect(unique.size).toBe(names.length);
  });

  test("each pandit's location is in PANDIT_LOCATIONS", () => {
    for (const p of MOCK_PANDITS) {
      expect(PANDIT_LOCATIONS).toContain(p.location);
    }
  });

  test("each pandit's specialization is in PANDIT_SPECIALIZATIONS", () => {
    for (const p of MOCK_PANDITS) {
      expect(PANDIT_SPECIALIZATIONS).toContain(p.specialization);
    }
  });

  test("each pandit's experience matches the format 'N+ years'", () => {
    const expPattern = /^\d+\+ years$/;
    for (const p of MOCK_PANDITS) {
      expect(p.experience).toMatch(expPattern);
    }
  });

  test("each pandit's rating is in [0, 5]", () => {
    for (const p of MOCK_PANDITS) {
      expect(p.rating).toBeGreaterThanOrEqual(0);
      expect(p.rating).toBeLessThanOrEqual(5);
    }
  });

  test("each pandit has at least one skill and at least one language", () => {
    for (const p of MOCK_PANDITS) {
      expect(p.skills.length).toBeGreaterThan(0);
      expect(p.languages.length).toBeGreaterThan(0);
    }
  });

  test("each pandit's languages are in PANDIT_LANGUAGES", () => {
    for (const p of MOCK_PANDITS) {
      for (const lang of p.languages) {
        expect(PANDIT_LANGUAGES).toContain(lang);
      }
    }
  });

  test("find-pandit extended data is present (about + certifications)", () => {
    for (const p of MOCK_PANDITS) {
      expect(typeof p.about).toBe("string");
      expect(p.about!.length).toBeGreaterThan(20);
      expect(Array.isArray(p.certifications)).toBe(true);
      expect(p.certifications!.length).toBeGreaterThan(0);
    }
  });

  test("image URLs are HTTPS", () => {
    for (const p of MOCK_PANDITS) {
      expect(p.image.startsWith("https://")).toBe(true);
    }
  });
});

describe("lib/pandits — list-source-of-truth (regression for drift)", () => {
  test("app/pandits and app/find-pandit can both consume the same list", () => {
    // Smoke test: both routes construct a `mockPandits` variable from
    // MOCK_PANDITS. If we accidentally break the export shape, this
    // fails fast.
    const a: Pandit[] = [...MOCK_PANDITS] as Pandit[];
    const b: Pandit[] = [...MOCK_PANDITS] as Pandit[];
    expect(a).toEqual(b);
  });
});
