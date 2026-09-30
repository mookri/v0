# ADR 004: Authentication Boundary

## Status
Accepted

## Context
Authentication logic is currently scattered across the app. We need a secure, centralized way to manage identity across multiple applications (Web, Customer, Admin, Partner).

## Decision
We will establish a strict Authentication Boundary managed by the `identity` capability. Apps will rely on this centralized boundary for user session validation, login, and signup.
