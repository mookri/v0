import type { UUID } from "@navdhya/contracts";
export function createTraceId(): UUID { return crypto.randomUUID(); }
