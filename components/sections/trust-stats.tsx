"use client";

import { useState, useEffect, useRef } from 'react';

export function TrustStats() {
  const stats = [
    ["2000", "Pujas Completed"],
    ["500", "Families Served"],
    ["50", "Certified Pandits"],
    ["98", "Satisfaction Rate"],
  ];

  const [counters, setCounters] = useState([0, 0, 0, 0]);
  const [hasAnimated, setHasAnimated] = useState(false);
  const sectionRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting && !hasAnimated) {
          setHasAnimated(true);
          animateCounters();
        }
      },
      { threshold: 0.5 }
    );

    if (sectionRef.current) {
      observer.observe(sectionRef.current);
    }

    return () => {
      if (sectionRef.current) {
        observer.unobserve(sectionRef.current);
      }
    };
  }, [hasAnimated]);

  const animateCounters = () => {
    const targets = [2000, 500, 50, 98];
    const duration = 2000; // ms
    const steps = 60;
    const stepDuration = duration / steps;

    const increments = targets.map(target => target / steps);

    let currentStep = 0;
    const timer = setInterval(() => {
      if (currentStep < steps) {
        setCounters(prev => 
          prev.map((val, index) => 
            Math.min(Math.floor(increments[index] * (currentStep + 1)), targets[index])
          )
        );
        currentStep++;
      } else {
        clearInterval(timer);
        setCounters(targets);
      }
    }, stepDuration);
  };

  return (
    <section 
      ref={sectionRef}
      aria-labelledby="stats-title" 
      className="bg-[#0E3F2E] py-10"
    >
      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="text-center mb-8">
          <h2 
            id="stats-title"
            className="font-serif text-xl md:text-2xl lg:text-3xl font-bold tracking-tight text-white relative inline-block mb-2"
          >
            Trust Statistics
            <span className="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-16 h-0.5 bg-gradient-to-r from-white to-white/80 rounded-full"></span>
          </h2>
        </div>
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8 w-full">
          {stats.map(([num, label], index) => (
            <div 
              key={label} 
              className="p-4 rounded-md border border-white/10 bg-white/5 backdrop-blur-sm flex flex-col items-center transition-all duration-300 hover:-translate-y-0.5"
            >
              <div className="font-serif text-2xl font-bold text-white">
                {index === 3 ? `${counters[index]}%` : `${counters[index].toLocaleString()}+`}
              </div>
              <div className="text-xs mt-1.5 text-center text-white/80">{label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}