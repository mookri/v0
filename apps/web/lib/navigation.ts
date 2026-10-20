// Navigation seam: components leave the page through here so tests can
// mock the destination without jsdom attempting a real navigation
// (Location is sealed — href/assign cannot be spied on).
export function navigateTo(url: string): void {
  window.location.assign(url);
}
