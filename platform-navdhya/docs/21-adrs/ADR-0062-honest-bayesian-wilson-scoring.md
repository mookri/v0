# ADR-0062: Honest Bayesian/Wilson/Recency Scoring & Confidence Thresholds

## Status
Accepted (2026-11-11)

## Context
Raw arithmetic star averages let a single `5.0` review outrank established pandits and misattributed venue or devotee delays to pandit punctuality (`TS-10`).

## Decision
Compute `FeedbackSubjectAggregate` metrics using Bayesian shrinkage toward the platform prior (`m=10`), exponential recency decay (`halfLifeDays=180`), and 95% Wilson score lower bounds for verified arrival punctuality (`ON_TIME` / `LATE_GRACE`). Suppress `5.0` headline rendering whenever `sampleCount < 5` (`NEW_PROVIDER` tier) across API DTOs, Search projections, and Sacred Ember UI.

