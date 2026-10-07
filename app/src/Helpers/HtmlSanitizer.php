<?php

declare(strict_types=1);

namespace Fc\Admin\Helpers;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML sanitizer for rich text that is rendered unescaped on public pages
 * (Version Manager release notes).
 *
 * The input is parsed with DOMDocument and copied node by node into a fresh document, so
 * only allow-listed elements and attributes can ever reach the output: nothing has to be
 * found and removed (the regex strippers elsewhere in this app miss "java\tscript:" URLs,
 * unquoted handlers and markup split by comments). Without ext-dom the text is escaped
 * instead, which is safe but drops the formatting.
 */
final class HtmlSanitizer
{
    /** Element => attributes it may keep. Everything else is unwrapped or dropped. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'hr' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'strong' => [], 'em' => [], 'u' => [], 's' => [], 'sub' => [], 'sup' => [],
        'a' => ['href', 'title', 'target'],
        'ul' => [], 'ol' => ['start'], 'li' => [],
        'blockquote' => [],
        'code' => ['class'], 'pre' => ['class'],
        'table' => [], 'caption' => [], 'thead' => [], 'tbody' => [], 'tfoot' => [], 'tr' => [],
        'th' => ['colspan', 'rowspan', 'scope'], 'td' => ['colspan', 'rowspan'],
    ];

    /** Folded into an allowed element instead of being unwrapped. */
    private const RENAME = [
        'b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's',
        'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4',
        'kbd' => 'code', 'samp' => 'code', 'var' => 'code', 'tt' => 'code',
    ];

    /** Removed together with their content: their text is code, data or chrome, never prose. */
    private const DROP = [
        'script', 'style', 'template', 'noscript', 'iframe', 'frame', 'frameset', 'object', 'embed',
        'applet', 'svg', 'math', 'canvas', 'audio', 'video', 'source', 'track', 'picture', 'img',
        'form', 'input', 'button', 'select', 'option', 'textarea', 'label', 'fieldset', 'datalist',
        'head', 'title', 'meta', 'link', 'base', 'xmp', 'plaintext', 'noembed', 'noframes', 'dialog',
    ];

    /** Unknown wrappers that become a paragraph when they hold only inline content. */
    private const BLOCK_WRAPPERS = [
        'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'figure',
        'figcaption', 'address', 'center', 'details', 'summary', 'dd', 'dt',
    ];

    private const BLOCK_ELEMENTS = [
        'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'blockquote', 'pre', 'table', 'hr',
        'div', 'section', 'article', 'header', 'footer', 'main', 'aside', 'nav', 'figure',
        'address', 'center', 'details', 'dl', 'h1', 'h5', 'h6',
    ];

    private const URL_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /** Deeper than any editor emits; past it only the text survives (and the stack stays small). */
    private const MAX_DEPTH = 32;

