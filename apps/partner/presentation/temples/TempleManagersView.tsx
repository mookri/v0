import React from 'react';

interface TempleManager {
  id: string;
  temple_id: string;
  user_id: string;
  role: string;
  invited_at: string;
  accepted_at: string | null;
}

interface TempleManagersViewProps {
  templeId: string;
  managers: TempleManager[];
}

export function TempleManagersView({ templeId, managers }: TempleManagersViewProps) {
  return (
    <div className="p-6 max-w-4xl mx-auto">
      <h1 className="text-2xl font-bold mb-4">Temple Managers</h1>
      <p className="text-gray-500 mb-6">Manage who can administer this temple.</p>

      {managers.length === 0 ? (
        <div className="rounded-lg border border-dashed border-gray-300 p-8 text-center text-gray-500">
          No managers invited yet.
        </div>
      ) : (
        <div className="overflow-x-auto rounded-lg border border-gray-200">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
              <tr>
                <th className="px-4 py-3 font-semibold">User ID</th>
                <th className="px-4 py-3 font-semibold">Role</th>
                <th className="px-4 py-3 font-semibold">Invited</th>
                <th className="px-4 py-3 font-semibold">Accepted</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {managers.map((manager) => (
                <tr key={manager.id} data-testid="manager-row">
                  <td className="px-4 py-3 font-mono text-xs">{manager.user_id}</td>
                  <td className="px-4 py-3">{manager.role}</td>
                  <td className="px-4 py-3 text-gray-500">{new Date(manager.invited_at).toLocaleString()}</td>
                  <td className="px-4 py-3 text-gray-500">
                    {manager.accepted_at ? new Date(manager.accepted_at).toLocaleString() : 'Pending'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
