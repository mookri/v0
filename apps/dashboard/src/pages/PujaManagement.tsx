import { useState, useEffect } from 'react';
import { apiClient } from '../api/client';

interface Puja {
  id: string;
  sku: string;
  name: string;
  description?: string;
  status: 'DRAFT' | 'ACTIVE' | 'ARCHIVED';
  currency: string;
  price_minor: number;
  market: string;
  pandit_id?: number;
  created_at: string;
  updated_at: string;
}

export function PujaManagement() {
  const [pujas, setPujas] = useState<Puja[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState<'all' | 'ACTIVE' | 'DRAFT' | 'ARCHIVED'>('all');

  useEffect(() => {
    fetchPujas();
  }, []);

  const fetchPujas = async () => {
    try {
      const params = new URLSearchParams();
      if (statusFilter !== 'all') params.set('status', statusFilter);
      const data = await apiClient.get<{ data: Puja[] }>(`/api/catalog/items?${params}`);
      setPujas(data.data || []);
    } catch (err) {
      console.error('Failed to fetch pujas:', err);
    } finally {
      setLoading(false);
    }
  };

  const filteredPujas = pujas.filter((puja) =>
    puja.name.toLowerCase().includes(search.toLowerCase()) ||
    puja.sku.toLowerCase().includes(search.toLowerCase())
  );

  const formatPrice = (minor: number, currency: string) => {
    return new Intl.NumberFormat('en-IN', {
      style: 'currency',
      currency,
      minimumFractionDigits: 0,
    }).format(minor / 100);
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'ACTIVE': return 'bg-green-100 text-green-800';
      case 'DRAFT': return 'bg-yellow-100 text-yellow-800';
      case 'ARCHIVED': return 'bg-gray-100 text-gray-800';
      default: return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Puja Management</h1>
          <p className="text-gray-500 mt-1">Manage your puja catalog and pricing</p>
        </div>
        <button className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
          + Add Puja
        </button>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search pujas..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as typeof statusFilter)}
            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="all">All Statuses</option>
            <option value="ACTIVE">Active</option>
            <option value="DRAFT">Draft</option>
            <option value="ARCHIVED">Archived</option>
          </select>
        </div>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        {loading ? (
          <div className="p-8 text-center text-gray-500">Loading...</div>
        ) : (
          <table className="w-full">
            <thead className="bg-gray-50 border-b border-gray-200">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puja</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Market</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pandit</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filteredPujas.length === 0 ? (
                <tr>
                  <td colSpan={7} className="px-6 py-12 text-center text-gray-500">
                    No pujas found
                  </td>
                </tr>
              ) : (
                filteredPujas.map((puja) => (
                  <tr key={puja.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div>
                        <p className="font-medium text-gray-900">{puja.name}</p>
                        <p className="text-sm text-gray-500 truncate max-w-xs">{puja.description || 'No description'}</p>
                      </div>
                    </td>
                    <td className="px-6 py-4">
                      <code className="text-sm text-gray-600 font-mono">{puja.sku}</code>
                    </td>
                    <td className="px-6 py-4">
                      <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(puja.status)}`}>
                        {puja.status}
                      </span>
                    </td>
                    <td className="px-6 py-4 text-gray-900">{formatPrice(puja.price_minor, puja.currency)}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{puja.market.toUpperCase()}</td>
                    <td className="px-6 py-4 text-sm text-gray-500">{puja.pandit_id ? `Pandit #${puja.pandit_id}` : 'Base price'}</td>
                    <td className="px-6 py-4">
                      <div className="flex items-center gap-2">
                        <button className="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
                        <button className="text-gray-600 hover:text-gray-900 text-sm font-medium">Prices</button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}