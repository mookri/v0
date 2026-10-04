import { useState } from 'react';

interface SettingsTab {
  id: string;
  label: string;
  icon: string;
}

export function Settings() {
  const [activeTab, setActiveTab] = useState('general');
  const [formData, setFormData] = useState({
    // General
    siteName: 'Navdhya',
    siteUrl: 'https://navdhya.com',
    supportEmail: 'support@navdhya.com',
    timezone: 'Asia/Kolkata',
    currency: 'INR',
    // Notifications
    emailNotifications: true,
    bookingReminders: true,
    marketingEmails: false,
    // Security
    twoFactorEnabled: false,
    sessionTimeout: 30,
    passwordMinLength: 8,
    // API
    apiRateLimit: 100,
    webhookUrl: '',
    webhookSecret: '',
  });

  const tabs: SettingsTab[] = [
    { id: 'general', label: 'General', icon: '⚙️' },
    { id: 'notifications', label: 'Notifications', icon: '🔔' },
    { id: 'security', label: 'Security', icon: '🔒' },
    { id: 'api', label: 'API', icon: '🔌' },
  ];

  const handleChange = (field: string, value: string | boolean | number) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const handleSave = () => {
    // In a real app, this would call an API
    console.log('Saving settings:', formData);
    alert('Settings saved! (Demo only)');
  };

  const renderGeneral = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-gray-900">Basic Information</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Site Name</label>
          <input
            type="text"
            value={formData.siteName}
            onChange={(e) => handleChange('siteName', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Site URL</label>
          <input
            type="url"
            value={formData.siteUrl}
            onChange={(e) => handleChange('siteUrl', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Support Email</label>
          <input
            type="email"
            value={formData.supportEmail}
            onChange={(e) => handleChange('supportEmail', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Timezone</label>
          <select
            value={formData.timezone}
            onChange={(e) => handleChange('timezone', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="Asia/Kolkata">Asia/Kolkata (UTC+5:30)</option>
            <option value="UTC">UTC</option>
            <option value="America/New_York">America/New_York (UTC-5)</option>
            <option value="Europe/London">Europe/London (UTC+0)</option>
          </select>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Default Currency</label>
          <select
            value={formData.currency}
            onChange={(e) => handleChange('currency', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="INR">INR (₹)</option>
            <option value="USD">USD ($)</option>
            <option value="EUR">EUR (€)</option>
            <option value="GBP">GBP (£)</option>
          </select>
        </div>
      </div>
    </div>
  );

  const renderNotifications = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-gray-900">Email Notifications</h3>
      <div className="space-y-4">
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            checked={formData.emailNotifications}
            onChange={(e) => handleChange('emailNotifications', e.target.checked)}
            className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <span className="text-gray-700 font-medium">Enable email notifications</span>
        </label>
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            checked={formData.bookingReminders}
            onChange={(e) => handleChange('bookingReminders', e.target.checked)}
            className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <span className="text-gray-700 font-medium">Booking reminders (24h before)</span>
        </label>
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            checked={formData.marketingEmails}
            onChange={(e) => handleChange('marketingEmails', e.target.checked)}
            className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <span className="text-gray-700 font-medium">Marketing & promotional emails</span>
        </label>
      </div>
    </div>
  );

  const renderSecurity = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-gray-900">Authentication</h3>
      <div className="space-y-4">
        <label className="flex items-center gap-3 cursor-pointer">
          <input
            type="checkbox"
            checked={formData.twoFactorEnabled}
            onChange={(e) => handleChange('twoFactorEnabled', e.target.checked)}
            className="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
          />
          <span className="text-gray-700 font-medium">Require two-factor authentication for staff</span>
        </label>
      </div>

      <h3 className="text-lg font-semibold text-gray-900">Session Management</h3>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Session Timeout (minutes)</label>
          <input
            type="number"
            min="5"
            max="480"
            value={formData.sessionTimeout}
            onChange={(e) => handleChange('sessionTimeout', parseInt(e.target.value) || 30)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Minimum Password Length</label>
          <input
            type="number"
            min="6"
            max="32"
            value={formData.passwordMinLength}
            onChange={(e) => handleChange('passwordMinLength', parseInt(e.target.value) || 8)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
      </div>
    </div>
  );

  const renderApi = () => (
    <div className="space-y-6">
      <h3 className="text-lg font-semibold text-gray-900">API Configuration</h3>
      <div className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Rate Limit (requests/minute)</label>
          <input
            type="number"
            min="10"
            max="10000"
            value={formData.apiRateLimit}
            onChange={(e) => handleChange('apiRateLimit', parseInt(e.target.value) || 100)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
          <input
            type="url"
            value={formData.webhookUrl}
            onChange={(e) => handleChange('webhookUrl', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="https://your-domain.com/webhook"
          />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Webhook Secret</label>
          <input
            type="password"
            value={formData.webhookSecret}
            onChange={(e) => handleChange('webhookSecret', e.target.value)}
            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="Leave blank to keep current"
          />
        </div>
      </div>
    </div>
  );

  const renderTab = () => {
    switch (activeTab) {
      case 'general': return renderGeneral();
      case 'notifications': return renderNotifications();
      case 'security': return renderSecurity();
      case 'api': return renderApi();
      default: return renderGeneral();
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900">Settings</h1>
        <p className="text-gray-500 mt-1">Configure your dashboard preferences</p>
      </div>

      <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div className="border-b border-gray-200 overflow-x-auto">
          <nav className="flex gap-1 px-4" aria-label="Settings tabs">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-4 py-4 text-sm font-medium border-b-2 transition-colors ${
                  activeTab === tab.id
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                }`}
              >
                <span>{tab.icon}</span>
                <span>{tab.label}</span>
              </button>
            ))}
          </nav>
        </div>
        <div className="p-6">
          {renderTab()}
          <div className="mt-8 pt-6 border-t border-gray-200 flex justify-end">
            <button
              onClick={handleSave}
              className="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
            >
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}