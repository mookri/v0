export function isSafeReturnUrl(url: string): boolean {
  if (url.startsWith("http://") || url.startsWith("https://") || url.startsWith("//") || url.startsWith("javascript:")) {
    return false;
  }
  return url.startsWith("/");
}
