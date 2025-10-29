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
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680301/Whisk_b79ae5ba729b83cafd54991c00e8c3eceg_hvtgu7.png",
          description: "Explore Intent"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680516/Whisk_551db29e967110fa359451e9db7c6274eg_jdcdpy.png",
          description: "Browse Puja Categories"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680691/Whisk_3dd8aeccb36feaa9cdc41fd86d9c8678dr_xk4an7.jpg",
          description: "View Ritual Details"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761680843/Whisk_fbe5ec7e748e65687974044ae3cef9d7dr_x8hrcm.jpg",
          description: "Add to Your Cart or Wishlist"
        }
      ],
    },
    {
      title: "Pick Date & Time",
      description: "Select a convenient time for your ritual",
      images: [
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761766531/step-02-01-navdhya_kzhldl.jpg",
          description: "Check Availability"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761766709/step-02-02-navdhya_qecw1d.jpg",
          description: "Select Auspicious Muhurat"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767149/step-02-03-navdhya_btt8kw.png",
          description: "Confirm Timing Preferences"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767321/step-02-04-navdhya-01_x3zlda.jpg",
          description: "Receive Confirmation"
        }
      ],
    },
    {
      title: "We Handle Everything",
      description: "Pandit, samagri, setup — arrive & experience divinity",
      images: [
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767902/step-03-01-navdhya_fkuzls.jpg",
          description: "Verified Pandits Assigned"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767903/step-03-03-navdhya_2_bn7mus.jpg",
          description: "Samagri Kit Prepared"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761767904/step-03-03-navdhya_rcfmu6.jpg",
          description: "Setup & Decoration"
        },
        {
          url: "https://res.cloudinary.com/dufzctlaj/image/upload/v1761768016/step-03-04_urqedn.jpg",
          description: "Ceremony Assistance"
        }
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
            {/* <div className="step-line" /> */}
          </div>
        </div>

        {/* RIGHT CONTENT - IMAGES */}
        <div className="process-right">
          <div className="image-grid">
            {steps[activeStep].images.map((image, index) => (
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
                <img src={image.url} alt={`${steps[activeStep].title} - Image ${index + 1}`} loading="lazy" />
                <div className="image-overlay" />
                <div className="image-number">{index + 1}</div>
                <div className="image-description">{image.description}</div>
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