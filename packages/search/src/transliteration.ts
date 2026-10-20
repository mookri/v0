// Devanagari -> Latin fold map (data, not code). Mirrors
// platform-navdhya/app/Modules/Search/Support/TransliterationMap.php.
// Lossy phonetic approximation for matching only.

const MAP: Record<string, string> = {
  'अ': 'a', 'आ': 'a', 'इ': 'i', 'ई': 'i', 'उ': 'u', 'ऊ': 'u', 'ऋ': 'ri',
  'ए': 'e', 'ऐ': 'ai', 'ओ': 'o', 'औ': 'au', 'अं': 'am', 'अः': 'ah',
  'क': 'k', 'ख': 'kh', 'ग': 'g', 'घ': 'gh', 'ङ': 'ng',
  'च': 'ch', 'छ': 'chh', 'ज': 'j', 'झ': 'jh', 'ञ': 'ny',
  'ट': 't', 'ठ': 'th', 'ड': 'd', 'ढ': 'dh', 'ण': 'n',
  'त': 't', 'थ': 'th', 'द': 'd', 'ध': 'dh', 'न': 'n',
  'प': 'p', 'फ': 'ph', 'ब': 'b', 'भ': 'bh', 'म': 'm',
  'य': 'y', 'र': 'r', 'ल': 'l', 'व': 'v', 'श': 'sh', 'ष': 'sh', 'स': 's',
  'ह': 'h', 'क्ष': 'ksh', 'त्र': 'tr', 'ज्ञ': 'gy', 'श्र': 'shr',
  'ा': 'a', 'ि': 'i', 'ी': 'i', 'ु': 'u', 'ू': 'u', 'ृ': 'ri',
  'े': 'e', 'ै': 'ai', 'ो': 'o', 'ौ': 'au', 'ं': 'm', 'ः': 'h', '्': '',
  'ँ': 'n',
  '०': '0', '१': '1', '२': '2', '३': '3', '४': '4', '५': '5', '६': '6',
  '७': '7', '८': '8', '९': '9',
};

const KEYS = Object.keys(MAP).sort((a, b) => b.length - a.length);

export function foldDevanagari(value: string): string {
  let out = '';
  let i = 0;
  const len = value.length; // code units; use Array.from for code points
  const chars = Array.from(value);
  const n = chars.length;
  let idx = 0;
  while (idx < n) {
    let matched = false;
    for (const k of KEYS) {
      const kl = Array.from(k).length;
      if (kl > 1 && chars.slice(idx, idx + kl).join('') === k) {
        out += MAP[k];
        idx += kl;
        matched = true;
        break;
      }
    }
    if (matched) continue;
    const ch = chars[idx];
    out += MAP[ch] ?? ch;
    idx++;
  }
  return out;
}