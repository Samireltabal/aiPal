<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\DocumentIngestionJob;
use App\Models\Document;
use App\Services\DocumentParser;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Documents extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    #[Validate('nullable|file|max:20480')]
    public mixed $uploadFile = null;

    public string $uploadError = '';

    public bool $uploadQueued = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function uploadDocument(): void
    {
        $this->uploadError = '';
        $this->uploadQueued = false;

        $this->validateOnly('uploadFile');

        if (! $this->uploadFile) {
            $this->uploadError = 'Please select a file.';

            return;
        }

        $parser = app(DocumentParser::class);

        if (! $parser->canParse($this->uploadFile)) {
            $ext = $this->uploadFile->getClientOriginalExtension();
            $this->uploadError = "Unsupported file type: .{$ext}. Please upload a text, markdown, or code file.";

            return;
        }

        $user = Auth::user();
        $originalName = $this->uploadFile->getClientOriginalName();
        $extension = $this->uploadFile->getClientOriginalExtension();
        $size = $this->uploadFile->getSize();
        $mime = $this->uploadFile->getMimeType() ?? 'text/plain';

        // Parse content immediately (fast — file reading only)
        $content = $parser->parse($this->uploadFile);

        // Store the parsed text content on disk for the ingestion job
        $fileName = uniqid('doc_', true).'.'.$extension;
        Storage::disk('local')->put("documents/{$user->id}/{$fileName}", $content);

        $document = Document::create([
            'user_id' => $user->id,
            'name' => pathinfo($originalName, PATHINFO_FILENAME),
            'file_name' => $fileName,
            'mime_type' => $mime,
            'size' => $size,
            'status' => 'pending',
        ]);

        DocumentIngestionJob::dispatch($document->id);

        $this->uploadFile = null;
        $this->uploadQueued = true;
    }

    public function delete(int $id): void
    {
        $document = Document::query()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($document === null) {
            return;
        }

        Storage::disk('local')->delete("documents/{$document->user_id}/{$document->file_name}");
        $document->delete();
    }

    public function render(): View
    {
        $query = Auth::user()->documents()->latest();

        if ($this->search !== '') {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        return view('livewire.documents', [
            'documents' => $query->paginate(20),
        ]);
    }
}
