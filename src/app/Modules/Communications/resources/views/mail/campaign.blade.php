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
@if ($openUrl)
<img src="{{ $openUrl }}" width="1" height="1" alt="">
@endif
</x-mail::message>
