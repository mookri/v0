# Identity Platform

One canonical identity, many isolated product sessions. Apps know Session/Actor — never credential internals.
Owner: identity domain. Owns: identity, credential refs, verification, authentication orchestration, sessions, handoffs, lifecycle.
Separation: Identity ≠ User profile (user package), ≠ business capabilities.
Tables (logical): identities, credentials, identity_verifications, sessions, handoffs, security_events.
