import React from 'react';

export interface RashiWheelProps {
  ascendantDegree: number; // 0 to 360
  planets: Record<string, { longitude: number; name?: string }>;
  className?: string;
}

const RASHIS = ['Aries', 'Taurus', 'Gemini', 'Cancer', 'Leo', 'Virgo', 'Libra', 'Scorpio', 'Sagittarius', 'Capricorn', 'Aquarius', 'Pisces'];

/**
 * 360-degree Circular Rashi Wheel SVG.
 * Colors strictly adhere to Sacred Ember:
 * Inner Ring: #FFF7ED, Outer Ring: #EFE3D3, Markers: #F16A24, Frame: #2C2033. Zero Green.
 */
export const RashiWheel: React.FC<RashiWheelProps> = ({
  ascendantDegree,
  planets,
  className = '',
}) => {
  const center = 200;
  const radius = 180;
  const innerRadius = 120;

  return (
    <div className={`relative inline-block w-full max-w-[400px] aspect-square ${className}`}>
      <svg
        viewBox="0 0 400 400"
        className="w-full h-full select-none"
        style={{ backgroundColor: '#FFF7ED' }}
      >
        {/* Outer Circular Frame */}
        <circle cx={center} cy={center} r={radius} fill="#EFE3D3" stroke="#2C2033" strokeWidth="2" opacity="0.5" />
        <circle cx={center} cy={center} r={innerRadius} fill="#FFF7ED" stroke="#2C2033" strokeWidth="2" />

        {/* 12 Rashi Segments (30 deg each) */}
        {RASHIS.map((rashiName, idx) => {
          const angleDeg = idx * 30;
          const angleRad = (angleDeg * Math.PI) / 180;
          const x1 = center + innerRadius * Math.cos(angleRad);
          const y1 = center + innerRadius * Math.sin(angleRad);
          const x2 = center + radius * Math.cos(angleRad);
          const y2 = center + radius * Math.sin(angleRad);

          const midAngleRad = ((angleDeg + 15) * Math.PI) / 180;
          const tx = center + (innerRadius + 30) * Math.cos(midAngleRad);
          const ty = center + (innerRadius + 30) * Math.sin(midAngleRad);

          return (
            <g key={rashiName}>
              <line x1={x1} y1={y1} x2={x2} y2={y2} stroke="#2C2033" strokeWidth="1" />
              <text
                x={tx}
                y={ty}
                textAnchor="middle"
                dominantBaseline="central"
                fill="#2A2421"
                fontSize="9"
                fontWeight="bold"
              >
                {rashiName.slice(0, 3)}
              </text>
            </g>
          );
        })}

        {/* Center Lagna Indicator */}
        <circle cx={center} cy={center} r="4" fill="#F16A24" />
        <text x={center} y={center + 20} textAnchor="middle" fill="#B74717" fontSize="11" fontWeight="bold">
          LAGNA
        </text>
      </svg>
    </div>
  );
};

