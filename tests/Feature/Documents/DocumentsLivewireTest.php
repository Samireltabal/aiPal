<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Jobs\DocumentIngestionJob;
use App\Livewire\Documents;
use App\Models\Document;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class DocumentsLivewireTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPersona(): User
    {
        $user = User::factory()->create();
        Persona::create([
            'user_id' => $user->id,
            'assistant_name' => 'Aria',
            'tone' => 'friendly',
            'formality' => 'casual',
            'humor_level' => 'light',
            'backstory' => 'An assistant.',
            'system_prompt' => 'You are Aria.',
        ]);

        return $user;
    }

    public function test_documents_page_loads(): void
    {
        $user = $this->userWithPersona();

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->assertOk();
    }

    public function test_upload_queues_ingestion_job(): void
    {
        Queue::fake();
        Storage::fake('local');

        $user = $this->userWithPersona();

        $file = UploadedFile::fake()->createWithContent('notes.txt', "Line one.\n\nLine two.");

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('uploadFile', $file)
            ->call('uploadDocument')
            ->assertSet('uploadQueued', true)
            ->assertSet('uploadError', '');

        Queue::assertPushed(DocumentIngestionJob::class);
        $this->assertDatabaseHas('documents', ['user_id' => $user->id, 'status' => 'pending']);
    }

    public function test_upload_rejects_unsupported_file_type(): void
    {
        Queue::fake();

        $user = $this->userWithPersona();
        $file = UploadedFile::fake()->create('image.png', 100, 'image/png');

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('uploadFile', $file)
            ->call('uploadDocument')
            ->assertSet('uploadQueued', false);

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('documents', 0);
    }

    public function test_delete_removes_document_belonging_to_user(): void
    {
        Storage::fake('local');

        $user = $this->userWithPersona();
        Storage::disk('local')->put("documents/{$user->id}/doc_test.txt", 'content');

        $document = Document::create([
            'user_id' => $user->id,
            'name' => 'Test',
            'file_name' => 'doc_test.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
        ]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->call('delete', $document->id);

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_delete_cannot_remove_another_users_document(): void
    {
        Storage::fake('local');

        $user = $this->userWithPersona();
        $other = $this->userWithPersona();

        $document = Document::create([
            'user_id' => $other->id,
            'name' => 'Other',
            'file_name' => 'doc_other.txt',
            'mime_type' => 'text/plain',
            'size' => 100,
            'status' => 'ready',
        ]);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->call('delete', $document->id);

        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_search_filters_documents(): void
    {
        $user = $this->userWithPersona();

        Document::create(['user_id' => $user->id, 'name' => 'Laravel Guide', 'file_name' => 'a.txt', 'mime_type' => 'text/plain', 'size' => 10, 'status' => 'ready']);
        Document::create(['user_id' => $user->id, 'name' => 'Vue Handbook', 'file_name' => 'b.txt', 'mime_type' => 'text/plain', 'size' => 10, 'status' => 'ready']);

        Livewire::actingAs($user)
            ->test(Documents::class)
            ->set('search', 'Laravel')
            ->assertSee('Laravel Guide')
            ->assertDontSee('Vue Handbook');
    }
}