    public static function clean(string $html): string
    {
        // A pasted \r would otherwise serialize as a visible "&#13;".
        $html = trim(str_replace(["\r\n", "\r"], "\n", $html));
        if ($html === '') {
            return '';
        }
        if (!class_exists(DOMDocument::class)) {
            return self::plainTextFallback($html);
        }

        $source = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        // The XML declaration makes libxml read the fragment as UTF-8 instead of Latin-1.
        $loaded = $source->loadHTML(
            '<?xml encoding="UTF-8"?><!DOCTYPE html><html><body>' . $html . '</body></html>',
            LIBXML_NONET | LIBXML_COMPACT
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        // The body itself, not a wrapper of ours: a stray </div> in the input would close a wrapper early.
        $body = $source->getElementsByTagName('body')->item(0);
        if (!$loaded || !$body instanceof DOMElement) {
            return self::plainTextFallback($html);
        }

        $out = new DOMDocument('1.0', 'UTF-8');
        $wrapper = $out->createElement('div');
        $out->appendChild($wrapper);
        self::copyChildren($body, $wrapper, $out, 0, false);

        $result = '';
        foreach ($wrapper->childNodes as $child) {
            $result .= $out->saveHTML($child);
        }

        return trim($result);
    }

    /**
     * Readable plain text of sanitized HTML, for table excerpts and search: block ends
     * become spaces so "<li>a</li><li>b</li>" reads "a b", not "ab".
     */
    public static function toPlainText(string $html): string
    {
        $html = preg_replace('#<(br|hr)\b[^>]*>|</?(p|li|ul|ol|h[1-6]|table|td|th|tr|blockquote|pre|caption)\b[^>]*>#i', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private static function copyChildren(DOMNode $from, DOMNode $to, DOMDocument $out, int $depth, bool $inLink): void
    {
        foreach ($from->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE || $child->nodeType === XML_CDATA_SECTION_NODE) {
                $to->appendChild($out->createTextNode($child->nodeValue ?? ''));
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue; // comments, processing instructions
            }

            $tag = strtolower($child->localName ?? $child->nodeName);
            if (in_array($tag, self::DROP, true)) {
                continue;
            }
            if ($depth >= self::MAX_DEPTH) {
                $to->appendChild($out->createTextNode($child->textContent));
                continue;
            }

            $tag = self::RENAME[$tag] ?? $tag;

            if (!isset(self::ALLOWED[$tag])) {
                if (in_array($tag, self::BLOCK_WRAPPERS, true) && !self::hasBlockChild($child)) {
                    $p = $out->createElement('p');
                    self::copyChildren($child, $p, $out, $depth + 1, $inLink);
                    $to->appendChild($p);
                } else {
                    self::copyChildren($child, $to, $out, $depth + 1, $inLink);
                }
                continue;
            }

            if ($tag === 'a') {
                $href = $inLink ? null : self::safeUrl($child->getAttribute('href'));
                if ($href === null) {
                    // A link without a usable target (or nested in another link) keeps its text only.
                    self::copyChildren($child, $to, $out, $depth + 1, $inLink);
                    continue;
                }
            }

            $el = $out->createElement($tag);
            foreach (self::ALLOWED[$tag] as $attr) {
                $value = self::attributeValue($tag, $attr, $child);
                if ($value !== null) {
                    $el->setAttribute($attr, $value);
                }
            }
            if ($tag === 'a' && $el->getAttribute('target') === '_blank') {
                $el->setAttribute('rel', 'noopener noreferrer');
            }

            self::copyChildren($child, $el, $out, $depth + 1, $inLink || $tag === 'a');
            $to->appendChild($el);
        }
    }

    private static function attributeValue(string $tag, string $attr, DOMElement $source): ?string
    {
        if (!$source->hasAttribute($attr)) {
            return null;
        }
        $raw = trim($source->getAttribute($attr));

        switch ($attr) {
            case 'href':
                return self::safeUrl($raw);
            case 'title':
                $title = trim(preg_replace('/\s+/u', ' ', $raw) ?? '');

                return $title === '' ? null : mb_substr($title, 0, 200);
            case 'target':
                return strtolower($raw) === '_blank' ? '_blank' : null;
            case 'class':
                // Only the language-xxx hint a code sample carries, never presentation classes.
                $classes = array_filter(
                    preg_split('/\s+/', strtolower($raw)) ?: [],
                    static fn (string $c): bool => preg_match('/^language-[a-z0-9_+#-]{1,30}$/', $c) === 1
                );

                return $classes === [] ? null : implode(' ', array_slice(array_values($classes), 0, 2));
            case 'colspan':
            case 'rowspan':
            case 'start':
                if (preg_match('/^\d{1,6}$/', $raw) !== 1) {
                    return null;
                }
                $n = (int) $raw;
                $max = $attr === 'start' ? 100000 : 100;

                return $n >= ($attr === 'start' ? 0 : 1) && $n <= $max ? (string) $n : null;
            case 'scope':
                return in_array(strtolower($raw), ['row', 'col', 'rowgroup', 'colgroup'], true) ? strtolower($raw) : null;
        }

        return null;
    }

    /**
     * An href that cannot run script: http(s), mailto, tel, or a scheme-less relative or
     * fragment URL. Whitespace and control characters are removed first, because browsers
     * ignore them too ("java\nscript:" still runs).
     */
    private static function safeUrl(string $url): ?string
    {
        $url = preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '';
        if ($url === '' || mb_strlen($url) > 2048) {
            return null;
        }
        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $m) === 1) {
            return in_array(strtolower($m[1]), self::URL_SCHEMES, true) ? $url : null;
        }
        // No scheme: relative path, query or fragment. A colon before the first / ? # would make it one.
        $firstColon = strpos($url, ':');
        if ($firstColon !== false) {
            $firstDelimiter = strcspn($url, '/?#');
            if ($firstColon < $firstDelimiter) {
                return null;
            }
        }

        return $url;
    }

    private static function hasBlockChild(DOMNode $node): bool
    {
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->localName ?? ''), self::BLOCK_ELEMENTS, true)) {
                return true;
            }
        }

        return false;
    }

    private static function plainTextFallback(string $html): string
    {
        $text = self::toPlainText($html);

        return $text === '' ? '' : '<p>' . StringHelper::escapeHtml($text) . '</p>';
    }
}
