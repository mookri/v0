import { StockPosition, availableOf } from '@navdhya/resource-management/src/domain/inventory';
import { InventoryReservation } from '@navdhya/resource-management/src/domain/reservation';
import { FulfillmentRecipe, FulfillmentResourcePlan, recipeActive } from '@navdhya/resource-management/src/domain/recipe';

export interface StockStore {
  get(itemId: string, locationId: string): Promise<StockPosition | null>;
  save(position: StockPosition, expectedVersion: number): Promise<boolean>;
}

export interface ReservationStore {
  save(reservation: InventoryReservation): Promise<void>;
}

// Atomic check-then-reserve: version-guarded position write prevents oversell.
export async function reserveStock(
  store: StockStore,
  reservations: ReservationStore,
  input: {
    reservationId: string;
    itemId: string;
    locationId: string;
    quantity: number;
    unit: string;
    referenceType: string;
    referenceId: string;
    expiresAt?: string;
    nowIso: string;
    idempotencyKey: string;
  },
): Promise<{ ok: true; reservation: InventoryReservation } | { ok: false; reason: 'INSUFFICIENT_STOCK' | 'CONFLICT' }> {
  void input.idempotencyKey;
  const position = await store.get(input.itemId, input.locationId);
  if (!position) return { ok: false, reason: 'INSUFFICIENT_STOCK' };
  if (availableOf(position) < input.quantity) return { ok: false, reason: 'INSUFFICIENT_STOCK' };
  const next: StockPosition = {
    ...position,
    reserved: position.reserved + input.quantity,
    version: position.version + 1,
  };
  const saved = await store.save(next, position.version);
  if (!saved) return { ok: false, reason: 'CONFLICT' };
  const reservation: InventoryReservation = {
    id: input.reservationId,
    itemId: input.itemId,
    locationId: input.locationId,
    quantity: { amount: input.quantity, unit: input.unit },
    referenceType: input.referenceType,
    referenceId: input.referenceId,
    status: 'HELD',
    expiresAt: input.expiresAt,
    version: 1,
  };
  await reservations.save(reservation);
  return { ok: true, reservation };
}

export function buildResourcePlan(input: {
  fulfillmentId: string;
  recipe: FulfillmentRecipe;
  nowIso: string;
}): FulfillmentResourcePlan | null {
  if (!recipeActive(input.recipe, input.nowIso)) return null;
  return {
    fulfillmentId: input.fulfillmentId,
    recipeId: input.recipe.id,
    recipeVersion: input.recipe.version,
    requirements: input.recipe.lines.map((line) => ({
      itemId: line.itemId,
      requiredQuantity: line.quantity,
      reservedQuantity: { ...line.quantity, amount: 0 },
      allocatedQuantity: { ...line.quantity, amount: 0 },
      consumedQuantity: { ...line.quantity, amount: 0 },
    })),
    status: 'PLANNED',
  };
}
