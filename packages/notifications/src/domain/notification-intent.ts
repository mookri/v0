export interface NotificationIntent {
  type: string;
  recipient: { userId: string };
  context: Record<string, string>;
  sourceEventId?: string;
}
