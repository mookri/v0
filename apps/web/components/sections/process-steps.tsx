"use client";

import { useState } from "react";
import Image from "next/image";

type ProcessImage = {
  url: string;
  description: string;
};

type ProcessStep = {
  number: string;
  eyebrow: string;
  title: string;
  subtitle: string;
  description: string;
  galleryTitle: string;
  images: ProcessImage[];
};

const steps: ProcessStep[] = [
  {
    number: "01",
    eyebrow: "START WITH WHAT YOU NEED",
    title: "Choose Your Puja",
    subtitle: "Explore our collection of traditional pujas, havans, homams, and Vedic rituals to find one that’s right for your occasion.",
    description: "Explore our collection of traditional pujas, havans, homams, and Vedic rituals to find one that’s right for your occasion.",
    galleryTitle: "Find Your Ritual",
    images: [
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680301/Whisk_b79ae5ba729b83cafd54991c00e8c3eceg_hvtgu7.png",
        description: "Explore your ritual",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680516/Whisk_551db29e967110fa359451e9db7c6274eg_jdcdpy.png",
        description: "Browse puja categories",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680691/Whisk_3dd8aeccb36feaa9cdc41fd86d9c8678dr_xk4an7.jpg",
        description: "View ritual details",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680843/Whisk_fbe5ec7e748e65687974044ae3cef9d7dr_x8hrcm.jpg",
        description: "Save your preferred puja",
      },
    ],
  },
  {
    number: "02",
    eyebrow: "CHOOSE WHEN IT FEELS RIGHT",
    title: "Select Your Date & Time",
    subtitle: "Pick a date and convenient time for your ceremony. Where applicable, you can also choose an auspicious muhurat.",
    description: "Pick a date and convenient time for your ceremony. Where applicable, you can also choose an auspicious muhurat.",
    galleryTitle: "Choose Your Time",
    images: [
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761766531/step-02-01-navdhya_kzhldl.jpg",
        description: "Check availability",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761766709/step-02-02-navdhya_qecw1d.jpg",
        description: "Select auspicious muhurat",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767149/step-02-03-navdhya_btt8kw.png",
        description: "Confirm timing preferences",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767321/step-02-04-navdhya-01_x3zlda.jpg",
        description: "Receive booking confirmation",
      },
    ],
  },
  {
    number: "03",
    eyebrow: "LEAVE THE PREPARATION TO US",
    title: "We Arrange Everything",
    subtitle: "Once your booking is confirmed, we coordinate the Pandit Ji, puja samagri, and other essentials needed for your ceremony.",
    description: "Once your booking is confirmed, we coordinate the Pandit Ji, puja samagri, and other essentials needed for your ceremony.",
    galleryTitle: "Everything Is Prepared",
    images: [
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767902/step-03-01-navdhya_fkuzls.jpg",
        description: "Verified Pandit Ji assigned",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767903/step-03-03-navdhya_2_bn7mus.jpg",
        description: "Puja samagri prepared",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767904/step-03-03-navdhya_rcfmu6.jpg",
        description: "Ritual setup arranged",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761768016/step-03-04_urqedn.jpg",
        description: "Ceremony assistance",
      },
    ],
  },
  {
    number: "04",
    eyebrow: "NOW, BE PRESENT",
    title: "Experience Your Puja",
    subtitle: "With the arrangements taken care of, you can be present for the ritual, focus on your prayers, and experience the ceremony with peace of mind.",
    description: "With the arrangements taken care of, you can be present for the ritual, focus on your prayers, and experience the ceremony with peace of mind.",
    galleryTitle: "Be Present for the Puja",
    images: [
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767902/step-03-01-navdhya_fkuzls.jpg",
        description: "Sacred fire ceremony",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767904/step-03-03-navdhya_rcfmu6.jpg",
        description: "Vedic mantras",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680691/Whisk_3dd8aeccb36feaa9cdc41fd86d9c8678dr_xk4an7.jpg",
        description: "Participate in the ritual",
      },
      {
        url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767321/step-02-04-navdhya-01_x3zlda.jpg",
        description: "Receive divine blessings",
      },
    ],
  },
];

