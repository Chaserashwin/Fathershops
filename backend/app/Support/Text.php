<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Helpers for cleaning the free-text fields found in upstream payloads.
 */
final class Text
{
    private static ?HtmlSanitizer $sanitizer = null;

    /**
     * Plain, single-line text: HTML entities decoded, tags removed,
     * whitespace collapsed.
     */
    public static function plain(mixed $value): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', strip_tags($decoded)));
    }

    /**
     * Rich text that is safe to inject into a page.
     *
     * Product descriptions are third-party HTML. Listing payloads ship it
     * entity-encoded while product payloads ship it raw, so the value is
     * decoded when needed and then passed through an allow-list sanitizer
     * that drops scripts, event handlers, inline styles and non-HTTPS media.
     */
    public static function richHtml(mixed $value): string
    {
        if (! is_string($value) || trim($value) === '') {
            return '';
        }

        if (! str_contains($value, '<') && str_contains($value, '&lt;')) {
            $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return trim(self::sanitizer()->sanitize($value));
    }

    private static function sanitizer(): HtmlSanitizer
    {
        return self::$sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowLinkSchemes(['https', 'http', 'mailto'])
                ->allowMediaSchemes(['https'])
                ->forceAttribute('a', 'rel', 'noopener noreferrer nofollow')
                ->forceAttribute('a', 'target', '_blank')
                ->forceAttribute('img', 'loading', 'lazy')
                ->forceAttribute('img', 'referrerpolicy', 'no-referrer')
                ->withMaxInputLength(500_000)
        );
    }
}
