export class ShellRegistry {
  private readonly shells = new Set<string>();

  register(shell: string): void {
    this.shells.add(shell);
  }

  has(shell: string): boolean {
    return this.shells.has(shell);
  }

  list(): string[] {
    return [...this.shells];
  }
}
