import Link from 'next/link';
import Image from 'next/image';

interface Temple {
  id: string;
  slug: string;
  name: string;
  trust_name: string;
  type: string;
  address: string;
  city: string;
  state: string;
  lat: number | null;
  lng: number | null;
  images: string[];
  amenities: string[];
  description: string | null;
  verification_status: string;
  status: string;
  created_at: string;
}

interface TempleDetailPageViewProps {
  temple: Temple;
}

export function TempleDetailPageView({ temple }: TempleDetailPageViewProps) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <Link
        href="/temples"
        className="inline-flex items-center text-indigo-600 hover:text-indigo-800 mb-6"
      >
        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
        </svg>
        Back to Temples
      </Link>

      <div className="space-y-8">
        {/* Hero Section */}
        <div className="rounded-xl overflow-hidden bg-white shadow-sm">
          {temple.images.length > 0 ? (
            <div className="relative h-80 md:h-96">
              <Image
                src={temple.images[0]}
                alt={temple.name}
                fill
                className="object-cover"
                priority
                sizes="(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
              <div className="absolute bottom-6 left-6 right-6 text-white">
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 capitalize">
                  {temple.verification_status.toLowerCase()}
                </span>
                <span className="ml-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 capitalize">
                  {temple.type === 'trust' ? 'Trust' : 'Individual'}
                </span>
              </div>
            </div>
          ) : (
            <div className="h-80 md:h-96 bg-indigo-100 flex items-center justify-center">
              <span className="text-6xl font-bold text-indigo-700">
                {temple.name.charAt(0).toUpperCase()}
              </span>
            </div>
          )}

          <div className="p-6 md:p-8">
            <div className="flex items-start justify-between gap-4 mb-4">
              <div>
                <h1 className="text-3xl font-bold text-gray-900">{temple.name}</h1>
                <p className="text-gray-500 mt-1">{temple.trust_name}</p>
              </div>
              <div className="flex items-center gap-2">
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 capitalize">
                  {temple.verification_status.toLowerCase()}
                </span>
                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 capitalize">
                  {temple.type === 'trust' ? 'Trust' : 'Individual'}
                </span>
              </div>
            </div>

            <div className="flex flex-wrap items-center gap-4 text-sm text-gray-500">
              <span className="flex items-center gap-1">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {temple.city}, {temple.state}
              </span>
              <span className="flex items-center gap-1">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {temple.type === 'trust' ? 'Trust' : 'Individual'}
              </span>
              <span className="flex items-center gap-1">
                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Member since {new Date(temple.created_at).getFullYear()}
              </span>
            </div>
          </div>
        </div>

        {/* Description */}
        {temple.description && (
          <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">About this Temple</h2>
            <p className="text-gray-600 leading-relaxed whitespace-pre-wrap">{temple.description}</p>
          </div>
        )}

        {/* Amenities */}
        {temple.amenities.length > 0 && (
          <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm">
            <h2 className="text-xl font-semibold text-gray-900 mb-4">Amenities & Facilities</h2>
            <div className="flex flex-wrap gap-2">
              {temple.amenities.map((amenity) => (
                <span
                  key={amenity}
                  className="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full"
                >
                  {amenity.replace(/_/g, ' ')}
                </span>
              ))}
            </div>
          </div>
        )}

        {/* Address & Location */}
        <div className="bg-white rounded-xl p-6 md:p-8 shadow-sm">
          <h2 className="text-xl font-semibold text-gray-900 mb-4">Location</h2>
          <address className="not-italic text-gray-600">
            <p>{temple.address}</p>
            <p>{temple.city}, {temple.state}</p>
            {temple.lat && temple.lng && (
              <p className="mt-2 text-sm text-gray-500">
                Coordinates: {temple.lat.toFixed(6)}, {temple.lng.toFixed(6)}
              </p>
            )}
          </address>
          {temple.lat && temple.lng && (
            <div className="mt-4">
              <a
                href={`https://maps.google.com/?q=${temple.lat},${temple.lng}`}
                target="_blank"
                rel="noopener noreferrer"
                className="inline-flex items-center text-indigo-600 hover:text-indigo-800 text-sm font-medium"
              >
                <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                View on Google Maps
              </a>
            </div>
          )}
        </div>

        {/* Book Puja CTA */}
        <div className="bg-indigo-600 rounded-xl p-6 md:p-8 text-center">
          <h3 className="text-xl font-semibold text-white mb-2">Ready to Book a Puja?</h3>
          <p className="text-indigo-100 mb-4 max-w-md mx-auto">Browse available pujas and book your ceremony at this temple.</p>
          <Link
            href={`/temples/${temple.slug}/pujas`}
            className="inline-flex items-center px-6 py-3 bg-white text-indigo-600 rounded-lg font-semibold hover:bg-indigo-50 transition-colors"
          >
            View Available Pujas
            <svg className="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
            </svg>
          </Link>
        </div>
      </div>
    </main>
  );
}