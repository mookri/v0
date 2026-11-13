import { createBackchannelLogoutStore } from '@navdhya/auth-client';

const store = createBackchannelLogoutStore();

export const rememberLogoutJti = store.rememberLogoutJti;
export const markSidTerminated = store.markSidTerminated;
export const isSidTerminated = store.isSidTerminated;
export const payloadSidTerminated = store.payloadSidTerminated;
export const clearBackchannelLogoutState = store.clear;
