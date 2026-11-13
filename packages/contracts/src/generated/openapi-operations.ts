// AUTO-GENERATED from platform-navdhya/docs/api/openapi.v1.yaml via `php artisan contracts:export`.
// Do NOT hand-edit.

export interface GeneratedOpenApiOperation {
  readonly method: string;
  readonly path: string;
  readonly permission: string | null;
  readonly gate: string | null;
}

export const OPENAPI_OPERATIONS = {
    "acceptInvite": {
        "method": "POST",
        "path": "/api/v1/identity/invites:accept",
        "permission": null,
        "gate": "public"
    },
    "activatePolicy": {
        "method": "POST",
        "path": "/api/v1/policies/{policy}:activate",
        "permission": "platform:policy:configure",
        "gate": null
    },
    "activateUser": {
        "method": "POST",
        "path": "/api/v1/platform/users/{id}:activate",
        "permission": "platform:user:manage",
        "gate": null
    },
    "addPartnerTempleManager": {
        "method": "POST",
        "path": "/api/v1/temple/partner/temples/{templeId}/managers",
        "permission": null,
        "gate": "ownership"
    },
    "adjustVendorInventory": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors/{id}/inventory:adjust",
        "permission": null,
        "gate": "ownership"
    },
    "adminAssignPandit": {
        "method": "POST",
        "path": "/api/v1/pandit/admin/assignments",
        "permission": "pandit:assignment:manage",
        "gate": null
    },
    "adminListPanditProfiles": {
        "method": "GET",
        "path": "/api/v1/pandit/admin/profiles",
        "permission": "pandit:profile:view",
        "gate": null
    },
    "adminListTemples": {
        "method": "GET",
        "path": "/api/v1/temple/admin/profiles",
        "permission": "temple:profile:view",
        "gate": null
    },
    "adminListVendors": {
        "method": "GET",
        "path": "/api/v1/vendor/admin/vendors",
        "permission": "vendor:profile:view",
        "gate": null
    },
    "adminUnassignPandit": {
        "method": "DELETE",
        "path": "/api/v1/pandit/admin/assignments/{id}",
        "permission": "pandit:assignment:manage",
        "gate": null
    },
    "advanceVendorOrder": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors/{id}/orders/{orderId}:advance",
        "permission": null,
        "gate": "ownership"
    },
    "approveApplication": {
        "method": "POST",
        "path": "/api/v1/onboarding/review/{id}:approve",
        "permission": "onboarding:review:approve",
        "gate": null
    },
    "archiveCatalogItem": {
        "method": "POST",
        "path": "/api/v1/catalog/items/{id}:archive",
        "permission": "catalog:item:manage",
        "gate": null
    },
    "assignPartnerTemplePuja": {
        "method": "POST",
        "path": "/api/v1/temple/partner/temples/{templeId}/pujas",
        "permission": null,
        "gate": "ownership"
    },
    "beginApplicationSubmit": {
        "method": "POST",
        "path": "/api/v1/onboarding/applications/{id}/submit",
        "permission": "onboarding:application:submit",
        "gate": null
    },
    "cancelReservation": {
        "method": "POST",
        "path": "/api/v1/booking/reservations/{id}:cancel",
        "permission": null,
        "gate": "ownership"
    },
    "cancelVendorOrder": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors/{id}/orders/{orderId}:cancel",
        "permission": null,
        "gate": "ownership"
    },
    "closeSupportTicket": {
        "method": "POST",
        "path": "/api/v1/customers/support/tickets/{id}:close",
        "permission": null,
        "gate": "ownership"
    },
    "completePanditAssignment": {
        "method": "POST",
        "path": "/api/v1/pandit/assignments/{id}:complete",
        "permission": null,
        "gate": "ownership"
    },
    "confirmApplicationSubmit": {
        "method": "POST",
        "path": "/api/v1/onboarding/applications/{id}/submit/confirm",
        "permission": "onboarding:application:submit",
        "gate": null
    },
    "confirmMfa": {
        "method": "POST",
        "path": "/api/v1/identity/mfa/confirm",
        "permission": null,
        "gate": "ownership"
    },
    "confirmPanditAssignment": {
        "method": "POST",
        "path": "/api/v1/pandit/assignments/{id}:confirm",
        "permission": null,
        "gate": "ownership"
    },
    "confirmReservation": {
        "method": "POST",
        "path": "/api/v1/booking/reservations/{id}:confirm",
        "permission": null,
        "gate": "ownership"
    },
    "confirmVerification": {
        "method": "POST",
        "path": "/api/v1/customers/verifications/{challengeId}:confirm",
        "permission": null,
        "gate": "ownership"
    },
    "createCatalogItem": {
        "method": "POST",
        "path": "/api/v1/catalog/items",
        "permission": "catalog:item:manage",
        "gate": null
    },
    "createCustomerAddress": {
        "method": "POST",
        "path": "/api/v1/customers/addresses",
        "permission": null,
        "gate": "ownership"
    },
    "createPartnerTemple": {
        "method": "POST",
        "path": "/api/v1/temple/partner/temples",
        "permission": null,
        "gate": "ownership"
    },
    "createPolicy": {
        "method": "POST",
        "path": "/api/v1/policies",
        "permission": "platform:policy:configure",
        "gate": null
    },
    "createRole": {
        "method": "POST",
        "path": "/api/v1/platform/roles",
        "permission": null,
        "gate": "ownership"
    },
    "createSodRule": {
        "method": "POST",
        "path": "/api/v1/platform/sod-rules",
        "permission": null,
        "gate": "ownership"
    },
    "createVendor": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors",
        "permission": null,
        "gate": "ownership"
    },
    "createVendorProduct": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors/{id}/products",
        "permission": null,
        "gate": "ownership"
    },
    "deactivateCustomer": {
        "method": "POST",
        "path": "/api/v1/customers/profile:deactivate",
        "permission": null,
        "gate": "ownership"
    },
    "declinePanditAssignment": {
        "method": "POST",
        "path": "/api/v1/pandit/assignments/{id}:decline",
        "permission": null,
        "gate": "ownership"
    },
    "defaultCustomerAddress": {
        "method": "POST",
        "path": "/api/v1/customers/addresses/{id}:default",
        "permission": null,
        "gate": "ownership"
    },
    "deleteBookingReview": {
        "method": "DELETE",
        "path": "/api/v1/booking/reviews/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "deleteCustomerAddress": {
        "method": "DELETE",
        "path": "/api/v1/customers/addresses/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "deleteSodRule": {
        "method": "DELETE",
        "path": "/api/v1/platform/sod-rules/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "disablePolicy": {
        "method": "POST",
        "path": "/api/v1/policies/{policy}:disable",
        "permission": "platform:policy:configure",
        "gate": null
    },
    "enrollMfa": {
        "method": "POST",
        "path": "/api/v1/identity/mfa/enroll",
        "permission": null,
        "gate": "ownership"
    },
    "explainAuthorization": {
        "method": "GET",
        "path": "/api/v1/authz/explain",
        "permission": "platform:authz:explain",
        "gate": null
    },
    "getApplication": {
        "method": "GET",
        "path": "/api/v1/onboarding/applications/{id}",
        "permission": "onboarding:application:view",
        "gate": null
    },
    "getApplicationStatus": {
        "method": "GET",
        "path": "/api/v1/onboarding/applications/{id}/status",
        "permission": "onboarding:application:view",
        "gate": null
    },
    "getCatalogItem": {
        "method": "GET",
        "path": "/api/v1/catalog/items/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getCustomerProfile": {
        "method": "GET",
        "path": "/api/v1/customers/profile",
        "permission": null,
        "gate": "ownership"
    },
    "getCustomerSettings": {
        "method": "GET",
        "path": "/api/v1/customers/settings",
        "permission": null,
        "gate": "ownership"
    },
    "getDiscoverFeed": {
        "method": "GET",
        "path": "/api/v1/customers/discover",
        "permission": null,
        "gate": "ownership"
    },
    "getMfaStatus": {
        "method": "GET",
        "path": "/api/v1/identity/mfa",
        "permission": null,
        "gate": "ownership"
    },
    "getOwnPanditEarnings": {
        "method": "GET",
        "path": "/api/v1/pandit/earnings",
        "permission": null,
        "gate": "ownership"
    },
    "getOwnPanditProfile": {
        "method": "GET",
        "path": "/api/v1/pandit/profile",
        "permission": null,
        "gate": "ownership"
    },
    "getOwnProfile": {
        "method": "GET",
        "path": "/api/v1/identity/profile",
        "permission": null,
        "gate": "ownership"
    },
    "getPanditApplication": {
        "method": "GET",
        "path": "/api/v1/pandit/application",
        "permission": null,
        "gate": "ownership"
    },
    "getPanditProfile": {
        "method": "GET",
        "path": "/api/v1/pandit/profiles/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getPartnerTemple": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getPartnerTemplePuja": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/pujas/{pujaId}",
        "permission": null,
        "gate": "ownership"
    },
    "getPolicy": {
        "method": "GET",
        "path": "/api/v1/policies/{policy}",
        "permission": "platform:policy:view",
        "gate": null
    },
    "getPublicPanditAvailability": {
        "method": "GET",
        "path": "/api/v1/pandit/profiles/{id}/availability",
        "permission": null,
        "gate": "ownership"
    },
    "getPublicTempleAvailability": {
        "method": "GET",
        "path": "/api/v1/temple/profiles/{templeId}/availability",
        "permission": null,
        "gate": "ownership"
    },
    "getReferralSummary": {
        "method": "GET",
        "path": "/api/v1/customers/referrals",
        "permission": null,
        "gate": "ownership"
    },
    "getReservation": {
        "method": "GET",
        "path": "/api/v1/booking/reservations/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getReservationHistory": {
        "method": "GET",
        "path": "/api/v1/booking/reservations/{id}/history",
        "permission": null,
        "gate": "ownership"
    },
    "getStaffSupportTicket": {
        "method": "GET",
        "path": "/api/v1/customers/support/staff/tickets/{id}",
        "permission": "customers:support:view",
        "gate": null
    },
    "getSupportTicket": {
        "method": "GET",
        "path": "/api/v1/customers/support/tickets/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardAnalytics": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/analytics",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardBookings": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/bookings",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardCalendar": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/calendar",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardOverview": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/overview",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardPujas": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/pujas",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleDashboardSettings": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/dashboard/settings",
        "permission": null,
        "gate": "ownership"
    },
    "getTempleProfile": {
        "method": "GET",
        "path": "/api/v1/temple/profiles/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getVendor": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "getVendorOrder": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}/orders/{orderId}",
        "permission": null,
        "gate": "ownership"
    },
    "getVerificationStatus": {
        "method": "GET",
        "path": "/api/v1/customers/verifications",
        "permission": null,
        "gate": "ownership"
    },
    "grantMembershipRole": {
        "method": "POST",
        "path": "/api/v1/platform/memberships/{membershipId}/roles",
        "permission": null,
        "gate": "ownership"
    },
    "instantBookSearch": {
        "method": "GET",
        "path": "/api/v1/booking/instant-search",
        "permission": null,
        "gate": "ownership"
    },
    "introspectOAuthToken": {
        "method": "POST",
        "path": "/api/oauth/introspect",
        "permission": null,
        "gate": "public"
    },
    "issueApiKey": {
        "method": "POST",
        "path": "/api/v1/platform/api-keys",
        "permission": null,
        "gate": "ownership"
    },
    "issueDocumentAccessLink": {
        "method": "GET",
        "path": "/api/v1/onboarding/documents/{id}/access-link",
        "permission": "onboarding:document:access",
        "gate": null
    },
    "issueInvite": {
        "method": "POST",
        "path": "/api/v1/identity/invites",
        "permission": null,
        "gate": "ownership"
    },
    "listApiKeys": {
        "method": "GET",
        "path": "/api/v1/platform/api-keys",
        "permission": null,
        "gate": "ownership"
    },
    "listBookingReviews": {
        "method": "GET",
        "path": "/api/v1/booking/reviews",
        "permission": null,
        "gate": "ownership"
    },
    "listCatalogItems": {
        "method": "GET",
        "path": "/api/v1/catalog/items",
        "permission": null,
        "gate": "ownership"
    },
    "listCustomerActivity": {
        "method": "GET",
        "path": "/api/v1/customers/activity",
        "permission": null,
        "gate": "ownership"
    },
    "listCustomerAddresses": {
        "method": "GET",
        "path": "/api/v1/customers/addresses",
        "permission": null,
        "gate": "ownership"
    },
    "listCustomerNotifications": {
        "method": "GET",
        "path": "/api/v1/customers/notifications",
        "permission": null,
        "gate": "ownership"
    },
    "listInvites": {
        "method": "GET",
        "path": "/api/v1/identity/invites",
        "permission": null,
        "gate": "ownership"
    },
    "listMembershipRoleAssignments": {
        "method": "GET",
        "path": "/api/v1/platform/memberships/{membershipId}/roles",
        "permission": null,
        "gate": "ownership"
    },
    "listOwnPanditAssignments": {
        "method": "GET",
        "path": "/api/v1/pandit/assignments",
        "permission": null,
        "gate": "ownership"
    },
    "listOwnPanditAvailability": {
        "method": "GET",
        "path": "/api/v1/pandit/availability",
        "permission": null,
        "gate": "ownership"
    },
    "listOwnPanditReviews": {
        "method": "GET",
        "path": "/api/v1/pandit/reviews",
        "permission": null,
        "gate": "ownership"
    },
    "listOwnSessions": {
        "method": "GET",
        "path": "/api/v1/identity/sessions",
        "permission": null,
        "gate": "ownership"
    },
    "listOwnTenantMemberships": {
        "method": "GET",
        "path": "/api/v1/platform/tenants",
        "permission": null,
        "gate": "ownership"
    },
    "listPanditProfiles": {
        "method": "GET",
        "path": "/api/v1/pandit/profiles",
        "permission": null,
        "gate": "ownership"
    },
    "listPartnerTempleManagers": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/managers",
        "permission": null,
        "gate": "ownership"
    },
    "listPartnerTemplePujas": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples/{templeId}/pujas",
        "permission": null,
        "gate": "ownership"
    },
    "listPartnerTemples": {
        "method": "GET",
        "path": "/api/v1/temple/partner/temples",
        "permission": null,
        "gate": "ownership"
    },
    "listPolicies": {
        "method": "GET",
        "path": "/api/v1/policies",
        "permission": "platform:policy:view",
        "gate": null
    },
    "listPublicCatalogItems": {
        "method": "GET",
        "path": "/api/v1/catalog/public/items",
        "permission": null,
        "gate": "public"
    },
    "listPublicTemplePujas": {
        "method": "GET",
        "path": "/api/v1/temple/profiles/{templeId}/pujas",
        "permission": null,
        "gate": "ownership"
    },
    "listReservations": {
        "method": "GET",
        "path": "/api/v1/booking/reservations",
        "permission": null,
        "gate": "ownership"
    },
    "listReviewQueue": {
        "method": "GET",
        "path": "/api/v1/onboarding/review/queue",
        "permission": "onboarding:review:view",
        "gate": null
    },
    "listRoles": {
        "method": "GET",
        "path": "/api/v1/platform/roles",
        "permission": null,
        "gate": "ownership"
    },
    "listSavedItems": {
        "method": "GET",
        "path": "/api/v1/customers/saved",
        "permission": null,
        "gate": "ownership"
    },
    "listSodRules": {
        "method": "GET",
        "path": "/api/v1/platform/sod-rules",
        "permission": null,
        "gate": "ownership"
    },
    "listStaffSupportTickets": {
        "method": "GET",
        "path": "/api/v1/customers/support/staff/tickets",
        "permission": "customers:support:view",
        "gate": null
    },
    "listSupportTickets": {
        "method": "GET",
        "path": "/api/v1/customers/support/tickets",
        "permission": null,
        "gate": "ownership"
    },
    "listTempleProfiles": {
        "method": "GET",
        "path": "/api/v1/temple/profiles",
        "permission": null,
        "gate": "ownership"
    },
    "listTenantUsers": {
        "method": "GET",
        "path": "/api/v1/platform/users",
        "permission": "platform:user:view",
        "gate": null
    },
    "listVendorInventory": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}/inventory",
        "permission": null,
        "gate": "ownership"
    },
    "listVendorOrders": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}/orders",
        "permission": null,
        "gate": "ownership"
    },
    "listVendorPayouts": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}/payouts",
        "permission": null,
        "gate": "ownership"
    },
    "listVendorProducts": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors/{id}/products",
        "permission": null,
        "gate": "ownership"
    },
    "listVendors": {
        "method": "GET",
        "path": "/api/v1/vendor/partner/vendors",
        "permission": null,
        "gate": "ownership"
    },
    "loginCustomer": {
        "method": "POST",
        "path": "/api/v1/customers/auth/login",
        "permission": null,
        "gate": "public"
    },
    "logoutCustomer": {
        "method": "POST",
        "path": "/api/v1/customers/auth/logout",
        "permission": null,
        "gate": "ownership"
    },
    "openSupportTicket": {
        "method": "POST",
        "path": "/api/v1/customers/support/tickets",
        "permission": null,
        "gate": "ownership"
    },
    "paymentWebhook": {
        "method": "POST",
        "path": "/api/v1/payment/webhook",
        "permission": null,
        "gate": "public"
    },
    "placeVendorOrder": {
        "method": "POST",
        "path": "/api/v1/vendor/partner/vendors/{id}/orders",
        "permission": null,
        "gate": "ownership"
    },
    "publishCatalogItem": {
        "method": "POST",
        "path": "/api/v1/catalog/items/{id}:publish",
        "permission": "catalog:item:manage",
        "gate": null
    },
    "quoteCatalogPrices": {
        "method": "POST",
        "path": "/api/v1/catalog/quote",
        "permission": null,
        "gate": "ownership"
    },
    "reactivateCustomer": {
        "method": "POST",
        "path": "/api/v1/customers/profile:reactivate",
        "permission": null,
        "gate": "public"
    },
    "reactivatePandit": {
        "method": "POST",
        "path": "/api/v1/pandit/admin/profiles/{id}:reactivate",
        "permission": "pandit:profile:manage",
        "gate": null
    },
    "reactivateTemple": {
        "method": "POST",
        "path": "/api/v1/temple/admin/profiles/{id}:reactivate",
        "permission": "temple:profile:manage",
        "gate": null
    },
    "readAllCustomerNotifications": {
        "method": "POST",
        "path": "/api/v1/customers/notifications:read-all",
        "permission": null,
        "gate": "ownership"
    },
    "readCustomerNotification": {
        "method": "POST",
        "path": "/api/v1/customers/notifications/{id}:read",
        "permission": null,
        "gate": "ownership"
    },
    "regenerateMfaRecoveryCodes": {
        "method": "POST",
        "path": "/api/v1/identity/mfa/recovery-codes",
        "permission": null,
        "gate": "ownership"
    },
    "rejectApplication": {
        "method": "POST",
        "path": "/api/v1/onboarding/review/{id}:reject",
        "permission": "onboarding:review:reject",
        "gate": null
    },
    "removePartnerTempleManager": {
        "method": "DELETE",
        "path": "/api/v1/temple/partner/temples/{templeId}/managers/{managerId}",
        "permission": null,
        "gate": "ownership"
    },
    "removePartnerTemplePuja": {
        "method": "DELETE",
        "path": "/api/v1/temple/partner/temples/{templeId}/pujas/{pujaId}",
        "permission": null,
        "gate": "ownership"
    },
    "replaceOwnPanditAvailability": {
        "method": "PUT",
        "path": "/api/v1/pandit/availability",
        "permission": null,
        "gate": "ownership"
    },
    "replySupportTicket": {
        "method": "POST",
        "path": "/api/v1/customers/support/tickets/{id}/messages",
        "permission": null,
        "gate": "ownership"
    },
    "requestApplicationInfo": {
        "method": "POST",
        "path": "/api/v1/onboarding/review/{id}:request-info",
        "permission": "onboarding:review:view",
        "gate": null
    },
    "requestCustomerOtp": {
        "method": "POST",
        "path": "/api/v1/customers/auth/otp",
        "permission": null,
        "gate": "public"
    },
    "requestVerification": {
        "method": "POST",
        "path": "/api/v1/customers/verifications",
        "permission": null,
        "gate": "ownership"
    },
    "rescheduleReservation": {
        "method": "POST",
        "path": "/api/v1/booking/reservations/{id}:reschedule",
        "permission": null,
        "gate": "ownership"
    },
    "reviewReservation": {
        "method": "POST",
        "path": "/api/v1/booking/reservations/{id}/reviews",
        "permission": null,
        "gate": "ownership"
    },
    "revokeApiKey": {
        "method": "POST",
        "path": "/api/v1/platform/api-keys/{id}:revoke",
        "permission": null,
        "gate": "ownership"
    },
    "revokeInvite": {
        "method": "POST",
        "path": "/api/v1/identity/invites/{id}:revoke",
        "permission": null,
        "gate": "ownership"
    },
    "revokeMembershipRole": {
        "method": "DELETE",
        "path": "/api/v1/platform/memberships/{membershipId}/roles/{assignmentId}",
        "permission": null,
        "gate": "ownership"
    },
    "revokeMfa": {
        "method": "POST",
        "path": "/api/v1/identity/mfa/revoke",
        "permission": null,
        "gate": "ownership"
    },
    "revokeOAuthToken": {
        "method": "POST",
        "path": "/api/oauth/revoke",
        "permission": null,
        "gate": "public"
    },
    "revokeOtherSessions": {
        "method": "POST",
        "path": "/api/v1/identity/sessions:revoke-others",
        "permission": null,
        "gate": "ownership"
    },
    "rotateApiKey": {
        "method": "POST",
        "path": "/api/v1/platform/api-keys/{id}:rotate",
        "permission": null,
        "gate": "ownership"
    },
    "saveApplicationStep": {
        "method": "PUT",
        "path": "/api/v1/onboarding/applications/{id}/step/{step}",
        "permission": "onboarding:application:create",
        "gate": null
    },
    "saveItem": {
        "method": "POST",
        "path": "/api/v1/customers/saved",
        "permission": null,
        "gate": "ownership"
    },
    "setCatalogPrice": {
        "method": "POST",
        "path": "/api/v1/catalog/items/{id}/prices",
        "permission": "catalog:item:manage",
        "gate": null
    },
    "signupCustomer": {
        "method": "POST",
        "path": "/api/v1/customers/auth/signup",
        "permission": null,
        "gate": "public"
    },
    "staffCloseSupportTicket": {
        "method": "POST",
        "path": "/api/v1/customers/support/staff/tickets/{id}:close",
        "permission": "customers:support:manage",
        "gate": null
    },
    "staffReplySupportTicket": {
        "method": "POST",
        "path": "/api/v1/customers/support/staff/tickets/{id}/messages",
        "permission": "customers:support:manage",
        "gate": null
    },
    "startApplication": {
        "method": "POST",
        "path": "/api/v1/onboarding/applications",
        "permission": "onboarding:application:create",
        "gate": null
    },
    "startPanditApplication": {
        "method": "POST",
        "path": "/api/v1/pandit/apply",
        "permission": null,
        "gate": "ownership"
    },
    "startPanditAssignment": {
        "method": "POST",
        "path": "/api/v1/pandit/assignments/{id}:start",
        "permission": null,
        "gate": "ownership"
    },
    "streamDocumentContent": {
        "method": "GET",
        "path": "/api/v1/onboarding/documents/{id}/content",
        "permission": "onboarding:document:access",
        "gate": null
    },
    "suspendPandit": {
        "method": "POST",
        "path": "/api/v1/pandit/admin/profiles/{id}:suspend",
        "permission": "pandit:profile:manage",
        "gate": null
    },
    "suspendTemple": {
        "method": "POST",
        "path": "/api/v1/temple/admin/profiles/{id}:suspend",
        "permission": "temple:profile:manage",
        "gate": null
    },
    "suspendUser": {
        "method": "POST",
        "path": "/api/v1/platform/users/{id}:suspend",
        "permission": "platform:user:manage",
        "gate": null
    },
    "suspendVendor": {
        "method": "POST",
        "path": "/api/v1/vendor/admin/vendors/{id}:suspend",
        "permission": "vendor:profile:manage",
        "gate": null
    },
    "unsaveItem": {
        "method": "DELETE",
        "path": "/api/v1/customers/saved/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "updateCatalogItem": {
        "method": "PATCH",
        "path": "/api/v1/catalog/items/{id}",
        "permission": "catalog:item:manage",
        "gate": null
    },
    "updateCustomerAddress": {
        "method": "PATCH",
        "path": "/api/v1/customers/addresses/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "updateCustomerProfile": {
        "method": "PATCH",
        "path": "/api/v1/customers/profile",
        "permission": null,
        "gate": "ownership"
    },
    "updateCustomerSettings": {
        "method": "PATCH",
        "path": "/api/v1/customers/settings",
        "permission": null,
        "gate": "ownership"
    },
    "updateOwnPanditProfile": {
        "method": "PATCH",
        "path": "/api/v1/pandit/profile",
        "permission": null,
        "gate": "ownership"
    },
    "updateOwnProfile": {
        "method": "PATCH",
        "path": "/api/v1/identity/profile",
        "permission": null,
        "gate": "ownership"
    },
    "updatePartnerTemple": {
        "method": "PATCH",
        "path": "/api/v1/temple/partner/temples/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "updatePartnerTempleManager": {
        "method": "PUT",
        "path": "/api/v1/temple/partner/temples/{templeId}/managers/{managerId}",
        "permission": null,
        "gate": "ownership"
    },
    "updatePartnerTemplePuja": {
        "method": "PUT",
        "path": "/api/v1/temple/partner/temples/{templeId}/pujas/{pujaId}",
        "permission": null,
        "gate": "ownership"
    },
    "updatePolicy": {
        "method": "PATCH",
        "path": "/api/v1/policies/{policy}",
        "permission": "platform:policy:configure",
        "gate": null
    },
    "updateSodRule": {
        "method": "PATCH",
        "path": "/api/v1/platform/sod-rules/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "updateVendor": {
        "method": "PATCH",
        "path": "/api/v1/vendor/partner/vendors/{id}",
        "permission": null,
        "gate": "ownership"
    },
    "updateVendorProduct": {
        "method": "PATCH",
        "path": "/api/v1/vendor/partner/vendors/{id}/products/{itemId}",
        "permission": null,
        "gate": "ownership"
    },
    "uploadApplicationDocument": {
        "method": "POST",
        "path": "/api/v1/onboarding/applications/{id}/documents",
        "permission": "onboarding:application:create",
        "gate": null
    },
    "verifyCustomerSignup": {
        "method": "POST",
        "path": "/api/v1/customers/auth/verify",
        "permission": null,
        "gate": "public"
    },
    "verifyMfa": {
        "method": "POST",
        "path": "/api/v1/identity/mfa/verify",
        "permission": null,
        "gate": "ownership"
    },
    "verifyPandit": {
        "method": "POST",
        "path": "/api/v1/pandit/admin/profiles/{id}:verify",
        "permission": "pandit:profile:manage",
        "gate": null
    },
    "verifyTemple": {
        "method": "POST",
        "path": "/api/v1/temple/admin/profiles/{id}:verify",
        "permission": "temple:profile:manage",
        "gate": null
    },
    "verifyVendor": {
        "method": "POST",
        "path": "/api/v1/vendor/admin/vendors/{id}:verify",
        "permission": "vendor:profile:manage",
        "gate": null
    }
} as const satisfies Record<string, GeneratedOpenApiOperation>;

export type OpenApiOperationId = keyof typeof OPENAPI_OPERATIONS;
