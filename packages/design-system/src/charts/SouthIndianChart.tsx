import React from 'react';

export interface SouthIndianChartProps {
  ascendantRashi: number; // 1 to 12
  planets: Record<string, { rashi: number; is_retrograde?: boolean; name?: string }>;
  className?: string;
}

const FIXED_RASHIS = [
  { rashi: 12, name: 'Pisces', row: 0, col: 0 },
  { rashi: 1, name: 'Aries', row: 0, col: 1 },
  { rashi: 2, name: 'Taurus', row: 0, col: 2 },
  { rashi: 3, name: 'Gemini', row: 0, col: 3 },
  { rashi: 4, name: 'Cancer', row: 1, col: 3 },
  { rashi: 5, name: 'Leo', row: 2, col: 3 },
  { rashi: 6, name: 'Virgo', row: 3, col: 3 },
  { rashi: 7, name: 'Libra', row: 3, col: 2 },
  { rashi: 8, name: 'Scorpio', row: 3, col: 1 },
  { rashi: 9, name: 'Sagittarius', row: 3, col: 0 },
  { rashi: 10, name: 'Capricorn', row: 2, col: 0 },
  { rashi: 11, name: 'Aquarius', row: 1, col: 0 },
];

/**
 * Classical South Indian Fixed-Zodiac Chart SVG.
 * Colors strictly adhere to Sacred Ember:
 * Canvas: #FFF7ED, Borders: #2C2033, Lagna line: #F16A24, Planets: #2A2421. Zero green tokens.
 */
export const SouthIndianChart: React.FC<SouthIndianChartProps> = ({
  ascendantRashi,
  planets,
  className = '',
}) => {
  const rashiPlanets: Record<number, string[]> = {};
  for (let r = 1; r <= 12; r++) rashiPlanets[r] = [];

  Object.entries(planets).forEach(([pName, pData]) => {
    if (rashiPlanets[pData.rashi]) {
      const label = pData.is_retrograde ? `${pName}(R)` : pName;
      rashiPlanets[pData.rashi].push(label);
    }
  });

  return (
    <div className={`relative inline-block w-full max-w-[400px] aspect-square ${className}`}>
      <svg
        viewBox="0 0 400 400"
        className="w-full h-full select-none"
        style={{ backgroundColor: '#FFF7ED' }}
      >
        {/* Outer Square */}
        <rect x="2" y="2" width="396" height="396" fill="none" stroke="#2C2033" strokeWidth="3" />

        {/* 4x4 Grid lines */}
        <line x1="100" y1="2" x2="100" y2="398" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="200" y1="2" x2="200" y2="100" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="200" y1="300" x2="200" y2="398" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="300" y1="2" x2="300" y2="398" stroke="#2C2033" strokeWidth="1.5" />

        <line x1="2" y1="100" x2="398" y2="100" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="2" y1="200" x2="100" y2="200" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="300" y1="200" x2="398" y2="200" stroke="#2C2033" strokeWidth="1.5" />
        <line x1="2" y1="300" x2="398" y2="300" stroke="#2C2033" strokeWidth="1.5" />

        {/* Central hollow 2x2 area */}
        <rect x="100" y="100" width="200" height="200" fill="#EFE3D3" opacity="0.4" />
        <text x="200" y="205" textAnchor="middle" fill="#2C2033" fontSize="16" fontWeight="bold">
          NAVDHYA
        </text>

        {/* 12 Rashi Boxes */}
        {FIXED_RASHIS.map((box) => {
          const x = box.col * 100;
          const y = box.row * 100;
          const isLagna = box.rashi === ascendantRashi;
          const pList = rashiPlanets[box.rashi] || [];

          return (
            <g key={box.rashi}>
              {isLagna && (
                <line
                  x1={x + 5}
                  y1={y + 5}
                  x2={x + 30}
                  y2={y + 30}
                  stroke="#F16A24"
                  strokeWidth="3"
                />
              )}
              <text x={x + 10} y={y + 20} fill="#B74717" fontSize="11" fontWeight="600">
                {isLagna ? 'ASC' : ''}
              </text>
              <text x={x + 50} y={y + 55} textAnchor="middle" fill="#2A2421" fontSize="12" fontWeight="bold">
                {pList.join(' ')}
              </text>
            </g>
          );
        })}
      </svg>
    </div>
  );
};

