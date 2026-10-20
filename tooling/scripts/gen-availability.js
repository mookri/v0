const fs = require('fs');
const path = require('path');

const dir = 'packages/availability/src';

const files = {
  'domain/SlotId.ts': `export type SlotId = string;\n`,
  'domain/AvailabilitySlot.ts': `import { SlotId } from './SlotId';\n\nexport interface AvailabilitySlot {\n  id: SlotId;\n  resourceId: string; // PanditId or similar\n  startTime: Date;\n  endTime: Date;\n  isReserved: boolean;\n}\n`,
  'domain/Availability.ts': `import { AvailabilitySlot } from './AvailabilitySlot';\n\nexport interface Availability {\n  resourceId: string;\n  slots: AvailabilitySlot[];\n}\n`,
  'domain/availability-rules.ts': `import { AvailabilitySlot } from './AvailabilitySlot';\n\nexport function isSlotAvailable(slot: AvailabilitySlot): boolean {\n  return !slot.isReserved && slot.startTime > new Date();\n}\n`,
  'contracts/AvailabilityQuery.ts': `export interface AvailabilityQuery {\n  resourceId: string;\n  startDate: Date;\n  endDate: Date;\n}\n`,
  'contracts/AvailabilityResponse.ts': `import { AvailabilitySlot } from '../domain/AvailabilitySlot';\n\nexport interface AvailabilityResponse {\n  resourceId: string;\n  availableSlots: AvailabilitySlot[];\n}\n`,
  'ports/AvailabilityRepository.ts': `import { AvailabilitySlot } from '../domain/AvailabilitySlot';\nimport { SlotId } from '../domain/SlotId';\n\nexport interface AvailabilityRepository {\n  findSlots(resourceId: string, start: Date, end: Date): Promise<AvailabilitySlot[]>;\n  findById(id: SlotId): Promise<AvailabilitySlot | null>;\n  update(slot: AvailabilitySlot): Promise<void>;\n}\n`,
  'application/get-available-slots.ts': `import { AvailabilityRepository } from '../ports/AvailabilityRepository';\nimport { AvailabilityQuery } from '../contracts/AvailabilityQuery';\nimport { AvailabilityResponse } from '../contracts/AvailabilityResponse';\nimport { isSlotAvailable } from '../domain/availability-rules';\n\nexport async function getAvailableSlots(repo: AvailabilityRepository, query: AvailabilityQuery): Promise<AvailabilityResponse> {\n  const slots = await repo.findSlots(query.resourceId, query.startDate, query.endDate);\n  return {\n    resourceId: query.resourceId,\n    availableSlots: slots.filter(isSlotAvailable),\n  };\n}\n`,
  'application/reserve-slot.ts': `import { AvailabilityRepository } from '../ports/AvailabilityRepository';\nimport { SlotId } from '../domain/SlotId';\nimport { isSlotAvailable } from '../domain/availability-rules';\n\nexport async function reserveSlot(repo: AvailabilityRepository, id: SlotId): Promise<void> {\n  const slot = await repo.findById(id);\n  if (!slot) throw new Error('Slot not found');\n  if (!isSlotAvailable(slot)) throw new Error('Slot is not available');\n  slot.isReserved = true;\n  await repo.update(slot);\n}\n`,
  'application/release-slot.ts': `import { AvailabilityRepository } from '../ports/AvailabilityRepository';\nimport { SlotId } from '../domain/SlotId';\n\nexport async function releaseSlot(repo: AvailabilityRepository, id: SlotId): Promise<void> {\n  const slot = await repo.findById(id);\n  if (!slot) throw new Error('Slot not found');\n  slot.isReserved = false;\n  await repo.update(slot);\n}\n`,
  'index.ts': `export * from './domain/Availability';\nexport * from './domain/AvailabilitySlot';\nexport * from './domain/SlotId';\nexport * from './domain/availability-rules';\n\nexport * from './contracts/AvailabilityQuery';\nexport * from './contracts/AvailabilityResponse';\n\nexport * from './ports/AvailabilityRepository';\n\nexport * from './application/get-available-slots';\nexport * from './application/reserve-slot';\nexport * from './application/release-slot';\n`
};

for (const [file, content] of Object.entries(files)) {
  const fullPath = path.join(dir, file);
  fs.writeFileSync(fullPath, content);
}
console.log('Availability capability files generated');
