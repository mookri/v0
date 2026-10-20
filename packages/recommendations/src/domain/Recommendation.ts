export interface Recommendation {
  id: string;
  kind: 'puja' | 'pandit' | 'content';
  title: string;
  reason: string;
}
