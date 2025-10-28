"use client";
import { useState } from "react";
import "./ProcessSteps.css";

export function ProcessSteps() {
  const [activeStep, setActiveStep] = useState(0);
  const [expandedImage, setExpandedImage] = useState<number | null>(null);

  const steps = [
    {
      title: "Select Your Ritual",
      description: "Choose from our comprehensive puja catalog",
      images: [
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680301/Whisk_b79ae5ba729b83cafd54991c00e8c3eceg_hvtgu7.png",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680516/Whisk_551db29e967110fa359451e9db7c6274eg_jdcdpy.png",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680691/Whisk_3dd8aeccb36feaa9cdc41fd86d9c8678dr_xk4an7.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680843/Whisk_fbe5ec7e748e65687974044ae3cef9d7dr_x8hrcm.jpg"
      ],
    },
    {
      title: "Pick Date & Time",
      description: "Select a convenient time for your ritual",
      images: [
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358807/Whisk_durga_puja_festival.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358809/Whisk_navratri_puja_celebration.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358811/Whisk_baby_naming_ceremony.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358813/Whisk_shraddh_ceremony.jpg"
      ],
    },
    {
      title: "We Handle Everything",
      description: "Pandit, samagri, setup — arrive & experience divinity",
      images: [
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358815/Whisk_rudrabhishek_shiva.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358817/Whisk_lakshmi_puja_ritual.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358819/Whisk_satyanarayan_katha.jpg",
        "https://res.cloudinary.com/dufzctlaj/image/upload/v1760358821/Whisk_griha_pravesh_setup.jpg"
      ],
    },
  ];

  const handleStepClick = (index: number) => {
    setActiveStep(index);
    setExpandedImage(null);
  };

  const handleImageClick = (index: number) => {
    setExpandedImage(expandedImage === index ? null : index);
  };

  return (
    <section className="process-section" id="process">
      <div className="process-wrapper">
        {/* LEFT CONTENT - STEPS */}
        <div className="process-left">
          <p className="process-tagline">SACRED PROCESS — SIMPLE & HASSLE-FREE</p>
          <h2 className="process-title">Your Journey to Divine Experience</h2>

          <div className="process-steps">
            {steps.map((step, index) => (
              <div
                key={index}
                className={`step-item ${activeStep === index ? "active" : ""}`}
                onClick={() => handleStepClick(index)}
                role="button"
                aria-label={`Go to step ${index + 1}: ${step.title}`}
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    handleStepClick(index);
                  }
                }}
              >
                <div className="step-number">{index + 1}</div>
                <div className="step-content">
                  <h3>{step.title}</h3>
                  <p>{step.description}</p>
                </div>
              </div>
            ))}
            <div className="step-line" />
          </div>
        </div>

        {/* RIGHT CONTENT - IMAGES */}
        <div className="process-right">
          <div className="image-grid">
            {steps[activeStep].images.map((src, index) => (
              <div
                key={`${activeStep}-${index}`}
                className={`image-card ${expandedImage === index ? "expanded" : ""}`}
                onClick={() => handleImageClick(index)}
                role="button"
                aria-label={`View image ${index + 1} for ${steps[activeStep].title}`}
                tabIndex={0}
                onKeyDown={(e) => {
                  if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    handleImageClick(index);
                  }
                }}
              >
                <img src={src} alt={`${steps[activeStep].title} - Image ${index + 1}`} loading="lazy" />
                <div className="image-overlay" />
                <div className="image-number">{index + 1}</div>
                <div className="image-title">{steps[activeStep].title}</div>
              </div>
            ))}
          </div>

          <button className="book-now-button" onClick={() => {
            const bookingSection = document.getElementById('booking');
            if (bookingSection) {
              bookingSection.scrollIntoView({ behavior: 'smooth' });
            }
          }}>Book Now</button>
        </div>
      </div>
    </section>
  );
}