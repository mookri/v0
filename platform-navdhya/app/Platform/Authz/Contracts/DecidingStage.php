<?php

namespace App\Platform\Authz\Contracts;

/**
 * K-013 AuthorizationDecision reason: which stage of the SEC-022
 * precedence chain decided the outcome. Every decision records its
 * deciding stage (SEC-022: "every decision records which stage decided
 * it"). forged_context is reserved for Working Context forgery denials
 * and lands with T-0K02-02.
 */
enum DecidingStage: string
{
    case ExplicitDeny = 'explicit_deny';
    case AgentSafety = 'agent_safety';
    case SecurityConstraint = 'security_constraint';
    case PolicyConstraint = 'policy_constraint';
    case AllowGrant = 'allow_grant';
    case SodConflict = 'sod_conflict';
    case DefaultDeny = 'default_deny';
    case ForgedContext = 'forged_context';
}
