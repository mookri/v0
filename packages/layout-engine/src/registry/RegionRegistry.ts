export class RegionRegistry {
  private readonly regions = new Set<string>();

  register(region: string): void {
    this.regions.add(region);
  }

  has(region: string): boolean {
    return this.regions.has(region);
  }
}
