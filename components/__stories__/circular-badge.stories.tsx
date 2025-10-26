import React from 'react';
import { CircularBadge } from '../ui/circular-badge';

export default {
  title: 'Components/CircularBadge',
  component: CircularBadge,
  argTypes: {
    variant: {
      control: { type: 'select' },
      options: ['default', 'primary', 'secondary', 'success', 'warning', 'destructive', 'outline'],
    },
    size: {
      control: { type: 'select' },
      options: ['xs', 'sm', 'md', 'lg', 'xl', '2xl'],
    },
  },
};

const Template = (args) => <CircularBadge {...args} />;

export const Default = Template.bind({});
Default.args = {
  children: 'A',
  variant: 'default',
  size: 'md',
};

export const Primary = Template.bind({});
Primary.args = {
  children: 'P',
  variant: 'primary',
  size: 'md',
};

export const Secondary = Template.bind({});
Secondary.args = {
  children: 'S',
  variant: 'secondary',
  size: 'md',
};

export const Success = Template.bind({});
Success.args = {
  children: '✓',
  variant: 'success',
  size: 'md',
};

export const Warning = Template.bind({});
Warning.args = {
  children: '!',
  variant: 'warning',
  size: 'md',
};

export const Destructive = Template.bind({});
Destructive.args = {
  children: '✗',
  variant: 'destructive',
  size: 'md',
};

export const Outline = Template.bind({});
Outline.args = {
  children: 'O',
  variant: 'outline',
  size: 'md',
};

export const AllSizes = () => (
  <div className="flex items-end gap-4">
    <CircularBadge size="xs">XS</CircularBadge>
    <CircularBadge size="sm">SM</CircularBadge>
    <CircularBadge size="md">MD</CircularBadge>
    <CircularBadge size="lg">LG</CircularBadge>
    <CircularBadge size="xl">XL</CircularBadge>
    <CircularBadge size="2xl">2X</CircularBadge>
  </div>
);

export const Interactive = () => (
  <div className="flex gap-4">
    <CircularBadge 
      variant="primary" 
      size="lg" 
      className="cursor-pointer hover:scale-110 transition-transform"
    >
      ★
    </CircularBadge>
    <CircularBadge 
      variant="success" 
      size="lg" 
      className="cursor-pointer hover:scale-110 transition-transform"
    >
      ✓
    </CircularBadge>
    <CircularBadge 
      variant="warning" 
      size="lg" 
      className="cursor-pointer hover:scale-110 transition-transform"
    >
      !
    </CircularBadge>
  </div>
);