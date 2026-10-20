// B2 acceptance: transactional guarantee — a committed business transaction
// leaves EXACTLY ONE outbox record; a rolled-back one leaves NONE. The real
// PostgresTransactionManager drives BEGIN/COMMIT/ROLLBACK over the fake
// client, and the real emitters + application functions do the appending.

import { describe, expect, it } from 'vitest';
import { PostgresTransactionManager } from '@infra/database/postgres/transaction';
import { bookingSignalSink, paymentSignalSink } from '@infra/messaging/outbox/signal-sinks';
import { confirmCapture } from '@navdhya/payment/src/application/collect-payment';
import { confirmBooking, type BookingConfirmedEmit } from '@navdhya/booking/src/application/confirm-booking';
import type { Payment } from '@navdhya/payment/src/domain/Payment';
import type { PaymentProvider, PaymentRepository } from '@navdhya/payment/src/ports/PaymentPorts';
import type { BookingRepository } from '@navdhya/booking/src/ports/BookingRepository';
import type { Booking } from '@navdhya/booking/src/domain/Booking';
import type { SignalEnvelope } from '@navdhya/analytics/src/contracts/envelope';
import { FakePgClient } from './helpers/fake-pg-client';

const NOW = '2026-10-12T10:00:00.000Z';

function paymentFixture(overrides: Partial<Payment> = {}): Payment {
  return {
    id: 'pay_1',
    bookingId: 'book_1',
    amountMinor: 510000, // ₹5,100.00
    currency: 'INR',
    status: 'AUTHORIZED',
    idempotencyKey: 'idem_1',
    refundedMinor: 0,
    providerEventIds: [],
    createdAt: NOW,
    updatedAt: NOW,
    ...overrides,
  };
}

class FakePaymentRepo implements PaymentRepository {
  constructor(private readonly initial: Payment) {}
  readonly saved: Payment[] = [];
  async findById() {
    return this.initial;
  }
  async findByIdempotencyKey() {
    return this.initial;
  }
  async findByProviderEventId() {
    return null;
  }
  async save(p: Payment) {
    this.saved.push(p);
  }
  async update(p: Payment) {
    this.saved.push(p);
  }
}

function provider(status: 'captured' | 'failed'): PaymentProvider {
  return {
    createPaymentIntent: async () => ({ id: 'pi', status: 'authorized' }),
    verifyPayment: async (r) => ({ paymentId: r.paymentId, verified: status === 'captured', status }),
    refund: async () => ({ refundId: 'rf', status: 'succeeded' }),
  };
}

function bookingFixture(): Booking {
  return {
    id: 'book_1',
    customer: { userId: 'u1', name: 'Test User' },
    items: [{ pujaId: 'puja_1', quantity: 1 }],
    schedule: { startTime: new Date(NOW), endTime: new Date(NOW) },
    status: 'PAYMENT_CONFIRMED',
    createdAt: new Date(NOW),
    updatedAt: new Date(NOW),
  };
}

class FakeBookingRepo implements BookingRepository {
  readonly updates: Booking[] = [];
  constructor(private readonly initial: Booking) {}
  async findById() {
    return this.initial;
  }
  async save(b: Booking) {
    this.updates.push(b);
  }
  async update(b: Booking) {
    this.updates.push(b);
  }
  async listByUser() {
    return [this.initial];
  }
}

function envelopeOf(row: { payload: unknown }): SignalEnvelope {
  return row.payload as SignalEnvelope;
}

