export type PolicyRule = {
  id: string;
  priority: number;
};

export const PRECEDENCE = {
  explicitRoute: 1000,
  product: 800,
  experience: 600,
  viewport: 400,
  default: 0,
} as const;
