<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Rich text written in the admin editor (product descriptions, articles, pages).
 *
 * The text is stored as HTML that went through an allow-list (no script, no inline style, no event handler,
 * safe links and images only). Content written before the editor existed (plain text for products, Markdown
 * for pages) keeps working: it is recognised, rendered safely, and converted the next time it is saved.
 */
class RichText
{
    /** Tags kept as they are. */
    private const ALLOWED = [
        'p', 'br', 'h2', 'h3', 'h4', 'strong', 'em', 'u', 's', 'sub', 'sup', 'ul', 'ol', 'li', 'blockquote', 'a', 'img', 'hr',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'code', 'pre', 'mark',
    ];

    /** Tags rewritten to another one. */
    private const RENAMED = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4'];

    /** Tags removed together with everything inside. */
    private const DROPPED = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta', 'noscript', 'template', 'head', 'title', 'audio', 'video', 'canvas'];

    /** Tags that can carry a text alignment. */
    private const ALIGNABLE = ['p', 'h2', 'h3', 'h4', 'li', 'blockquote', 'td', 'th'];

    /** Block tags: a div containing one of them is unwrapped instead of becoming a paragraph. */
    private const BLOCKS = ['p', 'h2', 'h3', 'h4', 'ul', 'ol', 'blockquote', 'table', 'pre', 'hr', 'div', 'h1', 'h5', 'h6'];

    private const ALIGNMENTS = ['left', 'center', 'right', 'justify'];

    /**
     * True when the text contains HTML written by the editor (as opposed to plain text or Markdown).
     */
    public static function isHtml(?string $value): bool
    {
        return $value !== null && preg_match('/<\/?(p|h[1-6]|ul|ol|li|br|strong|em|b|i|u|s|a|img|blockquote|table|tr|td|div|span|hr)\b[^>]*>/i', $value) === 1;
    }

    /**
     * What is saved in the database: editor HTML is cleaned, anything else (plain text, Markdown) is kept as typed.
     */
    public static function storable(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        if (! self::isHtml($value)) {
            return $value;
        }

        $clean = self::sanitize($value);

        return self::plain($clean) === '' && ! str_contains($clean, '<img') ? null : $clean;
    }

    /**
     * Safe HTML ready to print, whatever the way the text was written.
     */
    public static function render(?string $value, bool $markdown = false): HtmlString
    {
        return new HtmlString(self::toHtml($value, $markdown));
    }

    /**
     * The HTML shown in the editor: legacy plain text or Markdown is converted so it can be edited visually.
     */
    public static function toHtml(?string $value, bool $markdown = false): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (self::isHtml($value)) {
            return self::sanitize($value);
        }

        if ($markdown) {
            return self::sanitize(Str::markdown($value, ['html_input' => 'strip', 'allow_unsafe_links' => false]));
        }

        $paragraphs = preg_split('/\R{2,}/', trim($value)) ?: [];

