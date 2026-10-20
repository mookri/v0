export interface LayoutRegistration {
  shell: string;
  layouts: string[];
}

export class LayoutRegistry {
  private readonly entries = new Map<string, LayoutRegistration>();

  register(entry: LayoutRegistration): void {
    this.entries.set(entry.shell, entry);
  }

  get(shell: string): LayoutRegistration | undefined {
    return this.entries.get(shell);
  }

  shells(): string[] {
    return [...this.entries.keys()];
  }
}
