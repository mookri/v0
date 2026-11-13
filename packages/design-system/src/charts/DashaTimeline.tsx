import React from 'react';

export interface DashaBar {
  lord: string;
  startDate: string;
  endDate: string;
  durationYears: number;
  level: number;
}

export interface DashaTimelineProps {
  periods: DashaBar[];
  onSelectPeriod?: (period: DashaBar) => void;
  className?: string;
}

const LORD_COLORS: Record<string, string> = {
  ketu: '#B74717',
  venus: '#F16A24',
  sun: '#F16A24',
  moon: '#EFE3D3',
  mars: '#B74717',
  rahu: '#2C2033',
  jupiter: '#F16A24',
  saturn: '#2C2033',
  mercury: '#EFE3D3',
};

/**
 * Accessible Sacred Ember Dasha Timeline Bar.
 * Strict conformance to brand tokens: zero green.
 */
export const DashaTimeline: React.FC<DashaTimelineProps> = ({
  periods,
  onSelectPeriod,
  className = '',
}) => {
  const totalYears = periods.reduce((sum, p) => sum + p.durationYears, 0);

  return (
    <div className={`w-full space-y-3 ${className}`}>
      <div className="flex items-center justify-between text-xs font-semibold text-[#B74717] tracking-wider uppercase">
        <span>Vimshottari Dasha Progression</span>
        <span>Total: {Math.round(totalYears)} Years</span>
      </div>

      <div className="h-8 w-full rounded-lg overflow-hidden flex border border-[#2C2033]/20 bg-[#FFF7ED]">
        {periods.map((period, idx) => {
          const widthPct = totalYears > 0 ? (period.durationYears / totalYears) * 100 : 0;
          const bg = LORD_COLORS[period.lord.toLowerCase()] || '#2C2033';
          const isDark = bg === '#2C2033' || bg === '#B74717';

          return (
            <button
              key={`${period.lord}-${idx}`}
              type="button"
              onClick={() => onSelectPeriod?.(period)}
              style={{ width: `${widthPct}%`, backgroundColor: bg }}
              className="h-full relative group border-r border-white/20 transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-[#F16A24]"
              title={`${period.lord.toUpperCase()}: ${period.startDate} to ${period.endDate} (${period.durationYears.toFixed(1)}y)`}
            >
              {widthPct > 5 && (
                <span
                  className="text-[10px] font-bold uppercase truncate px-1"
                  style={{ color: isDark ? '#FFF7ED' : '#2C2033' }}
                >
                  {period.lord.slice(0, 3)}
                </span>
              )}
            </button>
          );
        })}
      </div>

      <div className="flex flex-wrap items-center gap-2 text-xs text-[#2A2421]/70">
        {periods.map((period, idx) => (
          <span key={`legend-${period.lord}-${idx}`} className="inline-flex items-center gap-1">
            <span
              className="w-2.5 h-2.5 rounded-full inline-block"
              style={{ backgroundColor: LORD_COLORS[period.lord.toLowerCase()] || '#2C2033' }}
            />
            <span className="capitalize font-medium text-[#2C2033]">{period.lord}</span>
            <span>({period.durationYears.toFixed(1)}y)</span>
          </span>
        ))}
      </div>
    </div>
  );
};

