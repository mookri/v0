import { Quantity } from './resource';

export type RequirementMode = 'REQUIRED' | 'OPTIONAL' | 'CONDITIONAL';
export type ConsumptionType = 'CONSUMED' | 'RETURNED' | 'REUSABLE';
export type ResourceSourceMode = 'PLATFORM' | 'PARTNER' | 'SUPPLIER' | 'CUSTOMER';

export interface RecipeLine {
  itemId: string;
  quantity: Quantity;
  consumptionType: ConsumptionType;
  optional: boolean;
  mode: RequirementMode;
  sourceMode: ResourceSourceMode;
}

export interface FulfillmentRecipe {
  id: string;
  offeringId: string;
  version: string;
  status: 'DRAFT' | 'ACTIVE' | 'RETIRED';
  lines: RecipeLine[];
  effectiveFrom: string;
  effectiveTo?: string;
}

export function recipeActive(recipe: FulfillmentRecipe, nowIso: string): boolean {
  if (recipe.status !== 'ACTIVE') return false;
  if (nowIso < recipe.effectiveFrom) return false;
  if (recipe.effectiveTo && nowIso >= recipe.effectiveTo) return false;
  return true;
}

export type PlanStatus = 'PLANNED' | 'CHECKED' | 'RESERVED' | 'ALLOCATED' | 'CONSUMED' | 'EXCEPTION';

export interface ResourceRequirement {
  itemId: string;
  requiredQuantity: Quantity;
  reservedQuantity: Quantity;
  allocatedQuantity: Quantity;
  consumedQuantity: Quantity;
}

export interface FulfillmentResourcePlan {
  fulfillmentId: string;
  recipeId: string;
  recipeVersion: string;
  requirements: ResourceRequirement[];
  status: PlanStatus;
}

export type ReadinessStatus = 'READY' | 'PARTIALLY_READY' | 'NOT_READY' | 'BLOCKED';

export interface ReadinessEvaluation {
  fulfillmentId: string;
  status: ReadinessStatus;
  missingItemIds: string[];
}
