<?php
namespace App\Support;

/** Allow-list HTML sanitizer for admin-authored rich text (XSS defence in depth). */
class Html
{
    private const TAGS = ['p','br','strong','b','em','i','u','s','h2','h3','h4','ul','ol','li','a','blockquote','img','table','thead','tbody','tr','th','td','hr','span','div','figure','figcaption'];
    private const ATTRS = ['href','title','alt','src','width','height','target','rel','class'];

    public static function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') return $html;
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8" ?><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $root = $dom->getElementById('__root');
        if (!$root) return strip_tags($html);
        self::walk($root);
        $out = '';
        foreach ($root->childNodes as $c) $out .= $dom->saveHTML($c);
        return $out;
    }

    private static function walk(\DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof \DOMComment) { $node->removeChild($child); continue; }
            if (!$child instanceof \DOMElement) continue;
            $tag = strtolower($child->tagName);
            if (!in_array($tag, self::TAGS, true)) {
                if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'svg', 'math'], true)) { $node->removeChild($child); continue; }
                self::walk($child);
                while ($child->firstChild) $node->insertBefore($child->firstChild, $child);
                $node->removeChild($child);
                continue;
            }
            foreach (iterator_to_array($child->attributes) as $a) {
                $n = strtolower($a->nodeName); $v = strtolower(trim(preg_replace('/[\s\x00-\x1f]+/', '', $a->nodeValue)));
                if (!in_array($n, self::ATTRS, true) || str_starts_with($v, 'javascript:') || str_starts_with($v, 'data:') || str_starts_with($v, 'vbscript:')) $child->removeAttribute($a->nodeName);
            }
            if ($tag === 'a' && $child->getAttribute('target') === '_blank') $child->setAttribute('rel', 'noopener noreferrer');
            self::walk($child);
        }
    }
}
