<?php

namespace App\Modules\SeoContent\Console;

use App\Modules\SeoContent\Models\SeoPage;
use App\Modules\SeoContent\Models\SeoCluster;
use Illuminate\Console\Command;

class SeoImportCommand extends Command
{
    protected $signature = 'seo:import {dir : Path to content/pages directory} {--force : Skip hash check and force update}';
    protected $description = 'Import SEO pages from JSON files into the database';

    public function handle(): int
    {
        $dir = $this->argument('dir');
        $force = $this->option('force');

        if (! is_dir($dir)) {
            $this->error("Directory not found: {$dir}");
            return self::FAILURE;
        }

        // Default clusters (fallback if registry.json missing)
        $defaultClusters = [
            'brand' => ['label' => 'Brand Knowledge', 'base_path' => 'explore'],
            'pujas' => ['label' => 'Delhi NCR Puja Services', 'base_path' => 'pujas'],
            'services' => ['label' => 'Puja Services', 'base_path' => 'services'],
            'ceremonies' => ['label' => 'Hindu Ceremonies', 'base_path' => 'ceremonies'],
            'ritual-guides' => ['label' => 'Ritual Guides', 'base_path' => 'ritual-guides'],
            'pandit-services' => ['label' => 'Pandit Services', 'base_path' => 'pandit-services'],
            'festival-rituals' => ['label' => 'Festival Rituals', 'base_path' => 'festival-rituals'],
            'spiritual-guides' => ['label' => 'Spiritual Guides', 'base_path' => 'spiritual-guides'],
            'faq' => ['label' => 'Frequently Asked Questions', 'base_path' => 'faq'],
            'blog' => ['label' => 'Navdhya Journal', 'base_path' => 'blog'],
            'compare' => ['label' => 'Delhi NCR Comparisons', 'base_path' => 'compare'],
            'centers' => ['label' => 'Service Centers', 'base_path' => 'centers'],
            'astrology' => ['label' => 'Astrology Guides', 'base_path' => 'astrology'],
            'vastu' => ['label' => 'Vastu Guides', 'base_path' => 'vastu'],
        ];

        // Load registry.json for cluster metadata
        $registryPath = $dir . '/../registry.json';
        if (file_exists($registryPath)) {
            $registry = json_decode(file_get_contents($registryPath), true);
            if (isset($registry['clusters'])) {
                foreach ($registry['clusters'] as $key => $meta) {
                    $defaultClusters[$key] = $meta;
                }
            }
        }

        // Upsert clusters
        foreach ($defaultClusters as $key => $meta) {
            $basePath = $meta['base_path'] ?? $meta['basePath'] ?? $key;
            SeoCluster::updateOrCreate(
                ['cluster' => $key],
                ['label' => $meta['label'], 'base_path' => $basePath]
            );
        }

        $files = glob($dir . '/*.json');
        $this->info("Found " . count($files) . " page files");

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        $progress = $this->output->createProgressBar(count($files));
        $progress->start();

        foreach ($files as $file) {
            try {
                $raw = file_get_contents($file);
                $page = json_decode($raw, true);

                if (! $page || ! isset($page['slug'], $page['cluster'], $page['title'], $page['content'])) {
                    $this->warn("\nSkipping {$file}: missing required fields");
                    $errors++;
                    $progress->advance();
                    continue;
                }

                // Compute content hash
                $contentHash = hash('sha256', $raw);

                // Check if page exists and hash matches (skip unchanged)
                $existing = SeoPage::where('slug', $page['slug'])->first(['content_hash']);

                if ($existing && ! $force && $existing->content_hash === $contentHash) {
                    $skipped++;
                    $progress->advance();
                    continue;
                }

                // Build search_text from content (strip HTML)
                $searchText = $this->stripHtml($page['content'] ?? '');
                // Also include title, meta fields for better search
                $searchText = implode(' ', array_filter([
                    $page['title'] ?? '',
                    $page['metaTitle'] ?? '',
                    $page['metaDescription'] ?? '',
                    $searchText,
                ]));

                // Upsert page
                SeoPage::updateOrCreate(
                    ['slug' => $page['slug']],
                    [
                        'cluster' => $page['cluster'],
                        'noindex' => $page['noindex'] ?? false,
                        'title' => $page['title'],
                        'meta_title' => $page['metaTitle'] ?? null,
                        'meta_description' => $page['metaDescription'] ?? null,
                        'last_updated' => $page['lastUpdated'] ?? null,
                        'automation_generated_at' => $page['automation']['generatedAt'] ?? null,
                        'content_hash' => $contentHash,
                        'page_json' => $page,
                        'search_text' => $searchText,
                    ]
                );

                $imported++;
            } catch (\Throwable $e) {
                $this->error("\nError importing {$file}: " . $e->getMessage());
                $errors++;
            }
            $progress->advance();
        }

        $progress->finish();
        $this->newLine(2);
        $this->info("Imported: {$imported} | Skipped (unchanged): {$skipped} | Errors: {$errors}");

        return self::SUCCESS;
    }

    private function stripHtml(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $html);
        // Remove HTML tags
        $text = strip_tags($html);
        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
