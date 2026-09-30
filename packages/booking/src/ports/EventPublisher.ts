export interface DomainEvent<TPayload> {
  id: string;
  type: string;
  version: number;
  occurredAt: string;
  aggregateId: string;
  payload: TPayload;
}

export interface EventPublisher {
  publish(event: DomainEvent<unknown>): Promise<void>;
}
