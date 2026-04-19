<?php

declare(strict_types=1);

namespace Tests\Feature\ToolRegistry;

use App\Ai\Services\ToolRegistry;
use App\Ai\Tools\AiTool;
use App\Ai\Tools\CreateNote;
use App\Ai\Tools\CreateReminder;
use App\Ai\Tools\CreateTask;
use App\Ai\Tools\ListTasks;
use App\Ai\Tools\SearchKnowledgeBase;
use App\Ai\Tools\SearchNotes;
use App\Models\User;
use App\Models\UserToolSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToolRegistryTest extends TestCase
{
    use RefreshDatabase;

    private ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = app(ToolRegistry::class);
    }

    public function test_discovers_all_concrete_ai_tools(): void
    {
        $discovered = $this->registry->discover();

        $this->assertContains(SearchKnowledgeBase::class, $discovered);
        $this->assertContains(CreateNote::class, $discovered);
        $this->assertContains(SearchNotes::class, $discovered);
        $this->assertContains(CreateReminder::class, $discovered);
        $this->assertContains(CreateTask::class, $discovered);
        $this->assertContains(ListTasks::class, $discovered);
    }

    public function test_does_not_discover_abstract_base_class(): void
    {
        $discovered = $this->registry->discover();

        $this->assertNotContains(AiTool::class, $discovered);
    }

    public function test_all_discovered_tools_are_subclasses_of_ai_tool(): void
    {
        foreach ($this->registry->discover() as $class) {
            $this->assertTrue(
                is_subclass_of($class, AiTool::class),
                "{$class} is not a subclass of AiTool"
            );
        }
    }

    public function test_for_user_returns_all_tools_when_none_disabled(): void
    {
        $user = User::factory()->create();

        $tools = $this->registry->forUser($user);

        $this->assertCount(count($this->registry->discover()), $tools);
    }

    public function test_for_user_excludes_disabled_tools(): void
    {
        $user = User::factory()->create();

        UserToolSetting::create([
            'user_id' => $user->id,
            'tool' => 'create_note',
            'enabled' => false,
        ]);

        $tools = $this->registry->forUser($user);
        $toolClasses = array_map(fn ($t) => get_class($t), $tools);

        $this->assertNotContains(CreateNote::class, $toolClasses);
        $this->assertContains(SearchKnowledgeBase::class, $toolClasses);
    }

    public function test_all_with_settings_returns_enabled_true_by_default(): void
    {
        $user = User::factory()->create();

        $settings = $this->registry->allWithSettings($user);

        foreach ($settings as $tool) {
            $this->assertTrue($tool['enabled'], "{$tool['label']} should be enabled by default");
        }
    }

    public function test_all_with_settings_reflects_disabled_setting(): void
    {
        $user = User::factory()->create();

        UserToolSetting::create([
            'user_id' => $user->id,
            'tool' => 'list_tasks',
            'enabled' => false,
        ]);

        $settings = $this->registry->allWithSettings($user);
        $listTasksSetting = $settings->firstWhere('name', 'list_tasks');

        $this->assertFalse($listTasksSetting['enabled']);
    }

    public function test_each_tool_has_required_metadata(): void
    {
        foreach ($this->registry->discover() as $class) {
            $this->assertNotEmpty($class::toolName(), "{$class}::toolName() must not be empty");
            $this->assertNotEmpty($class::toolLabel(), "{$class}::toolLabel() must not be empty");
            $this->assertNotEmpty($class::toolCategory(), "{$class}::toolCategory() must not be empty");
        }
    }
}
