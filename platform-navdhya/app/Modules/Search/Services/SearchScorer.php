<?php

namespace App\Modules\Search\Services;

use Carbon\Carbon;

/**
 * S59 — custom scorer (the relevance authority; FULLTEXT is recall-only).
 *
 * Operates on plain arrays or objects (Eloquent models); field access goes
 * through field() so unit tests can pass arrays without a DB connection.
 */
class SearchScorer
{
    private const K1 = 1.2;
    private const B = 0.75;
    private const TITLE_BOOST = 3.0;
    private const SUBTITLE_BOOST = 2.0;
    private const BODY_BOOST = 1.0;
    private const MAX_BODY_CHARS = 3000;

    private const W_BM25 = 1.00;
    private const W_EXACT = 0.60;
    private const W_PREFIX = 0.30;
    private const W_POP = 0.45;
    private const W_FRESH = 0.20;
    private const W_LOCALE = 0.15;
    private const W_PROX = 0.10;

    /** @var array<int,mixed> */
    private array $docs;
    /** @var array<string,array<int,float>> */
    private array $postings = [];
    /** @var array<int,float> */
    private array $docLengths = [];
    private float $avgDocLength = 0.0;
    private int $docCount = 0;
    private ?float $minPopLog = null;
    private ?float $maxPopLog = null;

    private SearchNormalizer $normalizer;

    /** @param iterable<int,mixed> $documents */
    public function __construct(iterable $documents, ?SearchNormalizer $normalizer = null)
    {
        $this->normalizer = $normalizer ?? new SearchNormalizer();
        $this->docs = [];
        foreach ($documents as $d) {
            $this->docs[] = $d;
        }
        $this->docCount = count($this->docs);
        $this->build();
    }

    /** @param mixed $doc @param mixed $default @return mixed */
    public static function field($doc, string $key, mixed $default = null): mixed
    {
        if (is_array($doc)) {
            return array_key_exists($key, $doc) ? $doc[$key] : $default;
        }
        if (is_object($doc)) {
            if (isset($doc->{$key})) {
                return $doc->{$key};
            }
            if ($doc instanceof \ArrayAccess && isset($doc[$key])) {
                return $doc[$key];
            }
        }
        return $default;
    }

    /** @return array<int,array{doc:mixed,score:float}> */
    public function rank(string $query, string $locale = 'en', int $limit = 20): array
    {
        $queryTokens = $this->normalizer->tokenize($query, $locale);
        $queryTokens = array_values(array_unique($queryTokens));
        if (empty($queryTokens) || $this->docCount === 0) {
            return [];
        }

        $normalizedQuery = $this->normalizer->normalize($query);

        $scores = [];
        for ($i = 0; $i < $this->docCount; $i++) {
            $scores[$i] = $this->scoreDoc($i, $queryTokens, $normalizedQuery, $locale);
        }

        $ranked = [];
        for ($i = 0; $i < $this->docCount; $i++) {
            $ranked[] = ['doc' => $this->docs[$i], 'score' => $scores[$i]];
        }

        usort($ranked, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            $pa = $this->popularityRaw($a['doc']);
            $pb = $this->popularityRaw($b['doc']);
            if ($pa !== $pb) {
                return $pb <=> $pa;
            }
            $ta = (string) self::field($a['doc'], 'title', '');
            $tb = (string) self::field($b['doc'], 'title', '');
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return (string) self::field($a['doc'], 'id', '') <=> (string) self::field($b['doc'], 'id', '');
        });

