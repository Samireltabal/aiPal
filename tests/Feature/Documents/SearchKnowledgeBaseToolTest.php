<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Ai\Tools\SearchKnowledgeBase;
use App\Models\User;
use App\Services\EmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class SearchKnowledgeBaseToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_no_results_when_user_has_no_documents(): void
    {
        $user = User::factory()->create();

        $mockEmbeddings = $this->mock(EmbeddingService::class);
        $mockEmbeddings->shouldNotReceive('embedText');

        $tool = new SearchKnowledgeBase($user, $mockEmbeddings);
        $result = $tool->handle(new Request(['query' => 'Laravel routing']));

        $this->assertStringContainsString('No relevant documents', (string) $result);
    }

    public function test_tool_description_mentions_knowledge_base(): void
    {
        $user = User::factory()->create();
        $tool = new SearchKnowledgeBase($user, $this->mock(EmbeddingService::class));

        $this->assertStringContainsString('knowledge base', strtolower((string) $tool->description()));
    }
}
