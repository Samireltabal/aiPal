<?php

declare(strict_types=1);

namespace App\Ai\Services;

use App\Ai\Tools\AiTool;
use App\Models\User;
use App\Models\UserToolSetting;
use Illuminate\Support\Collection;
use Laravel\Ai\Contracts\Tool;

class ToolRegistry
{
    /**
     * Discover all concrete AiTool subclasses in app/Ai/Tools/.
     *
     * @return class-string<AiTool>[]
     */
    public function discover(): array
    {
        $tools = [];

        foreach (glob(app_path('Ai/Tools/*.php')) as $file) {
            /** @var class-string $class */
            $class = 'App\\Ai\\Tools\\'.basename($file, '.php');

            if (
                is_subclass_of($class, AiTool::class) &&
                ! (new \ReflectionClass($class))->isAbstract()
            ) {
                $tools[] = $class;
            }
        }

        return $tools;
    }

    /**
     * Build tool instances for a user, filtered by their enabled settings.
     *
     * @return Tool[]
     */
    public function forUser(User $user): array
    {
        $disabled = UserToolSetting::disabledToolsFor($user->id);

        return collect($this->discover())
            ->reject(fn (string $class) => in_array($class::toolName(), $disabled, true))
            ->map(fn (string $class) => app()->makeWith($class, ['user' => $user]))
            ->values()
            ->all();
    }

    /**
     * All tools with their enabled state for the given user.
     *
     * @return Collection<int, array{name: string, label: string, category: string, enabled: bool}>
     */
    public function allWithSettings(User $user): Collection
    {
        $disabled = array_flip(UserToolSetting::disabledToolsFor($user->id));

        return collect($this->discover())->map(fn (string $class) => [
            'name' => $class::toolName(),
            'label' => $class::toolLabel(),
            'category' => $class::toolCategory(),
            'enabled' => ! isset($disabled[$class::toolName()]),
        ]);
    }
}
