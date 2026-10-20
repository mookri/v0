import { Puja } from './Puja';

export function isPujaAvailable(puja: Puja): boolean {
  return puja.isActive;
}
