const SYNONYMS: Record<string, string> = {
  laxmi: 'lakshmi',
  pooja: 'puja',
  ganesh: 'ganesha',
  poooja: 'puja',
};

export function expandSynonyms(tokens: string[]): string[] {
  return tokens.map((t) => SYNONYMS[t] ?? t);
}

export function suggestCorrection(query: string, candidates: string[]): string | null {
  const q = query.toLowerCase();
  let best: string | null = null;
  let bestDist = 3;
  for (const c of candidates) {
    for (const token of c.toLowerCase().split(/\s+/)) {
      const d = editDistance(q, token);
      if (d < bestDist && d > 0) {
        bestDist = d;
        best = c;
      }
    }
  }
  return best;
}

function editDistance(a: string, b: string): number {
  const dp: number[][] = Array.from({ length: a.length + 1 }, (_, i) => [i, ...Array(b.length).fill(0)]);
  for (let j = 1; j <= b.length; j++) dp[0]![j] = j;
  for (let i = 1; i <= a.length; i++) {
    for (let j = 1; j <= b.length; j++) {
      dp[i]![j] = Math.min(dp[i - 1]![j]! + 1, dp[i]![j - 1]! + 1, dp[i - 1]![j - 1]! + (a[i - 1] === b[j - 1] ? 0 : 1));
    }
  }
  return dp[a.length]![b.length]!;
}
