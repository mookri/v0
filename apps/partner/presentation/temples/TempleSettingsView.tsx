import Link from 'next/link';

interface TempleSettingsViewProps {
  settings: {
    temple: {
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
      status: string;
      verification_status: string;
    };
    managers: Array<{
      id: string;
      user_id: string;
      role: string;
      invited_at: string;
      accepted_at: string | null;
      user: {
        id: string;
        name: string;
        email: string;
      } | null;
    }>;
    amenities: string[];
  };
}

export function TempleSettingsView({ settings }: TempleSettingsViewProps) {
  const { temple, managers, amenities } = settings;

  return (
    <div className="space-y-8">
      {/* Temple Details */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Temple Details</h3>
        <div className="space-y-4">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Name</label>
              <p className="text-gray-900">{temple.name}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Trust Name</label>
              <p className="text-gray-900">{temple.trust_name}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Type</label>
              <p className="text-gray-900 capitalize">{temple.type}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Verification</label>
              <p className="text-gray-900">{settings.temple.verification_status}</p>
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-500 mb-1">Address</label>
            <p className="text-gray-900">{settings.temple.address}</p>
          </div>

          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">City</label>
              <p className="text-gray-900">{temple.city}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">State</label>
              <p className="text-gray-900">{temple.state}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Latitude</label>
              <p className="text-gray-900">{temple.lat?.toFixed(6) ?? 'Not set'}</p>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-500 mb-1">Longitude</label>
              <p className="text-gray-900">{temple.lng?.toFixed(6) ?? 'Not set'}</p>
            </div>
          </div>
        </div>
      </div>

      {/* Amenities */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Amenities & Facilities</h3>
        <div className="flex flex-wrap gap-2">
          {settings.amenities.map((amenity) => (
            <span
              key={amenity}
              className="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full"
            >
              {amenity.replace(/_/g, ' ')}
            </span>
          ))}
        </div>
      </div>

      {/* Managers */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <div className="flex items-center justify-between mb-6">
          <h3 className="text-lg font-semibold text-gray-900">Temple Managers</h3>
          <Link
            href={`/partner/temples/${settings.temple.slug}/managers/invite`}
            className="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 21a6 6 0 0112 0H3z" />
            </svg>
            Invite Manager
          </Link>
        </div>

        <div className="space-y-4">
          {settings.managers.length === 0 ? (
            <p className="text-gray-500 text-center py-8">No managers yet. Invite your first manager to get started.</p>
          ) : (
            <div className="space-y-3">
              {settings.managers.map((manager) => (
                <div
                  key={manager.id}
                  className="bg-white rounded-lg border border-gray-200 p-4 flex items-center justify-between"
                >
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                      <span className="text-lg font-bold text-indigo-700">
                        {manager.user?.name?.charAt(0).toUpperCase() ?? manager.user_id.charAt(0).toUpperCase()}
                      </span>
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">{manager.user?.name ?? 'Unknown User'}</p>
                      <p className="text-sm text-gray-500">{manager.user?.email ?? manager.user_id}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                      manager.role === 'owner' ? 'bg-purple-100 text-purple-800' :
                      manager.role === 'admin' ? 'bg-blue-100 text-blue-800' :
                      manager.role === 'manager' ? 'bg-green-100 text-green-800' :
                      'bg-gray-100 text-gray-800'
                    } capitalize`}>
                      {manager.role}
                    </span>
                    {manager.accepted_at ? (
                      <span className="text-sm text-gray-500">
                        Joined {new Date(manager.accepted_at).toLocaleDateString()}
                      </span>
                    ) : (
                      <span className="text-sm text-yellow-600">Pending acceptance</span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}