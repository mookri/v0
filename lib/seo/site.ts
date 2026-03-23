export const SITE_URL = "https://www.navdhya.com";
export const SITE_NAME = "Navdhya";
export const DEFAULT_OG_IMAGE = "/favicon/android-chrome-512x512.png";
export const DEFAULT_OG_IMAGE_WIDTH = 1200;
export const DEFAULT_OG_IMAGE_HEIGHT = 630;
export const DEFAULT_LOCALE = "en_IN";
export const DEFAULT_LANGUAGE = "en-IN";

export function isAbsoluteUrl(value: string): boolean {
  return /^https?:\/\//i.test(value);
}

export function toAbsoluteUrl(pathOrUrl: string): string {
  if (!pathOrUrl) {
    return `${SITE_URL}${DEFAULT_OG_IMAGE}`;
  }

  if (isAbsoluteUrl(pathOrUrl)) {
    return pathOrUrl;
  }

  return `${SITE_URL}${pathOrUrl.startsWith("/") ? pathOrUrl : `/${pathOrUrl}`}`;
}

export function withSiteName(title: string): string {
  const normalized = title.trim().toLowerCase();
  const suffix = `| ${SITE_NAME.toLowerCase()}`;
  return normalized.endsWith(suffix) ? title.trim() : `${title.trim()} | ${SITE_NAME}`;
}