export function ProcessSteps() {
  const [activeStep, setActiveStep] = useState(0);
  const currentStep = steps[activeStep];

  const changeStep = (index: number) => {
    if (index === activeStep) return;
    setActiveStep(index);
  };

  const bookPuja = () => {
    document
      .getElementById("booking")
      ?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  return (
    <section className="w-full bg-[#0E3F2E] text-white py-10 md:py-16 px-6 md:px-12 overflow-hidden" id="process">
      <div className="max-w-[1340px] w-full mx-auto flex flex-col gap-6 lg:gap-0">

        {/* HEADER */}
        <header className="max-w-[520px] mb-6 lg:mb-10">
          <h2 className="text-2xl md:text-[32px] leading-[1.15] font-semibold tracking-[-0.02em] mb-3 text-white">
            Choose Your Puja.<br />
            We'll Take Care of the Rest.
          </h2>
          <p className="text-[13px] md:text-[14px] leading-[1.6] text-white/70 m-0">
            Finding and arranging a puja should feel peaceful, not complicated. Choose the ritual
            you're looking for, select a date and time that works for you, and let Navdhya take
            care of the essential arrangements — from Pandit Ji and puja samagri to the ceremony setup.
          </p>
        </header>

        <div className="flex flex-col lg:flex-row items-stretch gap-8 lg:gap-12">

          {/* DESKTOP NAV (Hidden on Mobile) */}
          <div className="hidden lg:block lg:flex-[0_0_40%] lg:max-w-[420px]">
            <nav className="relative" aria-label="Puja booking process">
              <div className="absolute left-6 top-8 bottom-[100px] w-px bg-white/10 z-0" aria-hidden="true" />

              <div className="flex flex-col gap-2 relative z-10">
                {steps.map((step, index) => {
                  const active = index === activeStep;
                  return (
                    <button
                      key={step.number}
                      type="button"
                      className={`grid grid-cols-[auto_1fr_auto] gap-3 items-center min-h-[64px] p-2.5 px-3 border-0 rounded-[14px] bg-transparent text-left cursor-pointer transition-all duration-250 relative opacity-75 hover:opacity-100 hover:bg-white/5 group ${
                        active ? "bg-white/5 opacity-100 before:absolute before:left-0 before:top-3 before:bottom-3 before:w-[3px] before:rounded-r-sm before:bg-[#F9B234]" : ""
                      }`}
                      onClick={() => changeStep(index)}
                      aria-current={active ? "step" : "false"}
                    >
                      <div className={`w-6 h-6 rounded-full border border-white/10 flex items-center justify-center transition-all duration-250 relative z-20 ${active ? 'bg-[#F9B234] border-[#F9B234]' : 'bg-[#0E3F2E]'}`}>
                        <span className={`text-[10px] ${active ? 'font-bold text-[#0E3F2E]' : 'font-semibold text-white/70'}`}>{step.number}</span>
                      </div>

                      <div className="flex flex-col gap-1">
                        <strong className="text-[15px] font-semibold text-white">{step.title}</strong>
                        <span className={`text-[12px] leading-[1.4] ${active ? 'text-white/85' : 'text-white/70'}`}>{step.subtitle}</span>
                      </div>

                      <div className={`transition-all duration-250 ${active ? 'opacity-100 text-[#F9B234]' : 'text-white opacity-25 group-hover:opacity-70'}`} aria-hidden="true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                          <path d="M5 12h14"></path>
                          <path d="m12 5 7 7-7 7"></path>
                        </svg>
                      </div>
                    </button>
                  );
                })}
              </div>

              <div className="mt-6">
                <button type="button" className="flex items-center justify-center gap-3 w-full lg:max-w-[260px] h-12 border-0 rounded-full bg-[#F9B234] text-[#0E3F2E] text-sm font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-px hover:shadow-[0_4px_16px_rgba(249,178,52,0.2)] group" onClick={bookPuja}>
                  Book Your Puja
                  <svg className="transition-transform duration-200 group-hover:translate-x-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                    <path d="M5 12h14"></path>
                    <path d="m12 5 7 7-7 7"></path>
                  </svg>
                </button>
                <p className="text-[13px] lg:text-[11px] text-white/70 mt-3 lg:mt-3 text-center lg:text-left lg:pl-5">Choose your ritual and preferred time to get started.</p>
              </div>
            </nav>
          </div>

          {/* VISUAL STAGE */}
          <div className="flex-1 lg:flex-[0_0_58%]">
            <div className="mb-4">
              <span className="block text-[11px] font-bold tracking-[0.15em] text-[#F9B234] mb-1">{String(activeStep + 1).padStart(2, '0')} / 04</span>
              <h3 className="text-[18px] md:text-[22px] font-semibold m-0 text-white">{currentStep.galleryTitle}</h3>
            </div>

            <div className="relative w-full aspect-[4/3] lg:aspect-auto lg:h-[calc(100%-48px)] lg:min-h-[400px] lg:max-h-[480px] rounded-[20px] overflow-hidden group/gallery">
              {steps.map((step, stepIndex) => {
                const isActive = stepIndex === activeStep;
                return (
                  <div
                    key={step.number}
                    className={`absolute inset-0 grid grid-cols-1 lg:grid-cols-[2fr_1.1fr] gap-3 lg:gap-4 transition-all duration-[450ms] ${isActive ? 'opacity-100 visible pointer-events-auto translate-x-0' : 'opacity-0 invisible pointer-events-none translate-x-2'}`}
                    aria-hidden={!isActive}
                  >
                    <div className="relative w-full h-full rounded-[20px] overflow-hidden bg-white/5">
                      <img
                        src={step.images[0].url}
                        alt={step.images[0].description}
                        loading={stepIndex === 0 ? "eager" : "lazy"}
                        className="w-full h-full object-cover object-center transition-transform duration-[600ms] group-hover/gallery:scale-[1.03]"
                      />
                      <div className="absolute bottom-2 left-2 bg-[#0e3f2e]/80 backdrop-blur-sm px-2.5 py-1.5 rounded-lg lg:rounded-full text-[11px] font-medium text-white max-w-[calc(100%-16px)] whitespace-nowrap overflow-hidden text-ellipsis shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                        01 &middot; {step.images[0].description}
                      </div>
                    </div>

                    <div className="hidden lg:grid grid-rows-2 gap-3">
                      <div className="relative w-full h-full rounded-[20px] overflow-hidden bg-white/5">
                        <img 
                          src={step.images[1].url} 
                          alt={step.images[1].description} 
                          loading="lazy" 
                          className="w-full h-full object-cover object-center transition-transform duration-[600ms] group-hover/gallery:scale-[1.03]"
                        />
                        <div className="absolute bottom-2 left-2 bg-[#0e3f2e]/80 backdrop-blur-sm px-2.5 py-1.5 rounded-lg lg:rounded-full text-[11px] font-medium text-white max-w-[calc(100%-16px)] whitespace-nowrap overflow-hidden text-ellipsis shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                          02 &middot; {step.images[1].description}
                        </div>
                      </div>
                      <div className="relative w-full h-full rounded-[20px] overflow-hidden bg-white/5">
                        <img 
                          src={step.images[2].url} 
                          alt={step.images[2].description} 
                          loading="lazy" 
                          className="w-full h-full object-cover object-center transition-transform duration-[600ms] group-hover/gallery:scale-[1.03]"
                        />
                        <div className="absolute bottom-2 left-2 bg-[#0e3f2e]/80 backdrop-blur-sm px-2.5 py-1.5 rounded-lg lg:rounded-full text-[11px] font-medium text-white max-w-[calc(100%-16px)] whitespace-nowrap overflow-hidden text-ellipsis shadow-[0_4px_12px_rgba(0,0,0,0.1)]">
                          03 &middot; {step.images[2].description}
                        </div>
                      </div>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>

          {/* MOBILE NAV (Hidden on Desktop) */}
          <div className="flex flex-col gap-6 lg:hidden">
            <div className="flex items-center gap-2" role="tablist">
              {steps.map((step, index) => (
                <button
                  key={`mob-${step.number}`}
                  type="button"
                  role="tab"
                  className={`flex-1 h-9 border rounded-full text-xs font-semibold flex items-center justify-center transition-all duration-250 ${index === activeStep ? 'bg-[#F9B234] border-[#F9B234] text-[#0E3F2E]' : 'border-white/10 bg-transparent text-white/70'}`}
                  onClick={() => changeStep(index)}
                  aria-label={`Step ${step.number}`}
                  aria-selected={index === activeStep}
                  aria-current={index === activeStep ? "step" : "false"}
                >
                  {step.number}
                </button>
              ))}
            </div>

            <div className="flex flex-col gap-2" role="tabpanel">
              <span className="text-[10px] font-bold tracking-[0.1em] text-[#F9B234] uppercase">{currentStep.eyebrow}</span>
              <strong className="text-[18px] font-semibold text-white m-0">{currentStep.title}</strong>
              <p className="text-[14px] text-white/70 leading-[1.5] m-0">{currentStep.description}</p>
            </div>

            <div className="mt-2">
              <button type="button" className="flex items-center justify-center gap-3 w-full h-12 border-0 rounded-full bg-[#F9B234] text-[#0E3F2E] text-sm font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-px hover:shadow-[0_4px_16px_rgba(249,178,52,0.2)] group" onClick={bookPuja}>
                Book Your Puja
                <svg className="transition-transform duration-200 group-hover:translate-x-1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M5 12h14"></path>
                  <path d="m12 5 7 7-7 7"></path>
                </svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </section>
  );
}