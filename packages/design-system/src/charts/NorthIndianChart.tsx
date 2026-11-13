import React from 'react';

export interface NorthIndianChartProps {
  ascendantRashi: number; // 1 to 12
  planets: Record<string, { rashi: number; is_retrograde?: boolean; name?: string }>;
  className?: string;
}

const RASHI_ABBR = ['', 'Ar', 'Ta', 'Ge', 'Cn', 'Le', 'Vi', 'Li', 'Sc', 'Sg', 'Cp', 'Aq', 'Pi'];

/**
 * Classical Diamond (North Indian) Chart SVG.
 * Colors strictly adhere to Sacred Ember:
 * Canvas: #FFF7ED (Warm Ivory)
 * Outlines: #2C2033 (Aubergine Ink)
 * Accents: #F16A24 (Flame Orange) / #B74717 (Deep Ember)
 * Text: #2A2421 (Deep Text)
 * Zero Green Tokens.
 */
export const NorthIndianChart: React.FC<NorthIndianChartProps> = ({
  ascendantRashi,
  planets,
  className = '',
}) => {
  // Determine rashi in each house (House 1 = ascendantRashi, House 2 = (ascendantRashi % 12) + 1, etc.)
  const houseRashis: number[] = [];
  for (let h = 1; h <= 12; h++) {
    houseRashis[h] = ((ascendantRashi - 1 + (h - 1)) % 12) + 1;
  }

  // Group planets by house
  const housePlanets: Record<number, string[]> = {};
  for (let h = 1; h <= 12; h++) housePlanets[h] = [];

  Object.entries(planets).forEach(([pName, pData]) => {
    // Find house containing pData.rashi
    for (let h = 1; h <= 12; h++) {
      if (houseRashis[h] === pData.rashi) {
        const label = pData.is_retrograde ? `${pName}(R)` : pName;
        housePlanets[h].push(label);
        break;
      }
    }
  });

  return (
    <div className={`relative inline-block w-full max-w-[400px] aspect-square ${className}`}>
      <svg
        viewBox="0 0 400 400"
        className="w-full h-full select-none"
        style={{ backgroundColor: '#FFF7ED' }}
      >
        {/* Outer Square Border */}
        <rect
          x="2"
          y="2"
          width="396"
          height="396"
          fill="none"
          stroke="#2C2033"
          strokeWidth="3"
        />

        {/* Diagonal Cross Lines */}
        <line x1="2" y1="2" x2="398" y2="398" stroke="#2C2033" strokeWidth="2" />
        <line x1="398" y1="2" x2="2" y2="398" stroke="#2C2033" strokeWidth="2" />

        {/* Central Diamond Lines */}
        <line x1="200" y1="2" x2="2" y2="200" stroke="#2C2033" strokeWidth="2" />
        <line x1="2" y1="200" x2="200" y2="398" stroke="#2C2033" strokeWidth="2" />
        <line x1="200" y1="398" x2="398" y2="200" stroke="#2C2033" strokeWidth="2" />
        <line x1="398" y1="200" x2="200" y2="2" stroke="#2C2033" strokeWidth="2" />

        {/* House Coordinates for labels & planets */}
        {/* House 1 (Top Central Diamond) */}
        <text x="200" y="80" textAnchor="middle" fill="#B74717" fontSize="12" fontWeight="600">
          {RASHI_ABBR[houseRashis[1]]} ({houseRashis[1]})
        </text>
        <text x="200" y="115" textAnchor="middle" fill="#2A2421" fontSize="13" fontWeight="bold">
          {housePlanets[1].join(' ')}
        </text>

        {/* House 2 (Top Left Triangle) */}
        <text x="100" y="55" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[2]]}
        </text>
        <text x="100" y="80" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[2].join(' ')}
        </text>

        {/* House 3 */}
        <text x="55" y="100" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[3]]}
        </text>
        <text x="55" y="125" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[3].join(' ')}
        </text>

        {/* House 4 (Left Diamond) */}
        <text x="80" y="200" textAnchor="middle" fill="#B74717" fontSize="12" fontWeight="600">
          {RASHI_ABBR[houseRashis[4]]}
        </text>
        <text x="115" y="205" textAnchor="middle" fill="#2A2421" fontSize="13" fontWeight="bold">
          {housePlanets[4].join(' ')}
        </text>

        {/* House 5 */}
        <text x="55" y="300" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[5]]}
        </text>
        <text x="55" y="325" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[5].join(' ')}
        </text>

        {/* House 6 */}
        <text x="100" y="345" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[6]]}
        </text>
        <text x="100" y="370" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[6].join(' ')}
        </text>

        {/* House 7 (Bottom Central Diamond) */}
        <text x="200" y="320" textAnchor="middle" fill="#B74717" fontSize="12" fontWeight="600">
          {RASHI_ABBR[houseRashis[7]]}
        </text>
        <text x="200" y="285" textAnchor="middle" fill="#2A2421" fontSize="13" fontWeight="bold">
          {housePlanets[7].join(' ')}
        </text>

        {/* House 8 */}
        <text x="300" y="345" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[8]]}
        </text>
        <text x="300" y="370" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[8].join(' ')}
        </text>

        {/* House 9 */}
        <text x="345" y="300" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[9]]}
        </text>
        <text x="345" y="325" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[9].join(' ')}
        </text>

        {/* House 10 (Right Central Diamond) */}
        <text x="320" y="200" textAnchor="middle" fill="#B74717" fontSize="12" fontWeight="600">
          {RASHI_ABBR[houseRashis[10]]}
        </text>
        <text x="285" y="205" textAnchor="middle" fill="#2A2421" fontSize="13" fontWeight="bold">
          {housePlanets[10].join(' ')}
        </text>

        {/* House 11 */}
        <text x="345" y="100" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[11]]}
        </text>
        <text x="345" y="125" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[11].join(' ')}
        </text>

        {/* House 12 */}
        <text x="300" y="55" textAnchor="middle" fill="#B74717" fontSize="11">
          {RASHI_ABBR[houseRashis[12]]}
        </text>
        <text x="300" y="80" textAnchor="middle" fill="#2A2421" fontSize="11" fontWeight="600">
          {housePlanets[12].join(' ')}
        </text>
      </svg>
    </div>
  );
};

