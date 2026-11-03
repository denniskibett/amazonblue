<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateTree extends Command
{
    protected $signature = 'tree:generate {--output= : Where to write (default: docs/TREE-{host}-{date}.txt)}';
    protected $description = 'Write a project tree (excluding vendor, node_modules, .git, logs, builds)';

    public function handle(): int
    {
        $root = base_path();
        $host = gethostname() ?: 'unknown';
        $output = $this->option('output') ?: "docs/TREE-{$host}-" . date('Ymd') . ".txt";

        $skip = [
            'vendor/', 'node_modules/', '.git/',
            'storage/logs/', 'storage/framework/', 'storage/app/private/db-pulls/',
            'public/build/', 'public/_wt/', 'public/storage',
        ];

        $lines = [];
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $rel = str_replace('\\', '/', $rel);

            $skipIt = false;
            foreach ($skip as $s) {
                if (str_starts_with($rel, $s) || $rel === rtrim($s, '/')) {
                    $skipIt = true;
                    break;
                }
            }
            if ($skipIt) continue;

            $lines[] = ($file->isDir() ? 'D ' : 'F ') . $rel;
        }

        sort($lines);

        $path = $root . DIRECTORY_SEPARATOR . $output;
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, implode("\n", $lines) . "\n");

        $this->info("Wrote " . count($lines) . " entries to {$output}");
        return self::SUCCESS;
    }
}