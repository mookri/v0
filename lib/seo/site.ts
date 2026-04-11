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
  const cleanTitle = title
    .replace(/\s+/g, " ")
    .replace(/\s*(?:\||-)\s*navdhya\s*$/i, "")
    .trim();

  if (!cleanTitle) return SITE_NAME;
  if (cleanTitle.toLowerCase() === SITE_NAME.toLowerCase()) return SITE_NAME;

  return `${cleanTitle} | ${SITE_NAME}`;
}
