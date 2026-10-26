<?php

namespace App\Modules\Platform\Support;

use App\Modules\Identity\Models\User;
use Closure;

/**
 * Data-rights registry (roadmap 2.6). Each module declares, through its
 * service provider, what personal data it holds about a member (exporters)
 * and how to erase it when the member deletes their account (erasers).
 * Governance then builds the export and runs the deletion without knowing
 * any module's tables.
 */
class PersonalData
{
    /** @var array<string, Closure(User): mixed> */
    private array $exporters = [];

    /** @var array<int, Closure(User): void> */
    private array $erasers = [];

    public function export(string $section, Closure $exporter): void
    {
        $this->exporters[$section] = $exporter;
    }

    public function erase(Closure $eraser): void
    {
        $this->erasers[] = $eraser;
    }

    /** @return array<string, mixed> every section, in module order */
    public function collect(User $user): array
    {
        return array_map(fn (Closure $exporter) => $exporter($user), $this->exporters);
    }

    /**
     * Feature modules first, the core last (reverse of boot order), so every
     * module still sees the account as it was — e.g. its email — before
     * Identity anonymises it.
     */
    public function eraseAll(User $user): void
    {
        foreach (array_reverse($this->erasers) as $eraser) {
            $eraser($user);
        }
    }

    /** @return array<int, string> */
    public function sections(): array
    {
        return array_keys($this->exporters);
    }
}
