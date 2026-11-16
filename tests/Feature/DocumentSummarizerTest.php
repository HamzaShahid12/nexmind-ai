<?php

namespace Tests\Feature;

use App\Models\UploadedDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\PreparesApp;
use Tests\TestCase;
use ZipArchive;

class DocumentSummarizerTest extends TestCase
{
    use PreparesApp, RefreshDatabase;

    private const SUMMARY = ['summary' => 'A short summary.', 'key_points' => ['One', 'Two'], 'important_findings' => []];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedBase();
        Storage::fake('local');
    }

    private function txt(string $name = 'notes.txt', string $body = "Quarterly revenue grew 12%.\nCosts were flat."): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $body);
    }

    private function docx(string $text): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'dx').'.docx';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('word/document.xml',
            '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p><w:r><w:t>'.$text.'</w:t></w:r></w:p></w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'report.docx', null, null, true);
    }

    public function test_txt_upload_is_summarized_charged_and_the_raw_file_is_deleted(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt()])
            ->assertCreated()->assertJsonPath('generation.output.summary', 'A short summary.')->assertJsonPath('credits_balance', 15);

        $this->assertSame([], Storage::disk('local')->allFiles());                       // no raw upload kept
        $this->assertStringContainsString('Quarterly revenue', UploadedDocument::first()->extracted_text);
    }

    public function test_docx_text_is_extracted(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->docx('Hello from Word')])->assertCreated();

        $this->assertStringContainsString('Hello from Word', UploadedDocument::first()->extracted_text);
    }

    public function test_disallowed_file_types_are_rejected_before_any_ai_call(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        foreach (['malware.exe', 'page.html', 'sheet.xlsx', 'script.php'] as $name) {
            $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => UploadedFile::fake()->create($name, 10)])
                ->assertStatus(422)->assertJsonValidationErrors('file');
        }

        Http::assertNothingSent();
        $this->assertSame(20, $user->fresh()->credits_balance);
    }

    public function test_oversized_files_are_rejected(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf')])
            ->assertStatus(422)->assertJsonValidationErrors('file');
        Http::assertNothingSent();
    }

    public function test_a_file_must_be_provided(): void
    {
        $this->actingAs($this->makeUser())->postJson('/api/v1/ai/document-summary', [])->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_fake_pdf_with_non_pdf_content_is_rejected_cleanly(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => UploadedFile::fake()->createWithContent('fake.pdf', 'not really a pdf')])
            ->assertStatus(422);

        Http::assertNothingSent();
        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('uploaded_documents', 0);
    }

    public function test_empty_text_file_is_rejected(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt('empty.txt', "   \n  ")])->assertStatus(422);
        Http::assertNothingSent();
    }

    public function test_failed_ai_run_keeps_no_document_and_charges_nothing(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi('x', 500);

        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt()])->assertStatus(502);

        $this->assertSame(20, $user->fresh()->credits_balance);
        $this->assertDatabaseCount('uploaded_documents', 0);
    }

    public function test_user_can_ask_a_question_about_their_document(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);
        $id = $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt()])->json('document.id');

        $this->fakeAi('Revenue grew 12%.');
        $this->actingAs($user)->postJson("/api/v1/ai/document-summary/$id/ask", ['question' => 'How did revenue change?'])
            ->assertCreated()->assertJsonPath('answer', 'Revenue grew 12%.')->assertJsonPath('credits_balance', 10);
    }

    public function test_user_cannot_ask_about_or_delete_another_users_document(): void
    {
        $owner = $this->makeUser('user', 20);
        $intruder = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);
        $id = $this->actingAs($owner)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt()])->json('document.id');

        $this->actingAs($intruder)->postJson("/api/v1/ai/document-summary/$id/ask", ['question' => 'What does it say?'])->assertForbidden();
        $this->actingAs($intruder)->deleteJson("/api/v1/documents/$id")->assertForbidden();
        $this->assertSame(20, $intruder->fresh()->credits_balance);
    }

    public function test_document_text_is_never_returned_by_the_api(): void
    {
        $user = $this->makeUser('user', 20);
        $this->fakeAi(self::SUMMARY);
        $this->actingAs($user)->postJson('/api/v1/ai/document-summary', ['file' => $this->txt()])->assertCreated();

        $this->actingAs($user)->getJson('/api/v1/documents')->assertOk()->assertJsonMissingPath('data.0.extracted_text');
    }

    public function test_expired_documents_are_purged(): void
    {
        $user = $this->makeUser();
        $user->documents()->create(['original_name' => 'old.txt', 'mime' => 'text/plain', 'size' => 1, 'extracted_text' => 'x', 'char_count' => 1, 'expires_at' => now()->subDay()]);
        $user->documents()->create(['original_name' => 'new.txt', 'mime' => 'text/plain', 'size' => 1, 'extracted_text' => 'x', 'char_count' => 1, 'expires_at' => now()->addDay()]);

        $this->artisan('documents:purge')->assertSuccessful();

        $this->assertDatabaseCount('uploaded_documents', 1);
    }
}
