# Search Capability

Purpose: discovery orchestration — "what matches my query?" across puja/pandit/content sources. Swappable backend (Postgres LIKE → OpenSearch/Meilisearch later).

Owner: search domain

Entities: SearchQuery, SearchResult
Use cases: search-all (fans out to sources, merges by score)
Public API: `@navdhya/search`

Events: none owned
Dependencies: foundation, puja, pandit (via PujaSearchSource, PanditSearchSource, ContentSearchSource ports — never direct repo access)
Persistence ownership (Owns): none (read-only orchestration; no tables owned)
Does not own: puja/pandit/content data
Extraction readiness: source ports defined; ranking is merge-by-score placeholder.
