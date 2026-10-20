// Level-1 reconciliation (plan B2, item 6; architecture §7.3): the standing
// answer to "did every committed domain event survive the chain?".
//
// Three checkpoints, one ID space (the outbox record ID = envelope ULID):
//   committed  → what business transactions wrote to the outbox
//   published  → what the publisher acked toward Pub/Sub
//   persisted  → what signals_raw actually contains
//
// A gap at ANY checkpoint is reported, never inferred away: the evaluation
// criterion is "every committed domain event is either persisted exactly
// once in signals_raw or is visible in the gap report".

export interface Level1Inputs {
  committedIds: string[];
  publishedIds: string[];
  persistedIds: string[];
}

export interface Level1Report {
  ok: boolean;
  counts: { committed: number; published: number; persisted: number };
  /** Committed but never published — outbox/publisher gap. */
  missingPublished: string[];
  /** Published (or committed) but absent from signals_raw — ingestion gap. */
  missingPersisted: string[];
  /** Same ID persisted more than once — dedup violated. */
  duplicatedPersisted: string[];
}

function duplicates(ids: string[]): string[] {
  const seen = new Set<string>();
  const dupes = new Set<string>();
  for (const id of ids) {
    if (seen.has(id)) dupes.add(id);
    seen.add(id);
  }
  return [...dupes];
}

export function reconcileLevel1(input: Level1Inputs): Level1Report {
  const committed = new Set(input.committedIds);
  const published = new Set(input.publishedIds);
  const persisted = new Set(input.persistedIds);

  const missingPublished = input.committedIds.filter((id) => !published.has(id));
  // A committed event can still reach the warehouse via redelivery even if
  // the publisher never recorded its ack — the warehouse is the ground truth
  // for "persisted", so the persisted check starts from committed ∪ published.
  const expectPersisted = new Set([...committed, ...published]);
  const missingPersisted = [...expectPersisted].filter((id) => !persisted.has(id));
  const duplicatedPersisted = duplicates(input.persistedIds);

  return {
    ok: missingPublished.length === 0 && missingPersisted.length === 0 && duplicatedPersisted.length === 0,
    counts: {
      committed: input.committedIds.length,
      published: input.publishedIds.length,
      persisted: input.persistedIds.length,
    },
    missingPublished,
    missingPersisted,
    duplicatedPersisted,
  };
}
