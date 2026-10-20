import Link from 'next/link';

interface Pandit {
  id: string;
  name: string;
  bio?: string;
  specializations: string[];
  market: string;
  languages: string[];
  experience_years: number;
  rating_avg: number;
  review_count: number;
}

export function PanditsPageView({ pandits }: { pandits: Pandit[] }) {
  return (
    <main className="p-6 max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold mb-6">Verified Pandits</h1>

      {pandits.length === 0 ? (
        <div className="text-center py-12">
          <p className="text-gray-500 mb-4">No verified pandits available at the moment.</p>
          <Link href="/discover" className="text-indigo-600 hover:underline">
            Browse pujas instead
          </Link>
        </div>
      ) : (
        <ul className="space-y-4">
          {pandits.map((pandit) => (
            <li key={pandit.id}>
              <Link
                href={`/pandits/${pandit.id}`}
                className="block p-5 bg-white border border-gray-200 rounded-lg hover:border-indigo-300 hover:shadow-md transition-all"
              >
                <div className="flex items-start gap-4">
                  <div className="flex-shrink-0 w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span className="text-2xl font-bold text-indigo-700">
                      {pandit.name.charAt(0).toUpperCase()}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-4">
                      <h3 className="font-semibold text-gray-900 truncate">{pandit.name}</h3>
                      <span className="flex items-center gap-1 text-sm text-gray-500">
                        <svg className="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        {pandit.rating_avg.toFixed(1)} ({pandit.review_count})
                      </span>
                    </div>
                    {pandit.bio && (
                      <p className="mt-2 text-gray-600 text-sm line-clamp-2">{pandit.bio}</p>
                    )}
                    <div className="mt-3 flex flex-wrap gap-2">
                      {pandit.specializations.slice(0, 3).map((spec) => (
                        <span key={spec} className="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded-full">
                          {spec}
                        </span>
                      ))}
                      {pandit.specializations.length > 3 && (
                        <span className="px-2 py-1 text-xs bg-gray-100 text-gray-500 rounded-full">
                          +{pandit.specializations.length - 3} more
                        </span>
                      )}
                    </div>
                    <div className="mt-3 flex items-center gap-4 text-sm text-gray-500">
                      <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {pandit.market}
                      </span>
                      <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        {pandit.languages.join(', ')}
                      </span>
                      <span className="flex items-center gap-1">
                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {pandit.experience_years} yrs
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