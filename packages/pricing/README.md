# Pricing Capability

Purpose: single pricing truth. Booking never calculates price itself — it calls pricing and stores a PriceQuote.

Owner: pricing domain

Entities: Money, Price, PriceRule, Discount, Tax
Use cases: calculate-quote, validate-price
Public API: `@navdhya/pricing`

Events: PriceChanged (planned)
Dependencies: foundation, puja (by pujaId)
Persistence ownership (Owns): prices, rules, discounts
Does not own: booking status, payments
Extraction readiness: PricingRepository port; quote expiry enforced in validate-price.
