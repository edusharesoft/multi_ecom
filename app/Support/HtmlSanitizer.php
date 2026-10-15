<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Small whitelist HTML sanitizer for rich text written by merchants (pages, blog, text blocks).
 * Keeps basic formatting, links, images, tables and YouTube embeds; drops scripts, styles, event handlers
 * and javascript:/data: URLs.
 */
class HtmlSanitizer
{
    protected const TAGS = [
        'p' => [], 'br' => [], 'hr' => [], 'b' => [], 'strong' => [], 'i' => [], 'em' => [], 'u' => [], 's' => [], 'small' => [], 'sub' => [], 'sup' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'pre' => [], 'code' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'span' => [], 'div' => [],
        'a' => ['href', 'title', 'target', 'rel'], 'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => ['colspan', 'rowspan'], 'td' => ['colspan', 'rowspan'],
        'figure' => [], 'figcaption' => [], 'iframe' => ['src', 'width', 'height', 'allowfullscreen', 'frameborder'],
    ];

    protected const GLOBAL_ATTRS = ['class'];

    public static function clean(?string $html): string
    {
        $html = trim((string) $html);
        if ($html === '') {
            return '';
        }
        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        $root = $doc->getElementById('__root');
        if (! $root) {
            return e(strip_tags($html));
        }
        static::walk($root);
        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    protected static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['script', 'style', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'meta', 'link', 'base', 'svg', 'math'], true)) {
                $node->removeChild($child);

                continue;
            }
            if (! array_key_exists($tag, self::TAGS)) {
                // unwrap unknown tags, keep their text/children
                static::walk($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $name = strtolower($attr->name);
                $allowed = in_array($name, self::TAGS[$tag], true) || in_array($name, self::GLOBAL_ATTRS, true);
                if (! $allowed) {
                    $child->removeAttribute($attr->name);

                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && ! static::safeUrl($attr->value, $tag)) {
                    $child->removeAttribute($attr->name);
                }
            }
            if ($tag === 'iframe' && ! $child->hasAttribute('src')) {
                $node->removeChild($child);

                continue;
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') {
                $child->setAttribute('rel', 'noopener nofollow');
            }
            if ($tag === 'img') {
                $child->setAttribute('loading', 'lazy');
            }
            static::walk($child);
        }
    }

    protected static function safeUrl(string $url, string $tag): bool
    {
        $url = trim(html_entity_decode($url));
        if ($tag === 'iframe') {
            return (bool) preg_match('#^https://(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com|www\.facebook\.com|www\.google\.com/maps)/#i', $url);
        }
        if ($url === '' || str_starts_with($url, '#') || str_starts_with($url, '/')) {
            return ! str_starts_with($url, '//');
        }

        return (bool) preg_match('#^(https?:|mailto:|tel:)#i', $url);
    }
}
