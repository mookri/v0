import type { UUID } from "../common/identifiers";
import type { BookingStatus } from "./booking-status";
export interface BookingSummary { id: UUID; userId: UUID; status: BookingStatus; createdAt: Date; }
