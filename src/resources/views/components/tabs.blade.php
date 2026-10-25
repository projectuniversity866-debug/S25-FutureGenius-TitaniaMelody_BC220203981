{{-- Tab-style navigation between views of the same list.

     <x-tabs label="Sort questions" :items="[
         ['label' => 'Newest', 'url' => …, 'active' => true, 'count' => 12],
         …
     ]" />

     Each tab is a link that reloads the list, so this is navigation with
     aria-current, not an ARIA tablist (which promises in-page panels). --}}
@props(['label', 'items'])

<nav aria-label="{{ $label }}" {{ $attributes->class('aq-tabs') }}>
    <ul>
        @foreach ($items as $item)
            <li>
                <a href="{{ $item['url'] }}" @class(['is-active' => $item['active'] ?? false])
                   @if ($item['active'] ?? false) aria-current="page" @endif>
                    {{ $item['label'] }}
                    @isset($item['count'])
                        <span class="aq-tabs-count">{{ number_format($item['count']) }}</span>
                    @endisset
                </a>
            </li>
        @endforeach
    </ul>
</nav>