        return array_slice($ranked, 0, max(1, $limit));
    }

    /** @return array<int,mixed> */
    public function suggest(string $prefix, int $limit = 8): array
    {
        $tokens = $this->normalizer->tokenize($prefix, 'en');
        $p = $tokens[0] ?? null;
        if (! $p || $this->docCount === 0) {
            $p = mb_strtolower(trim($prefix), 'UTF-8');
            if (mb_strlen($p, 'UTF-8') < 2) {
                return [];
            }
        }

        $docScores = [];
        foreach ($this->postings as $term => $entries) {
            if (! str_starts_with($term, $p)) {
                continue;
            }
            $weight = (mb_strlen($term, 'UTF-8') === mb_strlen($p, 'UTF-8')) ? 2.0 : 1.0;
            foreach ($entries as $docIndex => $tf) {
                $docScores[$docIndex] = ($docScores[$docIndex] ?? 0.0) + $weight * min($tf, 5.0);
            }
        }

        foreach (array_keys($docScores) as $docIndex) {
            $docScores[$docIndex] += $this->popularityNorm($this->docs[$docIndex]) * 2.0;
        }

        arsort($docScores);
        $out = [];
        $i = 0;
        foreach ($docScores as $docIndex => $s) {
            $out[] = $this->docs[$docIndex];
            $i++;
            if ($i >= max(1, $limit)) {
                break;
            }
        }
        return $out;
    }

    private function build(): void
    {
        $totalLength = 0;
        $popLogs = [];

        foreach ($this->docs as $docIndex => $doc) {
            $locale = (string) self::field($doc, 'locale', 'en');
            $titleTokens = $this->normalizer->tokenize((string) self::field($doc, 'title', ''), $locale);
            $subtitleTokens = $this->normalizer->tokenize((string) self::field($doc, 'subtitle', ''), $locale);
            $body = mb_substr((string) self::field($doc, 'body', ''), 0, self::MAX_BODY_CHARS, 'UTF-8');
            $bodyTokens = $this->normalizer->tokenize($body, $locale);

            $tfMap = [];
            $add = function (array $tokens, float $weight) use (&$tfMap): void {
                foreach ($tokens as $t) {
                    $tfMap[$t] = ($tfMap[$t] ?? 0.0) + $weight;
                }
            };

            $add($titleTokens, self::TITLE_BOOST);
            $add($subtitleTokens, self::SUBTITLE_BOOST);
            $add($bodyTokens, self::BODY_BOOST);

            $length = 0.0;
            foreach ($tfMap as $term => $tf) {
                $length += $tf;
                if (! isset($this->postings[$term])) {
                    $this->postings[$term] = [];
                }
                $this->postings[$term][$docIndex] = ($this->postings[$term][$docIndex] ?? 0.0) + $tf;
            }

            $this->docLengths[$docIndex] = $length;
            $totalLength += $length;

            $popLogs[$docIndex] = log(1.0 + $this->popularityRaw($doc));
        }

        $this->avgDocLength = $this->docCount > 0 ? $totalLength / $this->docCount : 0.0;

        if (count($popLogs) > 0) {
            $this->minPopLog = min($popLogs);
            $this->maxPopLog = max($popLogs);
        } else {
            $this->minPopLog = 0.0;
            $this->maxPopLog = 0.0;
        }
    }

    /** @param array<int,string> $queryTokens */
    private function scoreDoc(int $docIndex, array $queryTokens, string $normalizedQuery, string $locale): float
    {
        $doc = $this->docs[$docIndex];

        $bm25 = 0.0;
        $docLength = $this->docLengths[$docIndex] ?: 1.0;
        foreach ($queryTokens as $term) {
            $entries = $this->postings[$term] ?? null;
            if (! $entries || ! isset($entries[$docIndex])) {
                continue;
            }
            $df = count($entries);
            $idf = log(1.0 + ($this->docCount - $df + 0.5) / ($df + 0.5));
            $tf = $entries[$docIndex];
            $norm = self::K1 * (1.0 - self::B + (self::B * $docLength) / ($this->avgDocLength ?: 1.0));
            $contrib = ($idf * $tf * (self::K1 + 1.0)) / ($tf + $norm);
            $bm25 += $contrib;
        }

        $titleNorm = $this->normalizer->normalize((string) self::field($doc, 'title', ''));
        $exact = ($titleNorm !== '' && $titleNorm === $normalizedQuery) ? 1.0 : 0.0;

        $docLocale = (string) self::field($doc, 'locale', 'en');
        $titleTokens = $this->normalizer->tokenize((string) self::field($doc, 'title', ''), $docLocale);
        $prefixHits = 0;
        foreach ($queryTokens as $qt) {
            foreach ($titleTokens as $tt) {
                if ($tt !== '' && str_starts_with($tt, $qt)) {
                    $prefixHits++;
                    break;
                }
            }
        }
        $prefixScore = count($queryTokens) > 0 ? $prefixHits / count($queryTokens) : 0.0;

        $pop = $this->popularityNorm($doc);

        $fresh = 0.5;
        $indexedAt = self::field($doc, 'indexed_at', null);
        if ($indexedAt) {
            try {
                $ia = $indexedAt instanceof Carbon ? $indexedAt : Carbon::parse($indexedAt);
                $ageDays = max(0, (int) $ia->diffInDays(Carbon::now()));
                $fresh = exp(-$ageDays / 180.0);
            } catch (\Throwable $e) {
                $fresh = 0.5;
            }
        }

        $localeMatch = ($docLocale === $locale) ? 1.0 : 0.0;

        $prox = 0.0;
        if (count($queryTokens) >= 2) {
            $hits = 0;
            $pairs = count($queryTokens) - 1;
            for ($i = 0; $i < $pairs; $i++) {
                $a = $queryTokens[$i];
                $b = $queryTokens[$i + 1];
                for ($j = 0; $j + 1 < count($titleTokens); $j++) {
                    if ($titleTokens[$j] === $a && $titleTokens[$j + 1] === $b) {
                        $hits++;
                        break;
                    }
                }
            }
            $prox = $hits / $pairs;
        }

        $score = self::W_BM25 * $bm25
            + self::W_EXACT * $exact
            + self::W_PREFIX * $prefixScore
            + self::W_POP * $pop
            + self::W_FRESH * $fresh
            + self::W_LOCALE * $localeMatch
            + self::W_PROX * $prox;

        return round($score, 6);
    }

    /** @param mixed $doc */
    private function popularityRaw($doc): float
    {
        $b = (float) self::field($doc, 'bookings_30d', 0);
        $v = (float) self::field($doc, 'views_7d', 0);
        return (pow($b, 0.5) + pow($v, 0.3));
    }

    /** @param mixed $doc */
    private function popularityNorm($doc): float
    {
        $raw = $this->popularityRaw($doc);
        $log = log(1.0 + $raw);
        $min = $this->minPopLog ?? 0.0;
        $max = $this->maxPopLog ?? 0.0;
        if ($max - $min < 1e-9) {
            return 0.0;
        }
        return ($log - $min) / ($max - $min);
    }
}
