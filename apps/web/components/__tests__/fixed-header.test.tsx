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

describe('Fixed SiteHeader', () => {
  it('should render the header with logo', () => {
    const { getByText, getByLabelText } = render(<SiteHeader />);
    
    // Check for logo
    expect(getByText('Navdhya')).toBeInTheDocument();
    expect(getByLabelText('Navdhya Home')).toBeInTheDocument();
  });

  it('should render desktop navigation links', () => {
    const { getByText } = render(<SiteHeader />);
    
    // Check for main navigation links
    expect(getByText('Home')).toBeInTheDocument();
    expect(getByText('About')).toBeInTheDocument();
    expect(getByText('Services')).toBeInTheDocument();
    expect(getByText('How It Works')).toBeInTheDocument();
    expect(getByText('Blog')).toBeInTheDocument();
    expect(getByText('Contact')).toBeInTheDocument();
  });

  it('should render action icons', () => {
    const { getByTestId } = render(<SiteHeader />);
    
    // Check for action icons
    expect(getByTestId('search-icon')).toBeInTheDocument();
    expect(getByTestId('phone-icon')).toBeInTheDocument();
    expect(getByTestId('user-icon')).toBeInTheDocument();
    expect(getByTestId('heart-icon')).toBeInTheDocument();
    expect(getByTestId('cart-icon')).toBeInTheDocument();
  });

  it('should render promotional banner', () => {
    const { getByText } = render(<SiteHeader />);
    
    // Check for promotional banner content
    expect(getByText('Limited time offer')).toBeInTheDocument();
    expect(getByText('-15%')).toBeInTheDocument();
  });
});