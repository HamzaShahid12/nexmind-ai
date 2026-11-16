<?php

namespace App\Services\Document;

use App\Models\Setting;
use App\Models\UploadedDocument;
use App\Models\User;
use DOMDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

/**
 * Secure document ingestion:
 *  - the upload is stored on the PRIVATE disk under a random name,
 *  - text is extracted server-side (nothing raw is ever sent to the AI provider),
 *  - the stored file is deleted immediately after extraction,
 *  - only the extracted text is kept, until the retention period expires.
 */
class DocumentService
{
    public const ALLOWED = [
        'pdf' => ['application/pdf'],
        'txt' => ['text/plain'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
    ];

    private const W_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public function ingest(User $user, UploadedFile $file): UploadedDocument
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();

        if (! isset(self::ALLOWED[$ext]) || ! in_array($mime, self::ALLOWED[$ext], true)) {
            $this->fail('Only PDF, TXT and DOCX files are allowed.');
        }

        $disk = Storage::disk('local');
        $path = $file->store("documents/{$user->id}", 'local');   // random hashed name, private disk

        try {
            $text = $this->extract($disk->path($path), $ext);
        } finally {
            $disk->delete($path);                                   // never keep the raw upload
        }

        $text = trim(preg_replace("/[ \t]+/", ' ', preg_replace("/\r\n?/", "\n", $text)) ?? '');
        if ($text === '') {
            $this->fail('No readable text was found in this file. Scanned or image-only documents are not supported.');
        }

        $max = (int) config('ai.documents.max_chars');
        $truncated = mb_strlen($text) > $max;
        $retention = (int) Setting::get('documents.retention_days', config('ai.documents.retention_days'));

        return $user->documents()->create([
            'original_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'mime' => $mime,
            'size' => $file->getSize(),
            'extracted_text' => mb_substr($text, 0, $max),
            'char_count' => min(mb_strlen($text), $max),
            'truncated' => $truncated,
            'expires_at' => now()->addDays(max($retention, 1)),
        ]);
    }

    public function purgeExpired(): int
    {
        return UploadedDocument::query()->where('expires_at', '<=', now())->delete();
    }

    private function extract(string $path, string $ext): string
    {
        try {
            return match ($ext) {
                'txt' => $this->extractTxt($path),
                'pdf' => $this->extractPdf($path),
                'docx' => $this->extractDocx($path),
            };
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::warning('Document extraction failed', ['ext' => $ext, 'reason' => class_basename($e)]);
            $this->fail('This file could not be read. It may be corrupted or password-protected.');
        }
    }

    private function extractTxt(string $path): string
    {
        $raw = (string) file_get_contents($path);

        return mb_check_encoding($raw, 'UTF-8') ? $raw : mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1');
    }

    private function extractPdf(string $path): string
    {
        return (new \Smalot\PdfParser\Parser())->parseFile($path)->getText();
    }

    private function extractDocx(string $path): string
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            $this->fail('This DOCX file could not be read.');
        }

        $stat = $zip->statName('word/document.xml');
        if ($stat === false || $stat['size'] > 20 * 1024 * 1024) {      // also guards against zip bombs
            $zip->close();
            $this->fail('This DOCX file could not be read.');
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        $dom = new DOMDocument();
        // LIBXML_NONET blocks network access; entities are never substituted (no LIBXML_NOENT).
        if (! $dom->loadXML((string) $xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            $this->fail('This DOCX file could not be read.');
        }

        $paragraphs = [];
        foreach ($dom->getElementsByTagNameNS(self::W_NS, 'p') as $p) {
            $line = '';
            foreach ($p->getElementsByTagNameNS(self::W_NS, 't') as $t) {
                $line .= $t->textContent;
            }
            $paragraphs[] = $line;
        }

        return implode("\n", $paragraphs);
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['file' => [$message]]);
    }
}
