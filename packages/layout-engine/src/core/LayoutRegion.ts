export type LayoutRegionId =
  | 'header'
  | 'hero'
  | 'primary-content'
  | 'secondary-content'
  | 'sidebar'
  | 'actions'
  | 'trust'
  | 'recommendations'
  | 'related'
  | 'content'
  | 'topbar'
  | 'summary'
  | 'upcoming'
  | 'bottom-navigation'
  | 'footer';

export interface LayoutRegion {
  id: LayoutRegionId;
}
