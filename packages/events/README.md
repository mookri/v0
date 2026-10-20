# Events

Governed platform vocabulary. Envelope `{eventId,type,version,occurredAt,producer,environment,actor,correlationId,data}`; registry declares owner/version/PII/retention/consumers. Outbox remains the reliability anchor; ingestion validates, dedups by `producer:eventId`, quarantines invalid.
