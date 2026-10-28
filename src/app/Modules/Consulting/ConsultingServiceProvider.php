<?php

namespace App\Modules\Consulting;

use App\Modules\Consulting\Models\ConsultantProfile;
use App\Modules\Consulting\Models\Consultation;
use App\Modules\Consulting\Models\ConsultingClient;
use App\Modules\Consulting\Models\Proposal;
use App\Modules\Consulting\Policies\ConsultantProfilePolicy;
use App\Modules\Consulting\Policies\ConsultationPolicy;
use App\Modules\Consulting\Policies\ConsultingClientPolicy;
use App\Modules\Consulting\Policies\ProposalPolicy;
use App\Modules\Consulting\Services\BookingService;
use App\Modules\Identity\Models\User;
use App\Modules\ModuleServiceProvider;

/**
 * FR6 Consulting Management — consultant profiles and availability (roadmap
 * 3.11), conflict-free booking (3.12), client management (3.13) and
 * proposals (3.14).
 */
class ConsultingServiceProvider extends ModuleServiceProvider
{
    protected string $module = 'Consulting';

    protected function policies(): array
    {
        return [
            ConsultantProfile::class => ConsultantProfilePolicy::class,
            Consultation::class      => ConsultationPolicy::class,
            ConsultingClient::class  => ConsultingClientPolicy::class,
            Proposal::class          => ProposalPolicy::class,
        ];
    }

    protected function morphMap(): array
    {
        return ['consultant_profile' => ConsultantProfile::class, 'consultation' => Consultation::class, 'consulting_client' => ConsultingClient::class];
    }

    protected function relations(): array
    {
        return [
            User::class => [
                'consultantProfile' => fn (User $user) => $user->hasOne(ConsultantProfile::class, 'user_id'),
            ],
        ];
    }

    /** Your consultant profile and hours, the sessions you booked, and (as a consultant) your client records (roadmap 2.6). */
    protected function personalData(): array
    {
        return [
            'consultant_profile' => fn (User $user) => ($p = ConsultantProfile::with('rules', 'daysOff')->where('user_id', $user->id)->first()) ? [
                'headline' => $p->headline, 'bio' => $p->bio, 'expertise' => $p->expertise, 'session_minutes' => $p->session_minutes,
                'price_pkr' => $p->price_pkr, 'timezone' => $p->timezone,
                'weekly_hours' => $p->rules->map->label()->all(),
                'days_off' => $p->daysOff->map(fn ($d) => ['date' => $d->date->toDateString(), 'reason' => $d->reason])->all(),
            ] : null,
            'consulting_sessions_booked' => fn (User $user) => Consultation::with('profile.user')->where('client_id', $user->id)->get()
                ->map(fn (Consultation $c) => [
                    'consultant' => $c->profile?->user?->name, 'starts_at' => $c->starts_at, 'topic' => $c->topic, 'message' => $c->message, 'status' => $c->status,
                ])->all(),
            'consulting_clients' => fn (User $user) => ConsultingClient::with('notes')->whereHas('profile', fn ($p) => $p->where('user_id', $user->id))->get()
                ->map(fn (ConsultingClient $c) => [
                    'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone, 'organization' => $c->organization, 'status' => $c->status,
                    'notes' => $c->notes->pluck('body')->all(),
                ])->all(),
        ];
    }

    /**
     * Upcoming sessions on either side are cancelled (the other person is
     * told). A consultant's profile, hours, client list and notes go; a
     * client's past sessions stay in consultants' calendars as "Deleted user".
     */
    protected function personalDataErasers(): array
    {
        return [function (User $user) {
            $bookings = app(BookingService::class);
            Consultation::with('profile.user', 'client')->where('status', 'confirmed')->where('starts_at', '>', now())
                ->where(fn ($q) => $q->where('client_id', $user->id)->orWhereHas('profile', fn ($p) => $p->where('user_id', $user->id)))
                ->get()->each(fn (Consultation $c) => $bookings->cancel($c, $user, 'The account was deleted.'));

            ConsultingClient::where('user_id', $user->id)->update(['name' => 'Deleted user', 'email' => null, 'phone' => null]);
            Consultation::where('client_id', $user->id)->update(['message' => null]);
            ConsultantProfile::where('user_id', $user->id)->delete();
        }];
    }
}
