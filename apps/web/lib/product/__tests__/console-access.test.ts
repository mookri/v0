import {
  CONSOLE_ROLES,
  canAccessConsole,
  consoleForPath,
  consoleLandingForRole,
  isConsolePath,
} from '@/lib/product/console-access';

describe('console-access', () => {
  describe('consoleForPath', () => {
    it('matches the bare base path and its descendants', () => {
      expect(consoleForPath('/admin')?.role).toBe('Admin');
      expect(consoleForPath('/admin/users')?.role).toBe('Admin');
      expect(consoleForPath('/platform/roles')?.role).toBe('SuperAdmin');
      expect(consoleForPath('/user')?.role).toBe('User');
    });

    it('is segment-safe (prefix lookalikes are not consoles)', () => {
      for (const path of ['/usertools', '/administrator', '/platformer', '/vendors', '/pandits/x', '/managerial']) {
        expect(consoleForPath(path)).toBeNull();
      }
    });

    it('ignores non-console paths', () => {
      for (const path of ['/', '/login', '/signup', '/pujas/griha-pravesh', '/api/auth/otp', '/settings']) {
        expect(consoleForPath(path)).toBeNull();
      }
    });
  });

  it('isConsolePath mirrors consoleForPath', () => {
    expect(isConsolePath('/admin/users')).toBe(true);
    expect(isConsolePath('/usertools')).toBe(false);
  });

  describe('canAccessConsole', () => {
    it('confines each role to its own console', () => {
      expect(canAccessConsole('Admin', '/admin/users')).toBe(true);
      expect(canAccessConsole('Admin', '/platform/roles')).toBe(false);
      expect(canAccessConsole('Manager', '/admin/users')).toBe(false);
      expect(canAccessConsole('Vendor', '/vendor/orders')).toBe(true);
      expect(canAccessConsole('Pandit', '/pandit/leads')).toBe(true);
      expect(canAccessConsole('User', '/user/bookings')).toBe(true);
    });

    it('lets SuperAdmin enter any console', () => {
      for (const entry of CONSOLE_ROLES) {
        expect(canAccessConsole('SuperAdmin', `${entry.basePath}/anything`)).toBe(true);
      }
    });

    it('does not restrict paths outside any console', () => {
      expect(canAccessConsole('User', '/pujas/griha-pravesh')).toBe(true);
    });
  });

  it('consoleLandingForRole points at the role overview', () => {
    expect(consoleLandingForRole('User')).toBe('/user/overview');
    expect(consoleLandingForRole('SuperAdmin')).toBe('/platform/overview');
  });
});
