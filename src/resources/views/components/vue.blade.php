{{-- Mount point for a Vue component from resources/js/components.

     <x-vue name="CharacterCounter" :props="['for' => 'body', 'min' => 20]" />

     Props are JSON-encoded and HTML-escaped here, so the browser hands the
     exact JSON to resources/js/app.js. (Blade's @json is meant for <script>
     blocks; inside an attribute its " escapes are not valid JSON.) --}}
@props(['name', 'props' => []])

<div data-vue="{{ $name }}" data-props="{{ json_encode($props) }}" {{ $attributes }}></div>
