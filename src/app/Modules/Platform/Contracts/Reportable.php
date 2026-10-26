<?php

namespace App\Modules\Platform\Contracts;

use App\Modules\Identity\Models\User;

/**
 * Content anyone can report to moderators (roadmap 2.5). Each module's
 * models describe themselves and know how to remove themselves properly
 * (through their own service), so Governance needs no knowledge of them.
 */
interface Reportable
{
    /** e.g. "Question “How do I…”" — also kept in the moderation log after removal. */
    public function reportLabel(): string;

    public function reportUrl(): string;

    /** A short plain-text extract shown to the moderator. */
    public function reportExcerpt(): string;

    /** Who wrote it (told if it is removed); null if unknown. */
    public function reportAuthor(): ?User;

    /** Remove it the way the owning module removes it (clean-up, logging). */
    public function removeAsModerator(User $moderator): void;
}
