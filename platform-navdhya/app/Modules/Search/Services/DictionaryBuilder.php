<?php

namespace App\Modules\Search\Services;

use App\Modules\Search\Models\SearchDelete;
use App\Modules\Search\Models\SearchDictionary;
use App\Modules\Search\Models\SearchDocument;
use App\Modules\Search\Models\SearchPosting;
use Illuminate\Support\Str;

class DictionaryBuilder
{
    public function __construct(
        private readonly ?SearchNormalizer $normalizer = null
    ) {
    }

    private function normalizer(): SearchNormalizer
    {
        return $this->normalizer ?? new SearchNormalizer();
    }

    /**
     * Index a search document into search_dictionary, search_postings, and search_deletes.
     */
    public function indexDocument(SearchDocument $document, int $generation = 1): void
    {
        // 1. Remove previous postings and adjust doc_freq
        $this->removeDocumentPostings($document->id);

        $tenantId = $document->tenant_id;
        $locale = $document->locale ?: 'en';

        // 2. Prepare field contents
        $fields = [
            'title' => ['text' => (string) $document->title, 'weight' => 1.00],
            'subtitle' => ['text' => (string) ($document->subtitle ?? ''), 'weight' => 0.60],
            'body' => ['text' => (string) ($document->body ?? ''), 'weight' => 0.20],
        ];

        // Include keys from keys_json
        $keysJson = $document->keys_json ?? [];
        if (! empty($keysJson['key_fold'])) {
            $fields['key_fold'] = ['text' => (string) $keysJson['key_fold'], 'weight' => 0.90];
        }
        if (! empty($keysJson['key_phon'])) {
            $fields['key_phon'] = ['text' => (string) $keysJson['key_phon'], 'weight' => 0.85];
        }
        if (! empty($keysJson['key_tr']) && is_array($keysJson['key_tr'])) {
            $fields['key_tr'] = ['text' => implode(' ', $keysJson['key_tr']), 'weight' => 0.85];
        }

        $allPostings = [];
        $uniqueTermsPerDoc = [];

        foreach ($fields as $fieldName => $fieldData) {
            $text = trim($fieldData['text']);
            if ($text === '') {
                continue;
            }

            $tokens = $this->normalizer()->tokenize($text, $locale);
            $tokenCounts = array_count_values($tokens);

            foreach ($tokenCounts as $token => $tf) {
                $termKey = mb_strtolower(trim($token), 'UTF-8');
                if ($termKey === '' || mb_strlen($termKey, 'UTF-8') < 2) {
                    continue;
                }

                $scheme = preg_match('/[\x{0900}-\x{097F}]/u', $termKey) ? 'devanagari' : 'latin_fold';

                $dict = SearchDictionary::firstOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'locale' => $locale,
                        'scheme' => $scheme,
                        'term_key' => $termKey,
                    ],
                    [
                        'key_len' => mb_strlen($termKey, 'UTF-8'),
                        'doc_freq' => 0,
                        'popularity' => 0,
                        'click_count' => 0,
                        'first_document_id' => $document->id,
                        'generation' => $generation,
                    ]
                );

                if (! isset($uniqueTermsPerDoc[$dict->id])) {
                    $uniqueTermsPerDoc[$dict->id] = $dict;
                }

                $allPostings[] = [
                    'id' => (string) Str::ulid(),
                    'dictionary_id' => $dict->id,
                    'search_document_id' => $document->id,
                    'field' => $fieldName,
                    'field_weight' => $fieldData['weight'],
                    'tf' => $tf,
                    'positions_json' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Build deletion neighborhood for dictionary entry
                $this->indexDeletions($termKey, $scheme);
            }
        }

        if (! empty($allPostings)) {
            SearchPosting::insert($allPostings);
        }

        foreach ($uniqueTermsPerDoc as $dict) {
            $dict->increment('doc_freq');
        }
    }

    /**
     * Remove postings for a document and decrement dictionary doc_freq.
     */
    public function removeDocumentPostings(string $documentId): void
    {
        $dictionaryIds = SearchPosting::where('search_document_id', $documentId)
            ->pluck('dictionary_id')
            ->unique()
            ->all();

        if (! empty($dictionaryIds)) {
            SearchPosting::where('search_document_id', $documentId)->delete();
            SearchDictionary::whereIn('id', $dictionaryIds)
                ->where('doc_freq', '>', 0)
                ->decrement('doc_freq');
        }
    }

    /**
     * Generate deletion neighborhood entries for a term (Task T-SR04-04).
     * All 1-edit deletions; 2-edit deletions if length >= 7.
     */
    public function indexDeletions(string $termKey, string $scheme = 'latin_fold'): void
    {
        $len = mb_strlen($termKey, 'UTF-8');
        if ($len < 3) {
            return;
        }

        $deletes = [];
        $chars = preg_split('//u', $termKey, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $charCount = count($chars);

        // 1-edit deletions
        for ($i = 0; $i < $charCount; $i++) {
            $delChars = $chars;
            array_splice($delChars, $i, 1);
            $delForm = implode('', $delChars);
            if ($delForm !== '') {
                $deletes[$delForm] = 1;
            }
        }

        // 2-edit deletions for terms with length >= 7
        if ($charCount >= 7) {
            for ($i = 0; $i < $charCount; $i++) {
                for ($j = $i + 1; $j < $charCount; $j++) {
                    $delChars = $chars;
                    // Remove higher index first to preserve lower index position
                    array_splice($delChars, $j, 1);
                    array_splice($delChars, $i, 1);
                    $delForm = implode('', $delChars);
                    if ($delForm !== '' && ! isset($deletes[$delForm])) {
                        $deletes[$delForm] = 2;
                    }
                }
            }
        }

        $rows = [];
        $now = now();
        foreach ($deletes as $delForm => $edits) {
            $rows[] = [
                'id' => (string) Str::ulid(),
                'delete_form' => $delForm,
                'original_key' => $termKey,
                'scheme' => $scheme,
                'edits' => $edits,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (! empty($rows)) {
            // Use insertOrIgnore to skip existing unique (delete_form, original_key, scheme)
            SearchDelete::insertOrIgnore($rows);
        }
    }
}
