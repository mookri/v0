import React from 'react';
import { render, screen } from '@testing-library/react';
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

// Mock lucide-react icons
jest.mock('lucide-react', () => ({
  Search: () => <div data-testid="search-icon" />,
  Phone: () => <div data-testid="phone-icon" />,
  User: () => <div data-testid="user-icon" />,
  Heart: () => <div data-testid="heart-icon" />,
  ShoppingCart: () => <div data-testid="cart-icon" />,
  Menu: () => <div data-testid="menu-icon" />,
  X: () => <div data-testid="close-icon" />,
  ChevronDown: () => <div data-testid="chevron-down-icon" />,
}));

describe('New SiteHeader', () => {
  it('should render the header with logo', () => {
    render(<SiteHeader />);
    
    // Check for logo
    expect(screen.getByText('Navdhya')).toBeInTheDocument();
    expect(screen.getByLabelText('Navdhya Home')).toBeInTheDocument();
  });

  it('should render desktop navigation links', () => {
    render(<SiteHeader />);
    
    // Check for main navigation links
    expect(screen.getByText('Home')).toBeInTheDocument();
    expect(screen.getByText('About')).toBeInTheDocument();
    expect(screen.getByText('Services')).toBeInTheDocument();
    expect(screen.getByText('How It Works')).toBeInTheDocument();
    expect(screen.getByText('Blog')).toBeInTheDocument();
    expect(screen.getByText('Contact')).toBeInTheDocument();
  });

  it('should render action icons', () => {
    render(<SiteHeader />);
    
    // Check for action icons
    expect(screen.getByTestId('search-icon')).toBeInTheDocument();
    expect(screen.getByTestId('phone-icon')).toBeInTheDocument();
    expect(screen.getByTestId('user-icon')).toBeInTheDocument();
    expect(screen.getByTestId('heart-icon')).toBeInTheDocument();
    expect(screen.getByTestId('cart-icon')).toBeInTheDocument();
  });

  it('should render promotional banner', () => {
    render(<SiteHeader />);
    
    // Check for promotional banner content
    expect(screen.getByText('Limited time offer')).toBeInTheDocument();
    expect(screen.getByText('-15%')).toBeInTheDocument();
  });
});