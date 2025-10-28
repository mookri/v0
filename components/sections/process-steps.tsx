"use client";

import { useState } from "react";

export function ProcessSteps() {
  const [activeStep, setActiveStep] = useState(0);
  const [expandedImage, setExpandedImage] = useState<number | null>(null);

  const steps = [
    {
      title: "Select Your Ritual",
      description: "Choose from our comprehensive puja catalog",
      images: [
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-1.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-2.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-3.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-4.avif"
      ]
    },
    {
      title: "Pick Date & Time",
      description: "Select auspicious muhurat or get consultation",
      images: [
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-2.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-3.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-4.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-1.avif"
      ]
    },
    {
      title: "We Handle Everything",
      description: "Pandit, samagri, setup — arrive & experience divinity",
      images: [
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-3.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-4.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-1.avif",
        "https://minicom.risingbamboo.com/wp-content/uploads/2022/09/home1-step-2.avif"
      ]
    },
  ];

  const handleStepClick = (index: number) => {
    setActiveStep(index);
    // Reset expanded image when step changes
    setExpandedImage(null);
  };

  const handleImageClick = (index: number) => {
    setExpandedImage(expandedImage === index ? null : index);
  };

  return (
    <>
      <style>{`
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');
        
        @keyframes growCircle {
          0% {
            transform: scale(1);
            opacity: 0.4;
          }
          50% {
            transform: scale(1.4);
            opacity: 0.1;
          }
          100% {
            transform: scale(1);
            opacity: 0.4;
          }
        }
        
        @keyframes pulse {
          0%, 100% {
            opacity: 1;
            background-color: #0E3F2E;
          }
          50% {
            opacity: 0.6;
            background-color: #155d42;
          }
        }
        
        @keyframes shimmer {
          0% {
            transform: translateY(-100%);
          }
          100% {
            transform: translateY(100%);
          }
        }
        
        .pulse-line {
          animation: pulse 2s ease-in-out infinite;
        }
        
        .process-section {
          position: relative;
          overflow: hidden;
        }
        
        .process-section::before {
          content: '';
          position: absolute;
          top: -50%;
          left: -50%;
          width: 200%;
          height: 200%;
          background: radial-gradient(circle, rgba(14, 63, 46, 0.03) 0%, transparent 70%);
          pointer-events: none;
          animation: rotate 30s linear infinite;
        }
        
        @keyframes rotate {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        
        .step-wrapper {
          transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .step-wrapper:hover {
          transform: translateX(4px);
        }
        
        /* Desktop - Horizontal 4 images */
        @media (min-width: 1024px) {
          .image-container-wrapper {
            flex-direction: row !important;
            gap: 16px !important;
            flex-wrap: nowrap !important;
          }
          
          .image-box {
            flex: 0 0 calc(25% - 12px) !important;
            height: 100% !important;
            min-height: 480px !important;
          }
          
          .image-box.expanded {
            flex: 0 0 calc(55%) !important;
          }
          
          .image-overlay-desktop {
            opacity: 0;
            transition: opacity 0.3s ease;
          }
          
          .image-box:hover .image-overlay-desktop {
            opacity: 1;
          }
        }
        
        /* Tablet */
        @media (min-width: 768px) and (max-width: 1023px) {
          .image-container-wrapper {
            flex-direction: row !important;
            gap: 12px !important;
            flex-wrap: nowrap !important;
          }
          
          .image-box {
            flex: 0 0 calc(25% - 9px) !important;
            height: 100% !important;
            min-height: 380px !important;
          }
          
          .image-box.expanded {
            flex: 0 0 calc(46%) !important;
          }
          
          .left-content-padding {
            padding-left: 40px !important;
            padding-right: 40px !important;
            padding-top: 50px !important;
            padding-bottom: 50px !important;
          }
        }
        
        /* Mobile - 2x2 Grid */
        @media (max-width: 767px) {
          .image-container-wrapper {
            flex-direction: row !important;
            flex-wrap: wrap !important;
            gap: 10px !important;
            height: auto !important;
          }
          
          .image-box {
            flex: 0 0 calc(50% - 5px) !important;
            height: 180px !important;
            min-height: 180px !important;
            position: relative !important;
          }
          
          .image-box.expanded {
            flex: 0 0 calc(50% - 5px) !important;
          }
          
          .image-overlay-mobile {
            display: flex !important;
          }
          
          .left-content-padding {
            padding-left: 24px !important;
            padding-right: 24px !important;
            padding-top: 40px !important;
            padding-bottom: 40px !important;
          }
          
          .step-item {
            padding-left: 48px !important;
            padding-top: 12px !important;
            padding-bottom: 12px !important;
          }
          
          .step-number {
            left: -48px !important;
            width: 36px !important;
            height: 36px !important;
            line-height: 36px !important;
            font-size: 15px !important;
          }
          
          .connecting-line {
            left: 29.5px !important;
            top: 46px !important;
            height: 30px !important;
          }
          
          .growing-circle {
            left: -6px !important;
            top: 0px !important;
            width: 48px !important;
            height: 48px !important;
          }
          
          .book-now-button {
            display: block !important;
            margin: 30px auto 20px !important;
            width: 80% !important;
          }
        }
        
        @media (max-width: 480px) {
          .image-box {
            height: 150px !important;
            min-height: 150px !important;
          }
        }
        
        .book-now-button {
          display: none;
        }
      `}</style>
      
      <section id="process" aria-labelledby="process-title" className="process-section lg:py-20" style={{ backgroundColor: '#F5F5F5', borderRadius: '10px 0px 0px 10px' }}>
        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 lg:grid-cols-5 gap-8 lg:gap-0">
            {/* Left Content Column */}
            <div className="lg:col-span-2 relative z-10 left-content-padding" >
              <div className="pb-4">
                <div className="inline-block">
                  <p style={{
                    color: '#B7B7B7',
                    letterSpacing: '1px',
                    lineHeight: '12px',
                    textTransform: 'uppercase',
                    fontWeight: 500,
                    fontSize: '10px',
                    marginBottom: '12px',
                    fontFamily: "'DM Sans', sans-serif"
                  }}>
                    Sacred Process - Simple & Hassle-Free
                  </p>
                  <h2 
                    id="process-title"
                    style={{
                      textTransform: 'uppercase',
                      color: '#181818',
                      fontFamily: "'DM Sans', sans-serif",
                      fontWeight: 700,
                      letterSpacing: 'normal',
                      lineHeight: '40px',
                      fontSize: '24px',
                      margin: '0px'
                    }}
                    className="text-center lg:text-left"
                  >
                    Your Journey to Divine Experience
                  </h2>
                </div>
              </div>

              <div className="pt-6">
                <div className="text-left">
                  {steps.map((step, index) => (
                    <div 
                      key={index} 
                      data-index={index}
                      className="step-item step-wrapper"
                      style={{
                        position: 'relative',
                        paddingLeft: '60px',
                        paddingTop: '15px',
                        paddingBottom: '16px',
                        cursor: 'pointer'
                      }}
                      onClick={() => handleStepClick(index)}
                    >
                        {/* Connecting line - perfectly centered */}
                        {index < steps.length - 1 && (
                          <div 
                            className={index < activeStep ? 'pulse-line' : ''}
                            style={{
                              position: 'absolute',
                              left: '20px',
                              top: '56px',
                              width: '2px',
                              height: '85px',
                              backgroundColor: index < activeStep ? '#0E3F2E' : '#D9D9D9',
                              zIndex: 1,
                              transition: 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)',
                              transformOrigin: 'top'
                            }}
                          />
                        )}
                        
                        {/* Growing circle animation - perfectly centered */}
                        {index === activeStep && (
                          <div 
                            className="growing-circle"
                            style={{
                              position: 'absolute',
                              left: '-10px',
                              top: '10px',
                              width: '60px',
                              height: '60px',
                              borderRadius: '50%',
                              backgroundColor: 'rgba(14, 63, 46, 0.2)',
                              zIndex: 0,
                              animation: 'growCircle 2s ease-in-out infinite'
                            }}
                          />
                        )}
                        
                        <div 
                          style={{
                            position: 'relative',
                            fontWeight: 700,
                            lineHeight: '30px'
                          }}
                        >
                          <span 
                            className={`step-number ${index === activeStep ? 'scale-110' : ''}`}
                            style={{
                              position: 'absolute',
                              left: '-60px',
                              top: '14px',
                              width: '2.5rem',
                              height: '40px',
                              lineHeight: '40px',
                              display: 'flex',
                              alignItems: 'center',
                              justifyContent: 'center',
                              borderRadius: '9999px',
                              backgroundColor: index === activeStep ? '#0E3F2E' : '#D9D9D9',
                              color: index === activeStep ? '#FFFFFF' : '#FFFFFF',
                              zIndex: 2,
                              cursor: 'pointer',
                              fontFamily: "'DM Sans', sans-serif",
                              transition: 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
                              fontSize: '17px',
                              fontWeight: 700
                            }}
                          >
                            {index + 1}
                          </span>
                          <h3 
                            style={{
                              fontFamily: "'DM Sans', sans-serif",
                              fontWeight: 700,
                              fontSize: '18px',
                              lineHeight: '28px',
                              margin: '0px',
                              cursor: 'pointer',
                              transition: 'all 0.3s ease',
                              color: index === activeStep ? '#181818' : '#ABABAB'
                            }}
                            className="text-lg md:text-xl"
                          >
                            {step.title}
                          </h3>
                        </div>
                        <div 
                          style={{
                            maxHeight: index === activeStep ? '200px' : '0',
                            opacity: index === activeStep ? 1 : 0,
                            overflow: 'hidden',
                            transition: 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)',
                            fontFamily: "'DM Sans', sans-serif"
                          }}
                      >
                        <p style={{
                          margin: '0px',
                          paddingTop: '10px',
                          marginBottom: '0px',
                          fontSize: '15px',
                          lineHeight: '1.6',
                          color: '#4A4A4A'
                        }}>
                          {step.description}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>

            {/* Right Image Column */}
            <div className="lg:col-span-3">
              <div style={{
                position: 'relative',
                display: 'flex',
                alignItems: 'center',
                height: '100%',
                minHeight: '500px',
                marginRight: '0'
              }}>
                <div style={{
                  position: 'relative',
                  display: 'block',
                  width: '100%',
                  height: '100%',
                  padding: '20px 10px'
                }}>
                  <div style={{
                    display: 'block',
                    width: '100%',
                    height: '100%'
                  }}>
                    <div 
                      className="image-container-wrapper"
                      style={{
                        display: 'flex',
                        flexDirection: 'row',
                        gap: '16px',
                        height: '100%',
                        alignItems: 'stretch'
                      }}
                    >
                      {steps[activeStep].images.map((image, index) => (
                        <div 
                          key={index}
                          className={`image-box ${expandedImage === index ? 'expanded' : ''}`}
                          style={{
                            cursor: 'pointer',
                            overflow: 'hidden',
                            borderRadius: '12px',
                            position: 'relative',
                            transition: 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)',
                            transform: expandedImage === index ? 'scale(1.03)' : 'scale(1)',
                            zIndex: expandedImage === index ? 10 : 1,
                            boxShadow: expandedImage === index 
                              ? '0 20px 40px rgba(0, 0, 0, 0.15)' 
                              : '0 4px 16px rgba(0, 0, 0, 0.08)',
                            background: '#fff'
                          }}
                          onClick={() => handleImageClick(index)}
                        >
                          <div style={{
                            position: 'relative',
                            height: '100%',
                            width: '100%',
                            overflow: 'hidden'
                          }}>
                            {/* Subtle blend overlay */}
                            <div style={{
                              position: 'absolute',
                              top: 0,
                              left: 0,
                              right: 0,
                              bottom: 0,
                              background: 'linear-gradient(rgba(14, 63, 46, 0.1), rgba(14, 63, 46, 0.3))',
                              zIndex: 1,
                              pointerEvents: 'none'
                            }} />
                            
                            {/* Shimmer effect on hover */}
                            <div style={{
                              position: 'absolute',
                              top: 0,
                              left: 0,
                              right: 0,
                              bottom: 0,
                              background: 'linear-gradient(to bottom, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%)',
                              transform: 'translateY(-100%)',
                              zIndex: 1,
                              pointerEvents: 'none'
                            }} 
                            className="shimmer-effect"
                            />
                            
                            <img 
                              decoding="async" 
                              src={image} 
                              alt={`${steps[activeStep].title} - Image ${index + 1}`} 
                              style={{
                                display: 'block',
                                width: '100%',
                                height: '100%',
                                objectFit: 'cover',
                                transition: 'transform 0.6s cubic-bezier(0.4, 0, 0.2, 1)',
                                verticalAlign: 'middle'
                              }} 
                              onMouseEnter={(e) => {
                                e.currentTarget.style.transform = 'scale(1.08)';
                                const shimmer = e.currentTarget.previousElementSibling as HTMLElement;
                                if (shimmer) shimmer.style.animation = 'shimmer 1s ease';
                              }}
                              onMouseLeave={(e) => {
                                e.currentTarget.style.transform = 'scale(1)';
                                const shimmer = e.currentTarget.previousElementSibling as HTMLElement;
                                if (shimmer) shimmer.style.animation = 'none';
                              }}
                            />
                            
                            {/* Desktop overlay on hover */}
                            <div 
                              className="image-overlay-desktop"
                              style={{
                                display: 'none',
                                position: 'absolute',
                                top: 0,
                                left: 0,
                                right: 0,
                                bottom: 0,
                                background: 'linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, transparent 100%)',
                                alignItems: 'flex-end',
                                justifyContent: 'center',
                                padding: '20px',
                                pointerEvents: 'none'
                              }}
                            >
                              <div style={{
                                color: '#FFFFFF',
                                fontSize: '14px',
                                fontWeight: 600,
                                fontFamily: "'DM Sans', sans-serif",
                                textAlign: 'center',
                                letterSpacing: '0.5px'
                              }}>
                                View {index + 1}
                              </div>
                            </div>
                            
                            {/* Mobile overlay - always visible */}
                            <div 
                              className="image-overlay-mobile"
                              style={{
                                display: 'none',
                                position: 'absolute',
                                bottom: 0,
                                left: 0,
                                right: 0,
                                background: 'linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.4) 60%, transparent 100%)',
                                padding: '12px 8px',
                                alignItems: 'center',
                                justifyContent: 'center',
                                pointerEvents: 'none'
                              }}
                            >
                              <div style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: '6px'
                              }}>
                                <div style={{
                                  width: '24px',
                                  height: '24px',
                                  borderRadius: '50%',
                                  backgroundColor: expandedImage === index ? '#0E3F2E' : 'rgba(255,255,255,0.3)',
                                  color: expandedImage === index ? '#FFFFFF' : '#FFFFFF',
                                  display: 'flex',
                                  alignItems: 'center',
                                  justifyContent: 'center',
                                  fontSize: '11px',
                                  fontWeight: 700,
                                  fontFamily: "'DM Sans', sans-serif",
                                  transition: 'all 0.3s ease'
                                }}>
                                  {index + 1}
                                </div>
                                <span style={{
                                  color: '#FFFFFF',
                                  fontSize: '11px',
                                  fontWeight: 600,
                                  fontFamily: "'DM Sans', sans-serif",
                                  textShadow: '0 2px 4px rgba(0,0,0,0.5)'
                                }}>
                                  {steps[activeStep].title}
                                </span>
                              </div>
                            </div>
                            
                            {/* Expanded image title */}
                            {expandedImage === index && (
                              <div style={{
                                position: 'absolute',
                                bottom: 0,
                                left: 0,
                                right: 0,
                                background: 'linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 60%, transparent 100%)',
                                padding: '20px 16px',
                                pointerEvents: 'none',
                                zIndex: 3
                              }}>
                                <div style={{
                                  color: '#FFFFFF',
                                  fontSize: '16px',
                                  fontWeight: 700,
                                  fontFamily: "'DM Sans', sans-serif",
                                  textAlign: 'center',
                                  letterSpacing: '0.5px'
                                }}>
                                  {steps[activeStep].title} - Image {index + 1}
                                </div>
                              </div>
                            )}
                          </div>
                        </div>
                      ))}
                    </div>
                     {/* Mobile Book Now Button */}
                <button 
                  className="book-now-button"
                  style={{
                    backgroundColor: '#0E3F2E',
                    color: '#FFFFFF',
                    border: 'none',
                    borderRadius: '8px',
                    padding: '14px 24px',
                    fontSize: '16px',
                    fontWeight: 700,
                    fontFamily: "'DM Sans', sans-serif",
                    cursor: 'pointer',
                    transition: 'all 0.3s ease',
                    boxShadow: '0 4px 12px rgba(14, 63, 46, 0.2)',
                    width: '100%',
                    maxWidth: '300px',
                    margin: '30px auto 0'
                  }}
                  onMouseEnter={(e) => {
                    e.currentTarget.style.backgroundColor = '#155d42';
                    e.currentTarget.style.transform = 'translateY(-2px)';
                    e.currentTarget.style.boxShadow = '0 6px 16px rgba(14, 63, 46, 0.3)';
                  }}
                  onMouseLeave={(e) => {
                    e.currentTarget.style.backgroundColor = '#0E3F2E';
                    e.currentTarget.style.transform = 'translateY(0)';
                    e.currentTarget.style.boxShadow = '0 4px 12px rgba(14, 63, 46, 0.2)';
                  }}
                >
                  Book Now
                </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
    </>
  );
}