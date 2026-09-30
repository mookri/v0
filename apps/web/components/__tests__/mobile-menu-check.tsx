// Simple test to verify mobile menu structure
import React from 'react';
import { render } from '@testing-library/react';
import { SiteHeader } from '../site-header';

// Mock next/link
jest.mock('next/link', () => {
  return {
    __esModule: true,
    default: ({ children, href, ...props }: any) => (
      <a href={href} {...props}>
        {children}
      </a>
    ),
  };
});

describe.skip('Mobile Menu Structure', () => {
  it('should have proper mobile menu structure', () => {
    // Mock window.innerWidth to simulate mobile view
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 375,
    });

    const { container } = render(<SiteHeader />);
    
    // Check that the mobile menu button exists
    const menuButton = container.querySelector('[aria-label="Open menu"]');
    expect(menuButton).toBeInTheDocument();
    
    // Check that the menu button has the correct text
    expect(menuButton).toHaveTextContent('Menu');
  });
});