<?php

namespace App\Services;

use App\Models\LandingContent;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LandingStorageService
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    private const PROJECT_FILE = 'landing/project.json';

    private const DRAFT_FILE = 'landing/draft.html';

    private const PUBLISHED_FILE = 'landing/published.html';

    private const META_FILE = 'landing/meta.json';

    public function project(): array
    {
        $this->ensureState();

        if (! $this->disk()->exists(self::PROJECT_FILE)) {
            return [];
        }

        $project = json_decode((string) $this->disk()->get(self::PROJECT_FILE), true);

        return is_array($project) ? $project : [];
    }

    public function draftHtml(): string
    {
        $this->ensureState();

        if (! $this->disk()->exists(self::DRAFT_FILE)) {
            return '';
        }

        return (string) $this->disk()->get(self::DRAFT_FILE);
    }

    public function publishedHtml(): ?string
    {
        $this->ensureState();

        if (! $this->disk()->exists(self::PUBLISHED_FILE)) {
            return null;
        }

        $html = trim((string) $this->disk()->get(self::PUBLISHED_FILE));

        return $html === '' ? null : $html;
    }

    public function status(): string
    {
        $this->ensureState();

        return $this->meta()['status'] ?? self::STATUS_DRAFT;
    }

    public function publishedAt(): ?Carbon
    {
        $this->ensureState();

        $publishedAt = $this->meta()['published_at'] ?? null;

        if (! is_string($publishedAt) || $publishedAt === '') {
            return null;
        }

        return Carbon::parse($publishedAt);
    }

    public function saveDraft(array $project, string $html): void
    {
        $this->disk()->put(self::PROJECT_FILE, $this->encodeJson($project));
        $this->disk()->put(self::DRAFT_FILE, $html);
        $this->writeMeta(['status' => self::STATUS_DRAFT]);
    }

    public function publish(): array
    {
        $this->ensureState();

        $html = $this->draftHtml();

        $this->disk()->put(self::PUBLISHED_FILE, $html);

        $publishedAt = Carbon::now();

        $this->writeMeta([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => $publishedAt->toISOString(),
        ]);

        $this->snapshotToRepo($html);

        return ['publishedAt' => $publishedAt->toISOString()];
    }

    private function snapshotToRepo(string $html): void
    {
        if ($html === '') {
            return;
        }

        $path = (string) config('landing.snapshot_path');

        if ($path === '') {
            return;
        }

        $directory = dirname($path);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return;
        }

        if (! is_writable($directory)) {
            return;
        }

        file_put_contents($path, $html);
    }

    private function meta(): array
    {
        if (! $this->disk()->exists(self::META_FILE)) {
            return [];
        }

        $meta = json_decode((string) $this->disk()->get(self::META_FILE), true);

        return is_array($meta) ? $meta : [];
    }

    private function writeMeta(array $overrides): void
    {
        $this->disk()->put(self::META_FILE, $this->encodeJson([...$this->meta(), ...$overrides]));
    }

    private function ensureState(): void
    {
        if ($this->hasState()) {
            return;
        }

        $this->importFromLegacyDatabase();

        if ($this->hasState()) {
            return;
        }

        $this->disk()->put(self::PROJECT_FILE, '[]');
        $this->writeMeta(['status' => self::STATUS_DRAFT]);
    }

    private function hasState(): bool
    {
        return $this->disk()->exists(self::PROJECT_FILE)
            || $this->disk()->exists(self::DRAFT_FILE)
            || $this->disk()->exists(self::PUBLISHED_FILE);
    }

    private function importFromLegacyDatabase(): void
    {
        $content = LandingContent::query()
            ->orderByDesc('updated_at')
            ->first();

        if ($content === null) {
            return;
        }

        if (is_array($content->project) && $content->project !== []) {
            $this->disk()->put(self::PROJECT_FILE, $this->encodeJson($content->project));
        }

        if ($content->draft_html !== '') {
            $this->disk()->put(self::DRAFT_FILE, $content->draft_html);
        }

        if ($content->published_html !== null && $content->published_html !== '') {
            $this->disk()->put(self::PUBLISHED_FILE, $content->published_html);
        }

        $this->writeMeta([
            'status' => $content->status,
            'published_at' => $content->published_at?->toISOString(),
        ]);
    }

    private function encodeJson(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function disk(): Filesystem
    {
        return Storage::disk('local');
    }
}