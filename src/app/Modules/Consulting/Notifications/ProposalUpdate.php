<?php

namespace App\Modules\Consulting\Notifications;

use App\Modules\Consulting\Models\Proposal;
use App\Modules\Platform\Notifications\AppNotification;

/**
 * A proposal moved on (roadmap 3.14): sent or completed (to the client),
 * accepted or rejected (to the consultant). Keeps the wording, not the
 * model, like ConsultationUpdate.
 */
class ProposalUpdate extends AppNotification
{
    private string $proposalTitle;

    private string $amount;

    private ?string $note;

    private string $link;

    /** @param  string  $what  sent | accepted | rejected | completed */
    public function __construct(Proposal $proposal, private string $what, private string $otherName)
    {
        $this->proposalTitle = $proposal->title;
        $this->amount = $proposal->amountLabel();
        $this->note = $proposal->response_note;
        $this->link = route('consulting.proposals.show', $proposal);
    }

    protected function title(): string
    {
        return [
            'sent'      => 'New proposal from '.$this->otherName,
            'accepted'  => $this->otherName.' accepted your proposal',
            'rejected'  => $this->otherName.' declined your proposal',
            'completed' => 'Proposal completed: '.$this->proposalTitle,
        ][$this->what];
    }

    protected function body(): string
    {
        return match ($this->what) {
            'sent'      => '“'.$this->proposalTitle.'” ('.$this->amount.'). Open it to accept or decline.',
            'accepted', 'rejected' => '“'.$this->proposalTitle.'”'.($this->note ? ' — '.$this->note : '.'),
            'completed' => $this->otherName.' marked the work in “'.$this->proposalTitle.'” as completed.',
        };
    }

    protected function url(): string
    {
        return $this->link;
    }

    protected function icon(): string
    {
        return Proposal::STATUSES[$this->what][1];
    }

    protected function category(): string
    {
        return 'consulting';
    }
}
