<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Display preferences from the accessibility toolbar (roadmap 2.7). Stored
 * in a plain (unencrypted) cookie so the page can apply them before it is
 * drawn, and JavaScript can change them without a reload. Read here, they
 * become classes on <html>; the styles live in public/css/aqdesk.css.
 */
final class AccessibilityPreferences
{
    public const COOKIE = 'aq_a11y';

    public const TEXT_SIZES = ['default' => 'Default', 'large' => 'Large', 'larger' => 'Larger'];

    /** switch => [label, hint] */
    public const SWITCHES = [
        'contrast' => ['High contrast', 'Darker text, stronger borders and underlined links'],
        'readable' => ['Readable font', 'Atkinson Hyperlegible, with wider letter spacing — helps with dyslexia and low vision'],
        'spacing'  => ['More line spacing', 'Taller lines and more space between paragraphs'],
        'motion'   => ['Reduce motion', 'No animations or transitions'],
    ];

    private function __construct(public readonly string $textSize, public readonly array $switches) {}

    public static function fromRequest(Request $request): self
    {
        return self::parse((string) $request->cookie(self::COOKIE, ''));
    }

    /** "text:large,contrast,motion" → preferences; anything unknown is ignored. */
    public static function parse(string $value): self
    {
        $size = 'default';
        $switches = [];
        foreach (explode(',', $value) as $part) {
            if (str_starts_with($part, 'text:') && isset(self::TEXT_SIZES[substr($part, 5)])) {
                $size = substr($part, 5);
            } elseif (isset(self::SWITCHES[$part])) {
                $switches[] = $part;
            }
        }

        return new self($size, array_values(array_unique($switches)));
    }

    /** @param  array<string, mixed>  $input  the toolbar form */
    public static function fromInput(array $input): self
    {
        $size = is_string($input['text'] ?? null) && isset(self::TEXT_SIZES[$input['text']]) ? $input['text'] : 'default';

        return new self($size, array_values(array_filter(array_keys(self::SWITCHES), fn ($s) => ! empty($input[$s]))));
    }

    public function has(string $switch): bool
    {
        return in_array($switch, $this->switches, true);
    }

    public function toCookieValue(): string
    {
        return implode(',', array_merge($this->textSize !== 'default' ? ['text:'.$this->textSize] : [], $this->switches));
    }

    /** e.g. "a11y-text-large a11y-contrast" */
    public function classes(): string
    {
        return implode(' ', array_merge(
            $this->textSize !== 'default' ? ['a11y-text-'.$this->textSize] : [],
            array_map(fn ($s) => 'a11y-'.$s, $this->switches),
        ));
    }
}
