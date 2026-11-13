# ADR-0061: Separated Feedback Submission, Publication & Aggregation Lifecycle

## Status
Accepted (2026-11-11)

## Context
Legacy `BookingReview` allowed submission without completion gating, coupled raw ratings directly to public visibility, and exposed mutable star counts without a right of reply or appeal path (`TS-07`/`TS-08`/`TS-09`).

## Decision
Separate `Feedback` into completed-ceremony invitation tokens (`IssueFeedbackInvitationUseCase`), server-derived subject resolution (`SubmitFeedbackUseCase` refusing client-supplied `pandit_id` and redirecting uncompleted/no-show bookings to `support_report`), bounded `48h` post-publication editability, provider right-of-reply/appeal (`ModerateFeedbackAndAppealService`), and materialized `feedback_subject_aggregates` / `feedback_score_snapshots`.

