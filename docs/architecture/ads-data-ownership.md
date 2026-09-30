# Ads Data Ownership

| Area | Owns |
|------|------|
| CAMPAIGN | campaigns, campaign_flights, campaign_targeting |
| CREATIVE | creatives, creative_versions, creative_assets |
| INVENTORY | placements, placement_formats |
| DELIVERY | delivery_counters, frequency_state |
| TRACKING | ad_events, conversion_events |

Only Ads mutates these. Other capabilities consume `BookingConfirmed` etc. via events for attribution — never direct table access.
