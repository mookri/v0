export function DashboardHome() {
  const stats = [
    { label: 'Total Pujas', value: '142', change: '+12%', icon: '🕉️' },
    { label: 'Active Bookings', value: '89', change: '+5%', icon: '📅' },
    { label: 'Customers', value: '1,234', change: '+8%', icon: '👥' },
    { label: 'Revenue', value: '₹42.5L', change: '+15%', icon: '💰' },
  ];

  const recentActivity = [
    { id: 1, type: 'booking', message: 'New booking created for Ganesh Puja', time: '2 min ago', icon: '📅' },
    { id: 2, type: 'customer', message: 'New customer registered: Rahul Sharma', time: '15 min ago', icon: '👤' },
    { id: 3, type: 'puja', message: 'Saraswati Puja price updated', time: '1 hour ago', icon: '🕉️' },
    { id: 4, type: 'booking', message: 'Booking #BK-2024-001 rescheduled', time: '3 hours ago', icon: '📅' },
    { id: 5, type: 'customer', message: 'Customer Priya Patel completed verification', time: '5 hours ago', icon: '👤' },
  ];

  return (
    <div className="space-y-8">
      <header>
        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p className="text-gray-500 mt-1">Welcome back! Here's what's happening.</p>
      </header>

      <section className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat) => (
          <div key={stat.label} className="bg-white rounded-xl border border-gray-200 p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm text-gray-500">{stat.label}</p>
                <p className="text-3xl font-bold text-gray-900 mt-1">{stat.value}</p>
                <p className="text-sm text-green-600 mt-1">{stat.change} vs last month</p>
              </div>
              <div className="text-4xl">{stat.icon}</div>
            </div>
          </div>
        ))}
      </section>

      <section>
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
        <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activity</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {recentActivity.map((activity) => (
                <tr key={activity.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <span className="text-lg">{activity.icon}</span>
                      <p className="text-gray-900">{activity.message}</p>
                    </div>
                  </td>
                  <td className="px-6 py-4 text-sm text-gray-500">{activity.time}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </section>
    </div>
  );
}