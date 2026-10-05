interface TempleAnalyticsViewProps {
  analytics: {
    revenue_by_month: Array<{ month: string; revenue_minor: int; bookings: int }>;
    occupancy_by_puja: Array<{ puja_name: string; occupancy: float; bookings: int }>;
    peak_hours: Array<{ hour: int; bookings: int }>;
  };
}

export function TempleAnalyticsView({ analytics }: TempleAnalyticsViewProps) {
  const formatCurrency = (minor: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(minor / 100);
  };

  return (
    <div className="space-y-8">
      {/* Revenue by Month */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Revenue by Month</h3>
        {analytics.revenue_by_month.length === 0 ? (
          <p className="text-gray-500 text-center py-8">No revenue data available</p>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            {analytics.revenue_by_month.map((month) => (
              <div
                key={month.month}
                className="bg-gray-50 rounded-lg p-4 text-center"
              >
                <p className="text-sm text-gray-500 mb-1">{month.month}</p>
                <p className="text-xl font-bold text-gray-900">
                  {new Intl.NumberFormat('en-IN', {
                    style: 'currency',
                    currency: 'INR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                  }).format(month.revenue_minor / 100)}
                </p>
                <p className="text-sm text-gray-500 mt-1">{month.bookings} bookings</p>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Occupancy by Puja */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Occupancy by Puja</h3>
        {analytics.occupancy_by_puja.length === 0 ? (
          <p className="text-gray-500 text-center py-8">No occupancy data available</p>
        ) : (
          <div className="space-y-4">
            {analytics.occupancy_by_puja.map((item) => (
              <div key={item.puja_name} className="bg-white rounded-lg border border-gray-200 p-4">
                <div className="flex items-center justify-between mb-2">
                  <span className="font-medium text-gray-900">{item.puja_name}</span>
                  <span className="text-sm font-medium text-indigo-600">{item.occupancy.toFixed(1)}%</span>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2">
                  <div
                    className="bg-indigo-600 h-2 rounded-full transition-all duration-300"
                    style={{ width: `${Math.min(item.occupancy, 100)}%` }}
                  ></div>
                </div>
                <div className="flex justify-between text-sm text-gray-500 mt-2">
                  <span>{item.bookings} bookings</span>
                  <span>{item.occupancy.toFixed(1)}% occupancy</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Peak Hours */}
      <div className="bg-white rounded-xl shadow-sm p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-6">Peak Booking Hours</h3>
        {analytics.peak_hours.length === 0 ? (
          <p className="text-gray-500 text-center py-8">No peak hours data available</p>
        ) : (
          <div className="grid grid-cols-4 md:grid-cols-8 lg:grid-cols-12 gap-4">
            {analytics.peak_hours.map((hour) => (
              <div
                key={hour.hour}
                className={`relative h-24 rounded-lg flex flex-col items-end justify-end p-3 ${
                  hour.bookings > 5 ? 'bg-indigo-100' : 'bg-gray-50'
                }`}
              >
                <div
                  className="absolute bottom-0 left-0 right-0 bg-indigo-600 rounded-t"
                  style={{ height: `${Math.min(hour.bookings / 10 * 100, 100)}%` }}
                />
                <span className="absolute bottom-2 left-1/2 -translate-x-1/2 text-xs font-medium text-white">
                  {hour.bookings}
                </span>
                <div className="absolute bottom-0 left-0 right-0 text-center text-xs text-gray-500">
                  {hour.hour}:00
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}