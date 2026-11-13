import React from 'react';
import { render, screen, act } from '@testing-library/react';
import { RecommendationReason } from '../discovery/RecommendationReason';
import { PersonalizationSettings } from '../discovery/PersonalizationSettings';

describe('Discovery UI Components (Sacred Ember Token Compliance)', () => {
  beforeEach(() => {
    // Mock global fetch for PersonalizationSettings
    global.fetch = jest.fn().mockImplementation(() =>
      Promise.resolve({
        ok: true,
        json: () =>
          Promise.resolve({
            personalization_enabled: true,
            tradition: 'smartha',
            gotra: 'kashyapa',
            preferred_region_id: 'varanasi',
            preferred_language: 'en',
          }),
      })
    ) as jest.Mock;
  });

  afterEach(() => {
    jest.resetAllMocks();
  });

  it('renders RecommendationReason chip with Sacred Ember styling', () => {
    const { container } = render(
      <RecommendationReason
        reasonCode="FESTIVAL_SPECIAL"
        reasonLabel="Sacred seasonal ritual special"
      />
    );

    expect(screen.getByText('Sacred seasonal ritual special')).toBeInTheDocument();

    const chip = container.firstChild as HTMLElement;
    expect(chip).toHaveAttribute('data-reason-code', 'FESTIVAL_SPECIAL');
    // Ensure styles use Soft Sand background and Aubergine Ink text
    expect(chip.style.backgroundColor).toBe('rgb(239, 227, 211)'); // #EFE3D3 Soft Sand
    expect(chip.style.color).toBe('rgb(44, 32, 51)'); // #2C2033 Aubergine Ink
  });

  it('renders PersonalizationSettings control panel with zero green', async () => {
    let renderedContainer: HTMLElement;
    await act(async () => {
      const { container } = render(<PersonalizationSettings />);
      renderedContainer = container;
    });

    expect(screen.getByText('Sacred Discovery & Privacy Preferences')).toBeInTheDocument();
    expect(screen.getByText('Enable Ritual Personalization')).toBeInTheDocument();
    expect(screen.getByText('Save Preferences')).toBeInTheDocument();
    expect(screen.getByText('Reset Inferred Recommendations')).toBeInTheDocument();
    expect(screen.getByText('Clear Search History')).toBeInTheDocument();

    // Verify STRICTLY zero green in HTML content or inline styles
    const html = renderedContainer!.innerHTML.toLowerCase();
    expect(html).not.toContain('green');
    expect(html).not.toContain('#00ff00');
    expect(html).not.toContain('emerald');
  });
});