        return implode('', array_map(fn (string $paragraph): string => '<p>'.nl2br(e(trim($paragraph)), false).'</p>', $paragraphs));
    }

    /**
     * Text without any formatting, for search, meta tags, spreadsheets and the chat assistant.
     */
    public static function plain(?string $value): string
    {
        if ($value === null || trim($value) === '') {
            return '';
        }

        if (! self::isHtml($value)) {
            return trim($value);
        }

        $text = preg_replace('/<\/(p|h[1-6]|li|blockquote|tr|ul|ol|table)>|<br\s*\/?>/i', "\n", $value) ?? $value;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t\x{00A0}]+/u', ' ', $text) ?? $text;

        return trim(preg_replace('/\n\s*\n+/', "\n\n", $text) ?? $text);
    }

    /**
     * Cleans HTML with an allow-list: unknown tags are unwrapped, dangerous ones removed with their content.
     */
    public static function sanitize(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $document->getElementsByTagName('body')->item(0);

        if (! $body) {
            return '';
        }

        self::clean($body);

        $output = '';

        foreach ($body->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }

        return trim($output);
    }

    private static function clean(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                if ($node->nodeType !== XML_TEXT_NODE && $node->nodeType !== XML_CDATA_SECTION_NODE) {
                    $parent->removeChild($node);
                }

                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROPPED, true)) {
                $parent->removeChild($node);

                continue;
            }

            $tag = self::RENAMED[$tag] ?? $tag;

            if ($tag === 'div' || $tag === 'span' || ! in_array($tag, self::ALLOWED, true)) {
                $isParagraph = $tag === 'div' && ! self::hasBlockChild($node);

                if ($isParagraph) {
                    $node = self::rename($node, 'p');
                    $tag = 'p';
                } else {
                    self::clean($node);

                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }

                    $parent->removeChild($node);

                    continue;
                }
            } elseif ($tag !== strtolower($node->tagName)) {
                $node = self::rename($node, $tag);
            }

            self::cleanAttributes($node, $tag);

            if ($tag === 'img' && $node->getAttribute('src') === '') {
                $parent->removeChild($node);

                continue;
            }

            if ($tag === 'a' && $node->getAttribute('href') === '') {
                self::clean($node);

                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }

                $parent->removeChild($node);

                continue;
            }

            self::clean($node);
        }
    }

    private static function hasBlockChild(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::BLOCKS, true)) {
                return true;
            }
        }

        return false;
    }

    private static function rename(DOMElement $element, string $tag): DOMElement
    {
        $replacement = $element->ownerDocument->createElement($tag);

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $replacement->setAttribute($attribute->nodeName, $attribute->nodeValue);
        }

        while ($element->firstChild) {
            $replacement->appendChild($element->firstChild);
        }

        $element->parentNode->replaceChild($replacement, $element);

        return $replacement;
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        $alignment = in_array($tag, self::ALIGNABLE, true) ? self::alignmentOf($element) : null;
        $kept = [];

        if ($tag === 'a') {
            $href = self::safeUrl($element->getAttribute('href'), ['http', 'https', 'mailto', 'tel']);

            if ($href !== null) {
                $kept['href'] = $href;
                $kept['rel'] = 'noopener noreferrer';

                if (preg_match('#^https?://#i', $href)) {
                    $kept['target'] = '_blank';
                }
            }

            if ($element->getAttribute('title') !== '') {
                $kept['title'] = Str::limit($element->getAttribute('title'), 200, '');
            }
        } elseif ($tag === 'img') {
            $src = self::safeUrl($element->getAttribute('src'), ['http', 'https']);

            if ($src !== null) {
                $kept['src'] = $src;
                $kept['alt'] = Str::limit($element->getAttribute('alt'), 200, '');
                $kept['loading'] = 'lazy';
            }

            foreach (['width', 'height'] as $dimension) {
                if (ctype_digit($element->getAttribute($dimension)) && (int) $element->getAttribute($dimension) <= 4000) {
                    $kept[$dimension] = $element->getAttribute($dimension);
                }
            }
        } elseif ($tag === 'td' || $tag === 'th') {
            foreach (['colspan', 'rowspan'] as $span) {
                if (ctype_digit($element->getAttribute($span)) && (int) $element->getAttribute($span) <= 20) {
                    $kept[$span] = $element->getAttribute($span);
                }
            }
        }

        if ($alignment !== null) {
            $kept['class'] = 'ta-'.$alignment;
        }

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $element->removeAttribute($attribute->nodeName);
        }

        foreach ($kept as $name => $value) {
            $element->setAttribute($name, $value);
        }
    }

    private static function alignmentOf(DOMElement $element): ?string
    {
        $candidates = [$element->getAttribute('align')];

        if (preg_match('/text-align\s*:\s*([a-z]+)/i', $element->getAttribute('style'), $matches)) {
            $candidates[] = $matches[1];
        }

        if (preg_match('/\bta-(left|center|right|justify)\b/', $element->getAttribute('class'), $matches)) {
            $candidates[] = $matches[1];
        }

        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim($candidate));

            if ($candidate !== 'left' && in_array($candidate, self::ALIGNMENTS, true)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $schemes
     */
    private static function safeUrl(string $url, array $schemes): ?string
    {
        $url = trim(preg_replace('/[\x00-\x20\x7F]+/', '', $url) ?? '');

        if ($url === '') {
            return null;
        }

        if (preg_match('/^([a-z][a-z0-9+.\-]*):/i', $url, $matches)) {
            return in_array(strtolower($matches[1]), $schemes, true) ? $url : null;
        }

        if (str_starts_with($url, '//')) {
            return null;
        }

        return str_starts_with($url, '/') || str_starts_with($url, '#') ? $url : null;
    }
}
