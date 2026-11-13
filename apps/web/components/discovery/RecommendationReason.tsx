'use client';

import React from 'react';

export interface RecommendationReasonProps {
  reasonCode: string;
  reasonLabel: string;
  className?: string;
}

export const RecommendationReason: React.FC<RecommendationReasonProps> = ({
  reasonCode,
  reasonLabel,
  className = '',
}) => {
  return (
    <div
      className={`inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium border ${className}`}
      style={{
        backgroundColor: '#EFE3D3', // Soft Sand
        borderColor: '#B74717', // Deep Ember border accent
        color: '#2C2033', // Aubergine Ink
      }}
      data-reason-code={reasonCode}
    >
      <span
        className="w-1.5 h-1.5 rounded-full flex-shrink-0 animate-pulse"
        style={{ backgroundColor: '#F16A24' }} // Navdhya Flame
        aria-hidden="true"
      />
      <span className="truncate">{reasonLabel}</span>
    </div>
  );
};

