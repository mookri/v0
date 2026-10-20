# Content Capability

Canonical meaning: typed documents, publication state, relationships, entity references. Never renders React/HTTP/SEO framework objects.

Owner: content domain
Entities: ContentDocument, ContentBlock, EntityReference
Use cases: get-document, resolve-content (effective scheduled doc), get-related-content
Public API: `@navdhya/content`
Events: ContentPublished, ContentUpdated, ContentUnpublished
Persistence ownership (Owns): content documents index (source corpus stays at apps/web/content via adapter)
Does not own: puja business entity, SEO metadata, UI.
