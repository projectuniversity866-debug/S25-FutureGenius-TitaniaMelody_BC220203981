<x-mail::message>
{!! $html !!}

<x-mail::subcopy>
You receive this because you subscribed to the AQDesk newsletter.
@if ($unsubscribeUrl)
[Unsubscribe with one click]({{ $unsubscribeUrl }})
@else
This is a test send — subscribers get an unsubscribe link here.
@endif
</x-mail::subcopy>
</x-mail::message>
