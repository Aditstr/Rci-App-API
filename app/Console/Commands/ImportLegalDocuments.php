<?php

namespace App\Console\Commands;

use App\Models\LegalDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportLegalDocuments extends Command
{
    protected $signature = 'rag:import
        {path : File .txt/.md or directory containing documents}
        {--category=umum : Document category}
        {--title= : Title for a single file}
        {--chunk=3000 : Maximum characters per chunk}';

    protected $description = 'Import text documents into the legal document RAG table';

    public function handle(): int
    {
        $input = $this->argument('path');
        $path = is_file($input) ? $input : base_path($input);
        $files = is_file($path) ? [$path] : glob($path . '/*.{txt,md}', GLOB_BRACE);

        if (! $files) {
            $this->error('Tidak ada file .txt atau .md yang ditemukan.');

            return self::FAILURE;
        }

        $chunkSize = max(500, (int) $this->option('chunk'));
        $total = 0;

        foreach ($files as $file) {
            $content = trim((string) file_get_contents($file));
            if ($content === '') {
                continue;
            }

            $title = $this->option('title') ?: pathinfo($file, PATHINFO_FILENAME);
            $chunks = $this->chunks($content, $chunkSize);

            foreach ($chunks as $index => $chunk) {
                $part = count($chunks) > 1 ? ' - Bagian ' . ($index + 1) : '';
                LegalDocument::updateOrCreate(
                    ['title' => $title . $part],
                    [
                        'content' => $chunk,
                        'keywords' => $this->keywords($title . ' ' . $chunk),
                        'category' => $this->option('category'),
                    ],
                );
                $total++;
            }

            $this->line("Imported: {$file} (" . count($chunks) . ' bagian)');
        }

        $this->info("Selesai: {$total} bagian masuk ke legal_documents.");

        return self::SUCCESS;
    }

    private function chunks(string $content, int $limit): array
    {
        $paragraphs = preg_split('/\R\s*\R/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if (mb_strlen($current . "\n\n" . $paragraph) > $limit && $current !== '') {
                $chunks[] = trim($current);
                $current = '';
            }
            $current .= ($current === '' ? '' : "\n\n") . $paragraph;
        }

        if ($current !== '') {
            $chunks[] = trim($current);
        }

        return $chunks;
    }

    private function keywords(string $text): string
    {
        $words = preg_split('/[^\pL\pN]+/u', Str::lower($text), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn (string $word) => mb_strlen($word) >= 4);

        return implode(', ', array_slice(array_unique($words), 0, 80));
    }
}
