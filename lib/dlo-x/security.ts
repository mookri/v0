// lib/dlo-x/security.ts
// Security utilities for DLO-X architecture

// Sanitize user input to prevent XSS attacks
export function sanitizeInput(input: string): string {
  if (!input) return input;
  
  // Replace HTML special characters with their entities
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#x27;')
    .replace(/\//g, '&#x2F;');
}

// Validate and sanitize headers
export function sanitizeHeaders(headers: Record<string, string>): Record<string, string> {
  const sanitized: Record<string, string> = {};
  
  for (const [key, value] of Object.entries(headers)) {
    // Only allow specific headers
    if (key.startsWith('x-')) {
      sanitized[key] = sanitizeInput(value);
    }
  }
  
  return sanitized;
}

// Check if a role has permission to access a resource
export function hasPermission(userRole: string, requiredRole: string): boolean {
  const roleHierarchy: Record<string, number> = {
    'User': 1,
    'Pandit': 2,
    'Vendor': 3,
    'Manager': 4,
    'Admin': 5
  };
  
  return (roleHierarchy[userRole] || 0) >= (roleHierarchy[requiredRole] || 0);
}

// Validate tenant access
export function validateTenant(userTenant: string, resourceTenant: string): boolean {
  // In a real implementation, this would check tenant permissions
  return userTenant === resourceTenant || userTenant === 'default';
}

// Remove PII from telemetry data
export function removePII(data: Record<string, any>): Record<string, any> {
  const piiFields = [
    'email', 'password', 'phone', 'address', 'ssn', 'creditCard',
    'firstName', 'lastName', 'fullName', 'userId', 'username'
  ];
  
  const sanitized = { ...data };
  
  for (const field of piiFields) {
    if (field in sanitized) {
      sanitized[field] = '[REDACTED]';
    }
  }
  
  return sanitized;
}

// Validate shell names to prevent injection
export function validateShellName(shellName: string): boolean {
  const validShells = [
    'MinimalShell',
    'MainShell',
    'AuthShell',
    'DashboardShell',
    'AdminShell',
    'VendorShell',
    'PoojaShell',
    'MobileShell',
    'ChromeShell'
  ];
  
  return validShells.includes(shellName);
}

// Validate policy IDs
export function validatePolicyId(policyId: string): boolean {
  // Policy IDs should be alphanumeric with hyphens
  return /^[a-zA-Z0-9\-_]+$/.test(policyId);
}

// Validate paths
export function validatePath(path: string): boolean {
  // Paths should start with / and contain only safe characters
  return /^\/[a-zA-Z0-9\-_\/]*$/.test(path);
}