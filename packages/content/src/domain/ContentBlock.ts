export type ContentBlockType = 'heading' | 'paragraph' | 'image' | 'quote' | 'list' | 'faq' | 'cta' | 'table';

export interface ContentBlock {
  type: ContentBlockType;
  text?: string;
  level?: number;
}
