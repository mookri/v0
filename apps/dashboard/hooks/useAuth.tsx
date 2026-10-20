'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';
import { apiClient } from '@/api/client';

interface User {
  id: string;
  email: string;
  name: string;
  role: 'admin' | 'manager' | 'staff';
}

interface AuthContextType {
  user: User | null;
  isAuthenticated: boolean;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Boot validation runs UNCONDITIONALLY (SPA port change): a
    // federated session lives in an HttpOnly cookie with no stored
    // token, so gating on localStorage would never validate it. With a
    // stored direct-mode token the Authorization header rides along
    // too; both contracts answer /api/auth/me.
    const token = localStorage.getItem('dashboard_token');
    if (token) {
      apiClient.setToken(token);
    }
    apiClient
      .get<User>('/api/auth/me')
      .then(setUser)
      .catch(() => {
        if (token) {
          localStorage.removeItem('dashboard_token');
          apiClient.clearToken();
        }
      })
      .finally(() => setLoading(false));
  }, []);

  const login = async (email: string, password: string) => {
    const { token, user: userData } = await apiClient.post<{ token: string; user: User }>('/api/auth/login', { email, password });
    localStorage.setItem('dashboard_token', token);
    apiClient.setToken(token);
    setUser(userData);
  };

  const logout = () => {
    // Best-effort server teardown: a federated session exists only as a
    // cookie + an issuer-side token the BFF revokes (POST logout).
    // Failures must never strand the user in a "signed-in" UI — the
    // local teardown below always completes.
    void fetch('/api/auth/logout', { method: 'POST' }).catch(() => {});
    localStorage.removeItem('dashboard_token');
    apiClient.clearToken();
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, isAuthenticated: !!user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
}
