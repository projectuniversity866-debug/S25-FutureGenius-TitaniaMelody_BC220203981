<?php

namespace App\Modules\Knowledge\Services;

use App\Modules\Identity\Models\Role;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Services\UserAdministrationService;
use App\Modules\Knowledge\Models\ExpertVerification;
use App\Modules\Knowledge\Notifications\NewVerificationRequest;
use App\Modules\Knowledge\Notifications\VerificationDecided;
use App\Modules\Platform\Services\ActivityLogger;
use App\Modules\Platform\Services\PrivateFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Expert verification (roadmap 2.1): apply → administrator review →
 * "Verified expert" badge. Approving a Member also makes them an Expert, so
 * they can publish opportunities and Knowledge Hub articles.
 */
class ExpertVerificationService
{
    public function __construct(
        private PrivateFiles $files,
        private ActivityLogger $activity,
        private UserAdministrationService $users,
    ) {}

    public function isVerified(?User $user): bool
    {
        return $user !== null && ExpertVerification::where('user_id', $user->id)->where('status', 'approved')->exists();
    }

    /** The member's most recent request, if any. */
    public function latestFor(User $user): ?ExpertVerification
    {
        return ExpertVerification::where('user_id', $user->id)->latest('id')->first();
    }

    /**
     * @param  array{field: string, credentials: string, evidence_url?: ?string}  $data
     *
     * @throws ValidationException when already verified or a request is waiting
     */
    public function apply(User $user, array $data, ?UploadedFile $document): ExpertVerification
    {
        $latest = $this->latestFor($user);
        if ($latest?->status === 'approved') {
            throw ValidationException::withMessages(['field' => 'You are already a verified expert.']);
        }
        if ($latest?->isPending()) {
            throw ValidationException::withMessages(['field' => 'Your request is already waiting for review.']);
        }

        $request = new ExpertVerification([
            'field'         => $data['field'],
            'credentials'   => $data['credentials'],
            'evidence_url'  => $data['evidence_url'] ?? null,
            'document_path' => $document ? $this->files->store($document, 'verifications') : null,
            'status'        => 'pending',
        ]);
        $request->user()->associate($user)->save();

        User::query()->where('is_active', true)
            ->whereHas('role.permissions', fn ($q) => $q->where('slug', 'experts.verify'))
            ->each(fn (User $reviewer) => $reviewer->notify(new NewVerificationRequest($request)));

        return $request;
    }

    public function approve(ExpertVerification $request, User $reviewer, ?string $note = null): ExpertVerification
    {
        DB::transaction(function () use ($request, $reviewer, $note) {
            $this->decide($request, $reviewer, 'approved', $note);

            // A verified Member becomes an Expert (an administrator keeps their role).
            $user = $request->user;
            $expert = Role::where('slug', Role::EXPERT)->value('id');
            if ($user->isMember() && ! $user->is($reviewer) && $expert) {
                $this->users->changeRole($reviewer, $user, $expert);
            }
        });

        return $request;
    }

    public function reject(ExpertVerification $request, User $reviewer, string $note): ExpertVerification
    {
        return $this->decide($request, $reviewer, 'rejected', $note);
    }

    /** Send the evidence file; authorise before calling. */
    public function downloadDocument(ExpertVerification $request): StreamedResponse
    {
        abort_unless($this->files->exists($request->document_path), 404);

        $name = 'Evidence - '.Str::slug($request->user->name, ' ').'.'.pathinfo($request->document_path, PATHINFO_EXTENSION);

        return $this->files->download($request->document_path, $name);
    }

    private function decide(ExpertVerification $request, User $reviewer, string $status, ?string $note): ExpertVerification
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages(['review_note' => 'This request has already been decided.']);
        }

        $request->forceFill([
            'status' => $status, 'review_note' => $note, 'reviewer_id' => $reviewer->id, 'reviewed_at' => now(),
        ])->save();

        $this->activity->record('expert.'.$status, $request,
            ucfirst($status).' '.$request->user->name.'’s expert verification ('.$request->field.')', ['note' => $note]);

        $request->user->notify(new VerificationDecided($request));

        return $request;
    }
}
