import React from 'react';
import { Shield, Handshake, Layers, Star } from 'lucide-react';

const ValuesSection = () => {
  const values = [
    {
      icon: Shield,
      title: 'Fiduciary',
      description: 'A commitment to acting in your best interest for long-term financial wellbeing.'
    },
    {
      icon: Handshake,
      title: 'Integrated',
      description: 'Comprehensive wealth services, tailored solutions, and exclusive alternative investments.'
    },
    {
      icon: Layers,
      title: 'Highly Personalized',
      description: 'A customized approach driven by proactive ideas and strategic problem-solving.'
    },
    {
      icon: Star,
      title: 'Experienced',
      description: 'A highly dedicated team, supported by our extensive network of trusted professionals.'
    }
  ];

  return (
    <div className="min-h-screen bg-gray-50 px-6 py-16">
      <div className="max-w-7xl mx-auto">
        {/* Header Section */}
        <div className="mb-16">
          <div className="inline-block px-4 py-2 bg-white border border-gray-200 rounded-full text-sm text-gray-600 mb-6">
            VALUES
          </div>
          
          <div className="grid lg:grid-cols-2 gap-12 items-start">
            <h1 className="text-5xl lg:text-6xl font-light text-gray-800 leading-tight">
              We Operate with<br />
              Unwavering <span className="text-lime-500">Honesty</span> &<br />
              Integrity
            </h1>
            
            <p className="text-gray-600 text-lg leading-relaxed lg:mt-8">
              We offer a deeply personalized approach, providing objective, 
              customized advice, seamless wealth management, and exclusive 
              access to private investments, all designed to help clients reach 
              their goals and build lasting wealth.
            </p>
          </div>
        </div>

        {/* Cards Section */}
        <div className="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
          {values.map((value, index) => {
            const Icon = value.icon;
            return (
              <div 
                key={index}
                className="bg-white rounded-3xl p-8 border border-gray-200 hover:shadow-md transition-shadow"
              >
                <div className="w-14 h-14 bg-lime-400 rounded-full flex items-center justify-center mb-24">
                  <Icon className="w-6 h-6 text-gray-800" />
                </div>
                
                <div className="bg-gray-50 rounded-2xl p-6 min-h-[180px]">
                  <h3 className="text-xl font-medium text-gray-800 mb-3">
                    {value.title}
                  </h3>
                  
                  <p className="text-gray-600 text-sm leading-relaxed">
                    {value.description}
                  </p>
                </div>
              </div>
            );
          })}
        </div>
      </div>
    </div>
  );
};

export default ValuesSection;