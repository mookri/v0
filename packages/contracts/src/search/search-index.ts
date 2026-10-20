export interface SearchIndexDoc {
  id: string;
  kind: string;
  title: string;
  text: string;
}

export interface SearchIndex {
  index(doc: SearchIndexDoc): Promise<void>;
  remove(id: string): Promise<void>;
  search(query: string, limit?: number): Promise<SearchIndexDoc[]>;
}
