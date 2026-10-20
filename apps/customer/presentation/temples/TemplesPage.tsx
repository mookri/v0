import Link from 'next/link';

interface Temple {
  id: string;
  slug: string;
  name: string;
  trust_name: string;
  type: string;
  city: string;
  state: string;
  lat: number | null;
  lng: number | null;
  images: string[];
  amenities: string[];
  description: string | null;
  rating_avg: number;
  review_count: number;
  verification_status: string;
  status: string;
}

export function TemplesPageView({ temples }: { temples: Temple[] }) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl font-bold mb-2">Temples & Sacred Spaces</h1>
        <p className="text-gray-600">Discover verified temples and sacred spaces for your ceremonies</p>
      </div>

      {temples.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500 mb-4">No temples available at the moment.</p>
          <Link href="/discover" className="text-indigo-600 hover:underline">
            Browse pujas instead
          </Link>
        </div>
      ) : (
        <ul className="space-y-4">
          {temples.map((temple) => (
            <li key={temple.id}>
              <Link
                href={`/temples/${temple.slug}`}
                className="block p-5 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-md transition-all"
              >
                <div className="flex items-start gap-4">
                  <div className="flex-shrink-0 w-16 h-16 bg-indigo-100 rounded-lg flex items-center justify-center overflow-hidden">
                    {temple.images.length > 0 ? (
                      <img
                        src={temple.images[0]}
                        alt={temple.name}
                        className="w-full h-full object-cover"
                      />
                    ) : (
                      <span className="text-2xl font-bold text-indigo-700">
                        {temple.name.charAt(0).toUpperCase()}
                      </span>
                    )}
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-4">
                      <div>
                        <h3 className="font-semibold text-gray-900 truncate">{temple.name}</h3>
                        <p className="text-sm text-gray-500 mt-1">{temple.trust_name}</p>
                      </div>
                      <span className="px-2 py-1 text-xs bg-green-100 text-green-700 rounded-full capitalize">
                        {temple.verification_status.toLowerCase()}
                      </span>
                    </div>
                    {temple.description && (
                      <p className="mt-2 text-gray-600 text-sm line-clamp-2">{temple.description}</p>
                    )}
                    <div className="mt-3 flex flex-wrap gap-2">
                      {temple.amenities.slice(0, 4).map((amenity) => (
                        <span key={amenity} className="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                          {amenity.replace(/_/g, ' ')}
                        </span>
                      ))}
                      {temple.amenities.length > 4 && (
                        <span className="px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">
                          +{temple.amenities.length - 4} more
                        </span>
                      )}
                    </div>
                    <div className="mt-3 flex items-center gap-4 text-sm text-gray-500">
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
                    </div>
                  </div>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </main>
  );
}