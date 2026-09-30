import React from 'react';
import { render, screen, fireEvent, waitFor } from '@testing-library/react';
import { SiteHeader } from '../site-header';
import '@testing-library/jest-dom';

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

// Mock next/navigation
jest.mock('next/navigation', () => ({
  useRouter() {
    return {
      prefetch: () => null,
    };
  },
}));

describe('SiteHeader', () => {
  beforeEach(() => {
    // Mock window.innerWidth to simulate mobile view
    Object.defineProperty(window, 'innerWidth', {
      writable: true,
      configurable: true,
      value: 375,
    });
  });

  it('should render the mobile menu button', () => {
    render(<SiteHeader />);
    
    const menuButton = screen.getByLabelText('Open menu');
    expect(menuButton).toBeInTheDocument();
    expect(menuButton).toHaveTextContent('Menu');
  });

  it('should open mobile menu when menu button is clicked', async () => {
    render(<SiteHeader />);
    
    const menuButton = screen.getByLabelText('Open menu');
    fireEvent.click(menuButton);
    
    // Wait for the menu to open
    await waitFor(() => {
      const mobileMenu = screen.getByLabelText('Mobile');
      expect(mobileMenu).toBeInTheDocument();
    });
  });

  it('should close mobile menu when close button is clicked', async () => {
    render(<SiteHeader />);
    
    // Open the menu
    const menuButton = screen.getByLabelText('Open menu');
    fireEvent.click(menuButton);
    
    // Wait for the menu to open
    await waitFor(() => {
      const mobileMenu = screen.getByLabelText('Mobile');
      expect(mobileMenu).toBeInTheDocument();
    });
    
    // Close the menu
    const closeButton = screen.getByLabelText('Close menu');
    fireEvent.click(closeButton);
    
    // Wait for the menu to close
    await waitFor(() => {
      const mobileMenu = screen.queryByLabelText('Mobile');
      expect(mobileMenu).not.toBeInTheDocument();
    });
  });

  it('should close mobile menu when clicking on a navigation link', async () => {
    render(<SiteHeader />);
    
    // Open the menu
    const menuButton = screen.getByLabelText('Open menu');
    fireEvent.click(menuButton);
    
    // Wait for the menu to open
    await waitFor(() => {
      const mobileMenu = screen.getByLabelText('Mobile');
      expect(mobileMenu).toBeInTheDocument();
    });
    
    // Click on a navigation link
    const homeLink = screen.getByText('Home');
    fireEvent.click(homeLink);
    
    // Wait for the menu to close
    await waitFor(() => {
      const mobileMenu = screen.queryByLabelText('Mobile');
      expect(mobileMenu).not.toBeInTheDocument();
    });
  });

  it('should render all navigation links in mobile menu', async () => {
    render(<SiteHeader />);
    
    // Open the menu
    const menuButton = screen.getByLabelText('Open menu');
    fireEvent.click(menuButton);
    
    // Wait for the menu to open
    await waitFor(() => {
      const mobileMenu = screen.getByLabelText('Mobile');
      expect(mobileMenu).toBeInTheDocument();
    });
    
    // Check that all main navigation links are present
    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('About')).toBeInTheDocument();
    expect(screen.getByText('How It Works')).toBeInTheDocument();
    expect(screen.getByText('Blog')).toBeInTheDocument();
    expect(screen.getByText('Contact')).toBeInTheDocument();
    
    // Check that services section is present
    expect(screen.getByText('Services')).toBeInTheDocument();
    
    // Check that accordion items are present
    expect(screen.getByText('Home Rituals')).toBeInTheDocument();
    expect(screen.getByText('Life Events')).toBeInTheDocument();
    expect(screen.getByText('Special Occasions')).toBeInTheDocument();
  });
});