describe('transactional P0 emission (plan B2 item 2 + acceptance)', () => {
  it('committed capture → exactly one payment_succeeded record, same transaction as the business write', async () => {
    const db = new FakePgClient();
    const tx = new PostgresTransactionManager(db);
    const repo = new FakePaymentRepo(paymentFixture());

    const result = await tx.run(async (ctx) =>
      confirmCapture(repo, provider('captured'), 'pay_1', 'rzp_ref_9', NOW, paymentSignalSink(ctx.outbox!)),
    );

    expect(result.status).toBe('CAPTURED'); // business write happened
    const rows = db.outboxRows();
    expect(rows).toHaveLength(1);
    expect(rows[0]!.type).toBe('payment_succeeded');
    expect(rows[0]!.aggregate_id).toBe('pay_1');

    const envelope = envelopeOf(rows[0]!);
    expect(envelope.id).toBe(rows[0]!.id); // ULID is the record ID end to end
    expect(envelope.params).toEqual({
      value: 5100,
      currency: 'INR',
      transaction_ref: 'rzp_ref_9',
    });
    expect(envelope.version).toBe(1);
    expect(envelope.session.seq).toBe(1);

    // Statement order: BEGIN … INSERT … COMMIT.
    const statements = db.statements();
    expect(statements[0]).toBe('BEGIN');
    expect(statements[statements.length - 1]).toBe('COMMIT');
    expect(statements.some((s) => s.startsWith('INSERT INTO outbox'))).toBe(true);
    expect(statements.some((s) => s === 'ROLLBACK')).toBe(false);
  });

  it('rolled-back capture → NO outbox record survives (the acceptance case)', async () => {
    const db = new FakePgClient();
    const tx = new PostgresTransactionManager(db);

    await expect(
      tx.run(async (ctx) => {
        await confirmCapture(
          new FakePaymentRepo(paymentFixture()),
          provider('captured'),
          'pay_1',
          'rzp_ref_9',
          NOW,
          paymentSignalSink(ctx.outbox!),
        );
        throw new Error('business logic failed after emit');
      }),
    ).rejects.toThrow('business logic failed after emit');

    expect(db.outboxRows()).toHaveLength(0);
    const statements = db.statements();
    expect(statements[0]).toBe('BEGIN');
    expect(statements).toContain('ROLLBACK');
    expect(statements).not.toContain('COMMIT');
  });

  it('committed failed capture → exactly one payment_failed record (emitted before the throw)', async () => {
    const db = new FakePgClient();
    const tx = new PostgresTransactionManager(db);

    // Webhook-style handler: the capture failure is a BUSINESS outcome — the
    // handler keeps the FAILED status by catching inside the transaction.
    await tx.run(async (ctx) => {
      await expect(
        confirmCapture(
          new FakePaymentRepo(paymentFixture()),
          provider('failed'),
          'pay_1',
          'rzp_ref_9',
          NOW,
          paymentSignalSink(ctx.outbox!),
        ),
      ).rejects.toThrow('PAYMENT_NOT_CAPTURED');
    });

    const rows = db.outboxRows();
    expect(rows).toHaveLength(1);
    expect(rows[0]!.type).toBe('payment_failed');
    const envelope = envelopeOf(rows[0]!);
    expect(envelope.params).toEqual({
      value: 5100,
      currency: 'INR',
      transaction_ref: 'rzp_ref_9',
    });
  });

  it('booking confirmation → exactly one booking_confirmed record; rollback → none', async () => {
    const db = new FakePgClient();
    const tx = new PostgresTransactionManager(db);
    const emit: Omit<BookingConfirmedEmit, 'sink'> = {
      value: 5100,
      currency: 'INR',
      serviceCategory: 'puja',
      city: 'Pune',
    };

    await tx.run(async (ctx) => {
      await confirmBooking(new FakeBookingRepo(bookingFixture()), 'book_1', {
        ...emit,
        sink: bookingSignalSink(ctx.outbox!),
      });
    });

    let rows = db.outboxRows();
    expect(rows).toHaveLength(1);
    expect(rows[0]!.type).toBe('booking_confirmed');
    expect(rows[0]!.aggregate_id).toBe('book_1');
    expect(envelopeOf(rows[0]!).params).toEqual({
      value: 5100,
      currency: 'INR',
      service_category: 'puja',
      city: 'Pune',
    });

    await expect(
      tx.run(async (ctx) => {
        await confirmBooking(new FakeBookingRepo(bookingFixture()), 'book_1', {
          ...emit,
          sink: bookingSignalSink(ctx.outbox!),
        });
        throw new Error('rollback');
      }),
    ).rejects.toThrow('rollback');

    rows = db.outboxRows();
    expect(rows).toHaveLength(1); // unchanged — second emit rolled back
  });

  it('emitters fail closed: schema-invalid params throw BEFORE reaching the outbox', async () => {
    const db = new FakePgClient();
    const tx = new PostgresTransactionManager(db);
    const { emitPaymentSucceeded } = await import('@navdhya/analytics/src/server/emitters');

    // Wrong-typed required param → generated validator rejects (typeof gate).
    await expect(
      tx.run(async (ctx) =>
        emitPaymentSucceeded(ctx.outbox!, { aggregateId: 'pay_x' }, {
          value: '5100' as unknown as number,
          currency: 'INR',
          transaction_ref: 'rzp_1',
        }),
      ),
    ).rejects.toThrow('rejected');

    expect(db.outboxRows()).toHaveLength(0); // nothing appended, nothing persisted
    expect(db.statements()).toContain('ROLLBACK'); // aborted transaction, cleanly
    expect(db.statements()).not.toContain('COMMIT');
  });
});
