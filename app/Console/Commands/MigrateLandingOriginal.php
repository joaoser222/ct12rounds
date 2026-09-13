<?php

namespace App\Console\Commands;

use App\Services\LandingStorageService;
use Illuminate\Console\Command;

class MigrateLandingOriginal extends Command
{
    /**
     * The command name and signature.
     *
     * @var string
     */
    protected $signature = 'landing:migrate-original
                            {file? : Path to the original HTML file (default: resources/views/index.html)}';

    /**
     * @var string
     */
    protected $description = 'Publish the original landing design (resources/views/index.html) into the new file-based storage and snapshot';

    public function __construct(private readonly LandingStorageService $storage)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $source = $this->argument('file') ?? resource_path('views/index.html');

        if (! is_file($source)) {
            $this->error("Original file not found at {$source}.");

            return Command::FAILURE;
        }

        $document = (string) file_get_contents($source);

        $css = $this->extractBlock($document, 'style');
        $body = $this->extractBody($document);

        if ($css === '' || $body === '') {
            $this->error('Could not extract the style or body blocks from the original file.');

            return Command::FAILURE;
        }

        $published = "<style>{$css}</style>\n".$this->localizeAssetUrls($body)."\n";

        foreach ($this->missingLocalAssets($published) as $assetPath) {
            $this->warn("Missing local asset referenced: {$assetPath}");
        }

        $this->storage->saveDraft([], $published);

        $result = $this->storage->publish();

        $this->writeTemplateSeed($css, $body);

        $this->info('Landing published from the original design.');
        $this->table(
            ['Status', 'Publicado em', 'Snapshot'],
            [[
                $this->storage->status(),
                $result['publishedAt'],
                (string) config('landing.snapshot_path'),
            ]],
        );

        return Command::SUCCESS;
    }

    private function extractBlock(string $html, string $tag): string
    {
        if (preg_match('#<'.$tag.'>(.*)</'.$tag.'>#s', $html, $matches) !== 1) {
            return '';
        }

        return trim($matches[1]);
    }

    private function extractBody(string $html): string
    {
        $body = $this->extractBlock($html, 'body');

        return trim((string) preg_replace('#<script\b[^>]*>.*?</script>#is', '', $body));
    }

    private function localizeAssetUrls(string $html): string
    {
        return (string) preg_replace('#https://pub-[a-z0-9]+\.r2\.dev/#i', '/landing-assets/img/', $html);
    }

    private function writeTemplateSeed(string $css, string $body): void
    {
        $html = $this->localizeAssetUrls($body);

        $html = (string) preg_replace(
            '#(<p class="hero-p">).*?</p>#s',
            '$1[[subtitle]]</p>',
            $html,
        );

        $html = str_replace('>Começar agora<', '>[[ctaText]]<', $html);

        $html = (string) preg_replace('#https://wa\.me/\d+#', '[[whatsappUrl]]', $html);

        $seedPath = resource_path('js/pages/landing-admin/landingTemplate.json');

        file_put_contents($seedPath, json_encode(
            ['html' => $html, 'css' => $css],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        $this->info("Editor template seed written to {$seedPath}");
    }

    private function missingLocalAssets(string $html): array
    {
        preg_match_all('#(?:src|href)="/landing-assets/([^"]+)"#', $html, $matches);

        $missing = [];

        foreach (array_unique($matches[1]) as $relativePath) {
            if (! is_file(resource_path('views/landing-assets/'.urldecode($relativePath)))) {
                $missing[] = $relativePath;
            }
        }

        return $missing;
    }
}