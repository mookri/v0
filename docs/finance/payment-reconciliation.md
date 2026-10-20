# Finance Runbooks

Payment reconciliation mismatch: verify payment state → provider state → ledger posting; never edit balances — raise FinanceException, repair via reversal/adjustment with approval.
Period close: confirm payment/settlement/refund/invoice reconciliations green, exceptions reviewed, then CLOSE (LOCKED for history).
Repair: dry-run → approve → execute → verify → reconcile; every step references incident/case IDs.
