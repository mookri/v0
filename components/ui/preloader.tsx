import React from 'react';

interface PreloaderProps {
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

const Preloader: React.FC<PreloaderProps> = ({ 
  size = 'md',
  className = ''
}) => {
  // Define sizes
  const sizes = {
    sm: { width: 'w-4', height: 'h-4', border: 'border-2' },
    md: { width: 'w-6', height: 'h-6', border: 'border-3' },
    lg: { width: 'w-8', height: 'h-8', border: 'border-4' }
  };

  const { width, height, border } = sizes[size];

  return (
    <div className="flex items-center justify-center" role="status" aria-label="Loading">
      <div 
        className={`
          ${width} ${height}
          ${border}
          rounded-full
          border-t-primary border-r-primary border-b-primary border-l-transparent
          animate-spin
          ${className}
        `}
      />
    </div>
  );
};

export default Preloader;