const fs = require('fs');
const path = require('path');

const contractsDir = path.join(process.cwd(), 'packages/contracts/src');

function mkdir(sub) {
  const p = path.join(contractsDir, sub);
  if (!fs.existsSync(p)) fs.mkdirSync(p, { recursive: true });
  return p;
}

const commonDir = mkdir('common');
fs.writeFileSync(path.join(commonDir, 'errors.ts'), `export interface ApiError { code: string; message: string; details?: any; }\n`);
fs.writeFileSync(path.join(commonDir, 'pagination.ts'), `export interface PaginationParams { page: number; limit: number; }\nexport interface PaginatedResult<T> { data: T[]; total: number; }\n`);
fs.writeFileSync(path.join(commonDir, 'identifiers.ts'), `export type UUID = string;\n`);

const userDir = mkdir('user');
fs.writeFileSync(path.join(userDir, 'user-summary.ts'), `import type { UUID } from "../common/identifiers";\nexport interface UserSummary { id: UUID; name: string; email: string; }\n`);

const bookingDir = mkdir('booking');
fs.writeFileSync(path.join(bookingDir, 'booking-status.ts'), `export type BookingStatus = "draft" | "payment_pending" | "confirmed" | "in_progress" | "completed" | "cancelled";\n`);
fs.writeFileSync(path.join(bookingDir, 'booking-summary.ts'), `import type { UUID } from "../common/identifiers";\nimport type { BookingStatus } from "./booking-status";\nexport interface BookingSummary { id: UUID; userId: UUID; status: BookingStatus; createdAt: Date; }\n`);

const paymentDir = mkdir('payment');
fs.writeFileSync(path.join(paymentDir, 'payment-status.ts'), `export type PaymentStatus = "pending" | "authorized" | "captured" | "failed" | "refunded";\n`);

const eventsDir = mkdir('events');
fs.writeFileSync(path.join(eventsDir, 'envelope.ts'), `export interface EventEnvelope<T = any> { eventId: string; type: string; timestamp: Date; data: T; }\n`);

fs.writeFileSync(path.join(contractsDir, 'index.ts'), `
export * from "./common/errors";
export * from "./common/pagination";
export * from "./common/identifiers";
export * from "./user/user-summary";
export * from "./booking/booking-status";
export * from "./booking/booking-summary";
export * from "./payment/payment-status";
export * from "./events/envelope";
`);

// update apps/web/package.json
const webPkgPath = path.join(process.cwd(), 'apps/web/package.json');
let webPkg = JSON.parse(fs.readFileSync(webPkgPath, 'utf8'));
webPkg.dependencies = webPkg.dependencies || {};
webPkg.dependencies["@navdhya/contracts"] = "workspace:*";
fs.writeFileSync(webPkgPath, JSON.stringify(webPkg, null, 2), 'utf8');

