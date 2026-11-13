import React from 'react';

export interface EastIndianChartProps {
  ascendantRashi: number; // 1 to 12
  planets: Record<string, { rashi: number; is_retrograde?: boolean; name?: string }>;
  className?: string;
}

/**
 * Classical East Indian (Bengali / Odia / Assamese) Chart SVG.
 * Outer square with inner diamond and corner triangles.
 * Zero green tokens. Strict Sacred Ember compliance.
 */
export const EastIndianChart: React.FC<EastIndianChartProps> = ({
  ascendantRashi,
  planets,
  className = '',
}) => {
  return (
    <div className={`relative inline-block w-full max-w-[400px] aspect-square ${className}`}>
      <svg
        viewBox="0 0 400 400"
        className="w-full h-full select-none"
        style={{ backgroundColor: '#FFF7ED' }}
      >
        <rect x="2" y="2" width="396" height="396" fill="none" stroke="#2C2033" strokeWidth="3" />
        <line x1="2" y1="2" x2="398" y2="398" stroke="#2C2033" strokeWidth="2" />
        <line x1="398" y1="2" x2="2" y2="398" stroke="#2C2033" strokeWidth="2" />
        <line x1="200" y1="2" x2="2" y2="200" stroke="#2C2033" strokeWidth="2" />
        <line x1="2" y1="200" x2="200" y2="398" stroke="#2C2033" strokeWidth="2" />
        <line x1="200" y1="398" x2="398" y2="200" stroke="#2C2033" strokeWidth="2" />
        <line x1="398" y1="200" x2="200" y2="2" stroke="#2C2033" strokeWidth="2" />

        <text x="200" y="205" textAnchor="middle" fill="#B74717" fontSize="13" fontWeight="bold">
          EAST KUNDLI
        </text>
      </svg>
    </div>
  );
};

