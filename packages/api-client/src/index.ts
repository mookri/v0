export interface ServiceErrorShape {
  code: string;
  status: number;
  requestId: string;
}

export class ServiceApiError extends Error {
  code: string;
  status: number;
  requestId: string;

  constructor(shape: ServiceErrorShape) {
    super(shape.code);
    this.code = shape.code;
    this.status = shape.status;
    this.requestId = shape.requestId;
  }
}

export interface BookingGateway {
  getBooking(id: string): Promise<unknown>;
  cancelBooking(id: string, idempotencyKey: string): Promise<unknown>;
}

// Local today (modular monolith), remote HTTP tomorrow — same interface.
export type BookingGatewayKind = 'local' | 'remote';
