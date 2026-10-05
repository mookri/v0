interface TempleBooking {
  id: string;
  puja_name: string;
  user_name: string;
  user_email: string;
  start_at: string;
  end_at: string;
  status: string;
  amount_minor: number;
}

interface TempleDashboardBookingsViewProps {
  bookings: TempleBooking[];
}

export function TempleDashboardBookingsView({ bookings }: TempleDashboardBookingsViewProps) {
  const formatCurrency = (minor: number) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency: 'INR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(minor / 100);
  };

  const renderBookingRow = (booking: TempleBooking) => (
    <tr key={booking.id} className="hover:bg-gray-50">
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{booking.puja_name}</td>
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        <div className="font-medium text-gray-900">{booking.user_name}</div>
        <div className="text-sm text-gray-500">{booking.user_email}</div>
      </td>
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        {new Date(booking.start_at).toLocaleDateString('en-IN', {
          day: 'numeric',
          month: 'short',
          year: 'numeric',
        })}
      </td>
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
        {new Date(booking.start_at).toLocaleTimeString('en-IN', {
          hour: '2-digit',
          minute: '2-digit',
        })} - {new Date(booking.end_at).toLocaleTimeString('en-IN', {
          hour: '2-digit',
          minute: '2-digit',
        })}
      </td>
      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
        {new Intl.NumberFormat('en-IN', {
          style: 'currency',
          currency: 'INR',
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(booking.amount_minor / 100)}
      </td>
      <td className="px-6 py-4 whitespace-nowrap">
        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
          booking.status === 'CONFIRMED' ? 'bg-green-100 text-green-800' :
          booking.status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' :
          booking.status === 'COMPLETED' ? 'bg-blue-100 text-blue-800' :
          booking.status === 'CANCELLED' ? 'bg-red-100 text-red-800' :
          'bg-gray-100 text-gray-800'
        }`}>
          {booking.status}
        </span>
      </td>
    </tr>
  );

  return (
    <div className="bg-white rounded-xl shadow-sm overflow-hidden">
      <div className="p-6 border-b">
        <h3 className="text-lg font-semibold text-gray-900">All Bookings</h3>
      </div>
      <div className="overflow-x-auto">
        <table className="w-full">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puja</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {bookings.length === 0 ? (
              <tr>
                <td colSpan={6} className="px-6 py-12 text-center text-gray-500">
                  No bookings found
                </td>
              </tr>
            ) : (
              bookings.map(renderBookingRow)
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}