# Notification Rules

`BookingConfirmed → booking.confirmed` (transactional/high → push, whatsapp fallback sms, email). Identity OTP uses synchronous `CommunicationGateway.send()`; Booking stays event-driven. Marketing requires consent before preference evaluation. Dedup: `rule:event:recipient:channel`. Quiet hours suppress service/marketing, never security.
