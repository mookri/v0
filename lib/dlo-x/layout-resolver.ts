// lib/dlo-x/layout-resolver.ts
import { RequestContext, ResolvedLayout, LayoutPolicy } from './types';
import { layoutPolicies } from './layout-policies';
import { deterministicHash } from './ab-testing';
import { layoutCache } from './cache';

// Resolve layout tree based on context
export async function resolveLayoutTree(context: RequestContext): Promise<ResolvedLayout> {
  // Create cache key
  const cacheKey = `${context.path}-${context.tenantId}-${context.userRole}-${context.device}-${context.seed}`;
  
  // Check cache first
  const cachedResult = layoutCache.get(cacheKey);
  if (cachedResult) {
    return cachedResult;
  }
  
  // Find matching policies
  const matchingPolicies = findMatchingPolicies(context);
  
  if (matchingPolicies.length === 0) {
    // No matching policies, return minimal shell
    const minimalLayout: ResolvedLayout = {
      shells: ['MinimalShell'],
      policyId: 'minimal-fallback'
    };
    
    // Cache the result
    layoutCache.set(cacheKey, minimalLayout);
    
    return minimalLayout;
  }
  
  // Sort by priority (higher priority first)
  matchingPolicies.sort((a, b) => b.priority - a.priority);
  
  // Select the highest priority policy
  const selectedPolicy = matchingPolicies[0];
  
  // Handle A/B testing if applicable
  let abTestVariant: 'A' | 'B' | undefined;
  if (selectedPolicy.abTest) {
    const hash = deterministicHash(`${context.seed}-${selectedPolicy.abTest.seed}`);
    abTestVariant = hash < selectedPolicy.abTest.percentage ? 'A' : 'B';
  }
  
  const resolvedLayout: ResolvedLayout = {
    shells: selectedPolicy.shells,
    policyId: selectedPolicy.id,
    abTestVariant
  };
  
  // Cache the result
  layoutCache.set(cacheKey, resolvedLayout);
  
  return resolvedLayout;
}

// Find policies that match the context
function findMatchingPolicies(context: RequestContext): LayoutPolicy[] {
  return layoutPolicies.filter((policy: LayoutPolicy) => {
    // Path matching (simple exact match for now)
    if (policy.path !== context.path && policy.path !== '*') {
      // Handle wildcard matching for route groups
      if (!matchesRoutePattern(policy.path, context.path)) {
        return false;
      }
    }
    
    // Role matching
    if (policy.role && policy.role !== context.userRole) {
      return false;
    }
    
    // Tenant matching
    if (policy.tenant && policy.tenant !== context.tenantId) {
      return false;
    }
    
    // Device matching
    if (policy.device && policy.device !== context.device) {
      return false;
    }
    
    return true;
  });
}

// Simple route pattern matching
function matchesRoutePattern(pattern: string, path: string): boolean {
  // Handle wildcard patterns like /auth/* or /dashboard/*
  if (pattern.endsWith('/*')) {
    const prefix = pattern.slice(0, -2); // Remove /*
    return path.startsWith(prefix);
  }
  
  // Handle exact matches
  return pattern === path;
}