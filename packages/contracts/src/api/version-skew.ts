export const MIN_SUPPORTED_API_VERSION = '2026-11-01';

export class ApiVersionSkewError extends Error {
  readonly requiredFloor: string;
  readonly observedVersion: string;

  constructor(observedVersion: string, requiredFloor: string = MIN_SUPPORTED_API_VERSION) {
    super(
      `API floor skew detected: backend reported ${observedVersion}, but this frontend requires >= ${requiredFloor}.`,
    );
    this.name = 'ApiVersionSkewError';
    this.observedVersion = observedVersion;
    this.requiredFloor = requiredFloor;
  }
}

export function isApiVersionCompatible(
  observedVersion: string | null | undefined,
  requiredFloor: string = MIN_SUPPORTED_API_VERSION,
): boolean {
  if (!observedVersion || !/^\d{4}-\d{2}-\d{2}$/.test(observedVersion.trim())) {
    return false;
  }
  return observedVersion.trim() >= requiredFloor;
}

export function assertApiFloorCompatible(
  headers: Headers | Record<string, string | undefined>,
  requiredFloor: string = MIN_SUPPORTED_API_VERSION,
): void {
  const version =
    headers instanceof Headers
      ? headers.get('x-navdhya-api-version') ?? headers.get('X-Navdhya-Api-Version')
      : headers['x-navdhya-api-version'] ?? headers['X-Navdhya-Api-Version'];

  if (!version) {
    return; // Header absent during legacy/mock dev calls
  }

  if (!isApiVersionCompatible(version, requiredFloor)) {
    throw new ApiVersionSkewError(version, requiredFloor);
  }
}

