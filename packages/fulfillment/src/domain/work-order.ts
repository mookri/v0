export type WorkOrderStatus = 'PENDING' | 'READY' | 'ASSIGNED' | 'IN_PROGRESS' | 'COMPLETED' | 'FAILED' | 'CANCELLED';
export type WorkOrderType = 'PUJA_EXECUTION' | 'SAMAGRI_PREPARATION' | 'VENUE_SETUP' | 'CUSTOMER_COORDINATION';

export interface WorkOrder {
  id: string;
  fulfillmentId: string;
  type: WorkOrderType;
  status: WorkOrderStatus;
  scheduledAt?: string;
  completedAt?: string;
  version: number;
}
