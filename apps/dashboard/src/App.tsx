import { Routes, Route, Navigate } from 'react-router-dom';
import { Layout } from './components/Layout';
import { DashboardHome } from './pages/DashboardHome';
import { Login } from './pages/Login';
import { PujaManagement } from './pages/PujaManagement';
import { BookingManagement } from './pages/BookingManagement';
import { CustomerManagement } from './pages/CustomerManagement';
import { Settings } from './pages/Settings';
import { useAuth } from './hooks/useAuth';

function PrivateRoute({ children }: { children: React.ReactNode }) {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <div className="min-h-screen flex items-center justify-center">Loading...</div>;
  }

  if (!isAuthenticated) {
    return <Navigate to="/login" replace />;
  }

  return <>{children}</>;
}

function ProtectedRoutes() {
  return (
    <PrivateRoute>
      <Routes>
        <Route element={<Layout />}>
          <Route path="/" element={<DashboardHome />} />
          <Route path="/pujas" element={<PujaManagement />} />
          <Route path="/bookings" element={<BookingManagement />} />
          <Route path="/customers" element={<CustomerManagement />} />
          <Route path="/settings" element={<Settings />} />
        </Route>
      </Routes>
    </PrivateRoute>
  );
}

function App() {
  return (
    <Routes>
      <Route path="/login" element={<Login />} />
      <Route path="/*" element={<ProtectedRoutes />} />
    </Routes>
  );
}

export default App;