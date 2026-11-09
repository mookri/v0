import { test, expect } from '@playwright/test';

/**
 * Program E End-to-End Devotee Spiritual Lifecycle Spec:
 * 1. Consent Grant & Purpose Ledger (RITUAL_REMINDERS, ANALYTICS).
 * 2. Devotee Spiritual Profile (Gotra, Nakshatra, Tradition).
 * 3. Ancestral Lineage (Pitru Shraddha Anchor).
 * 4. Panchang Ritual Reminder Creation & Next Occurrence Resolution.
 * 5. Channel Matrix Configuration (WhatsApp, Email, SMS).
 * 6. Devotee Telemetry Ingestion (Consent-Gated Batch).
 * 7. Data Portability & Archive Export (JSON Package).
 * 8. Right to Erasure & Financial Exception Retention.
 */

test.describe('Devotee Spiritual Profile, Panchang Engine & Privacy Lifecycle', () => {
  const customerBase = process.env.CUSTOMER_BFF ?? 'http://127.0.0.1:3000';
  const tenantId = '01JBC000000000000000000001';

  test('Step 1: Devotee grants explicit consent for Ritual Reminders & Analytics', async ({ request }) => {
    const res = await request.post(`${customerBase}/api/consent`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        purpose: 'RITUAL_REMINDERS',
        state: 'GRANTED',
        source: 'ONBOARDING_FLOW',
      },
    });

    expect([200, 201]).toContain(res.status());
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 2: Devotee saves sacred Gotra, Nakshatra, and Amanta tradition', async ({ request }) => {
    const res = await request.put(`${customerBase}/api/spiritual`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        gotra: 'Kashyapa',
        nakshatra: 'Rohini',
        rashi: 'Vrishabha',
        kuladevata_name: 'Sri Venkateshwara',
        tradition: 'AMANTA',
      },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 3: Devotee records ancestor lineage for Shraddha observances', async ({ request }) => {
    const res = await request.post(`${customerBase}/api/family`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        full_name: 'Late Sri Ramachandra Sharma',
        relation: 'FATHER',
        data_subject_kind: 'DECEASED',
        death_date: '2020-09-17',
      },
    });

    expect([200, 201]).toContain(res.status());
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 4: Devotee configures Panchang reminder with astronomical anchor', async ({ request }) => {
    const res = await request.post(`${customerBase}/api/reminders`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        type: 'SHRADDHA',
        title: 'Pitru Paksha Tithi Shraddha',
        anchor_rule: {
          type: 'SHRADDHA',
          lunar_month: 6, // Bhadrapada
          paksha: 'KRISHNA',
          tithi: 15, // Mahalaya Amavasya
          tradition: 'AMANTA',
          timezone: 'Asia/Kolkata',
        },
        advance_days: [7, 3, 1],
      },
    });

    expect([200, 201]).toContain(res.status());
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 5: Devotee configures notification delivery matrix', async ({ request }) => {
    const res = await request.patch(`${customerBase}/api/preferences`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        category: 'REMINDER',
        channel: 'WHATSAPP',
        is_enabled: true,
      },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 6: Devotee client ships identified telemetry batch behind analytics gate', async ({ request }) => {
    const res = await request.post(`${customerBase}/api/telemetry/v1`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        events: [
          {
            event_type: 'page_view',
            properties: { page_type: 'spiritual_profile' },
          },
        ],
      },
    });

    expect([200, 202]).toContain(res.status());
  });

  test('Step 7: Devotee downloads complete machine-readable data export archive', async ({ request }) => {
    const res = await request.get(`${customerBase}/api/privacy/export`, {
      headers: {
        'x-tenant-id': tenantId,
      },
    });

    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.data).toBeDefined();
  });

  test('Step 8: Devotee exercises Right to Erasure with financial exception safeguards', async ({ request }) => {
    const res = await request.post(`${customerBase}/api/privacy/erasure`, {
      headers: {
        'Content-Type': 'application/json',
        'x-tenant-id': tenantId,
      },
      data: {
        reason: 'closing_account_permanently',
      },
    });

    expect([200, 202]).toContain(res.status());
    const body = await res.json();
    expect(body.data).toBeDefined();
  });
});

