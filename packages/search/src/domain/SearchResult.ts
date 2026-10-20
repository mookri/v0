export interface SearchResult {
  id: string;
  kind: 'puja' | 'pandit' | 'content';
  title: string;
  score: number;
}
