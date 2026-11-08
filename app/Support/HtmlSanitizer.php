<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Pembersih HTML berbasis whitelist untuk isi template surat.
 *
 * Template dibuat oleh pengguna tim dan dirender di pratinjau (browser) dan
 * PDF, sehingga wajib disanitasi agar tidak menjadi stored XSS: tag di luar
 * daftar dibuang (isinya dipertahankan), atribut on*, href, src dibuang,
 * dan style hanya boleh berisi properti tata letak yang aman.
 */
class HtmlSanitizer
{
    private const TAGS = [
        'p', 'br', 'div', 'span', 'strong', 'b', 'em', 'i', 'u', 'small', 'sup', 'sub',
        'table', 'thead', 'tbody', 'tr', 'td', 'th', 'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'hr', 'blockquote',
    ];

    private const ATTRIBUTES = ['class', 'style', 'colspan', 'rowspan', 'width', 'align'];

    private const STYLE_PROPERTIES = [
        'text-align', 'font-weight', 'font-style', 'font-size', 'text-decoration', 'text-transform', 'letter-spacing',
        'line-height', 'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
        'padding', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
        'width', 'height', 'border', 'border-top', 'border-bottom', 'border-left', 'border-right', 'border-collapse',
        'vertical-align', 'white-space', 'color', 'background', 'background-color',
    ];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');

        if ($root === null) {
            return e(strip_tags($html));
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(DOMNode $node): void
    {
        // Salin daftar anak dulu karena node bisa dihapus/diganti saat iterasi.
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math'], true)) {
                $node->removeChild($child);

                continue;
            }

            self::walk($child);

            if (! in_array($tag, self::TAGS, true)) {
                // Buang tag, pertahankan isinya.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);

                if (! in_array($name, self::ATTRIBUTES, true)) {
                    $child->removeAttribute($attribute->nodeName);
                } elseif ($name === 'style') {
                    $style = self::cleanStyle($attribute->nodeValue ?? '');
                    $style === '' ? $child->removeAttribute('style') : $child->setAttribute('style', $style);
                }
            }
        }
    }

    private static function cleanStyle(string $style): string
    {
        $safe = [];

        foreach (explode(';', $style) as $declaration) {
            [$property, $value] = array_pad(array_map('trim', explode(':', $declaration, 2)), 2, '');
            $property = strtolower($property);

            if ($property === '' || $value === '' || ! in_array($property, self::STYLE_PROPERTIES, true)) {
                continue;
            }

            // Tolak url(), expression(), javascript:, dan escape CSS.
            if (preg_match('/url\s*\(|expression|javascript:|\\\\|@import|behavior/i', $value)) {
                continue;
            }

            $safe[] = $property.':'.$value;
        }

        return implode(';', $safe);
    }
}
