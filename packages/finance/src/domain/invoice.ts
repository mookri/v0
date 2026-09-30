import { MoneyMinor } from './money';

export type InvoiceStatus = 'DRAFT' | 'ISSUED' | 'PARTIALLY_PAID' | 'PAID' | 'VOID' | 'OVERDUE' | 'CANCELLED';

export interface InvoiceLine {
  id: string;
  invoiceId: string;
  description: string;
  quantity: number;
  unitAmount: MoneyMinor;
  lineSubtotal: MoneyMinor;
  discountAmount: MoneyMinor;
  taxAmount: MoneyMinor;
  lineTotal: MoneyMinor;
  sourceType: string;
  sourceId: string;
}

export interface Invoice {
  id: string;
  invoiceNumber: string;
  customerId?: string;
  bookingId?: string;
  issuedAt?: string;
  dueAt?: string;
  currency: string;
  subtotal: MoneyMinor;
  discounts: MoneyMinor;
  fees: MoneyMinor;
  taxes: MoneyMinor;
  total: MoneyMinor;
  status: InvoiceStatus;
  pricingSnapshotId?: string;
  version: number;
}

export function invoiceTotals(lines: InvoiceLine[], currency: string): Pick<Invoice, 'subtotal' | 'discounts' | 'fees' | 'taxes' | 'total'> {
  const m = (amountMinor: number): MoneyMinor => ({ amountMinor, currency });
  const gross = lines.reduce((acc, l) => acc + l.lineSubtotal.amountMinor, 0);
  const discounts = lines.reduce((acc, l) => acc + l.discountAmount.amountMinor, 0);
  const taxes = lines.reduce((acc, l) => acc + l.taxAmount.amountMinor, 0);
  const total = gross + discounts + taxes;
  return { subtotal: m(gross), discounts: m(discounts), fees: m(0), taxes: m(taxes), total: m(total) };
}

export type DocumentType = 'INVOICE' | 'CREDIT_NOTE' | 'DEBIT_NOTE' | 'RECEIPT' | 'STATEMENT';

export interface FinancialDocument {
  id: string;
  type: DocumentType;
  referenceNumber: string;
  sourceId: string;
  version: number;
  storageRef?: string;
  issuedAt: string;
}
