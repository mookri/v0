# Fulfillment Ownership

DISCOVERY → search/discovery/recommendations/ads. COMMERCE → booking/pricing/payment. DELIVERY → fulfillment/scheduling/assignment (internal). BookingConfirmed creates fulfillment; BookingCancelled/Rescheduled drive operational workflows. Settlement/feedback consume FulfillmentCompleted. Tables (logical, shared Postgres): fulfillment_orders, work_orders, assignments, assignment_offers, schedules, operational_tasks, fulfillment_exceptions, execution_records, completion_records, fulfillment_events.
