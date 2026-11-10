<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Template;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Hyvä strict CSP rules for every template in this package.
 *
 * 1. An inline script runs only when Hyvä registers it (nonce or hash), so each one must be
 *    followed directly by $hyvaCsp->registerInlineScript().
 * 2. The Alpine CSP build cannot evaluate expressions, so an Alpine attribute may hold only a
 *    property or method name (dotted paths allowed), or "item in list" for x-for. The
 *    x-transition class lists and x-ref names are not expressions and are not checked.
 *
 * @coversNothing
 */
#[CoversNothing]
class StrictCspTemplateTest extends TestCase
{
    private const TEMPLATES = __DIR__ . '/../../../view/frontend/templates';

    private const NAME = '[A-Za-z_$][\w$]*(?:\.[A-Za-z_$][\w$]*)*';

    /**
     * The template with HTML comments removed, so prose such as "x-html does not run <script>
     * tags" is not read as markup.
     *
     * @param string $html
     * @return string
     */
    private static function withoutComments(string $html): string
    {
        return (string) preg_replace('#<!--.*?-->#s', '', $html);
    }

    /**
     * Inline scripts in an HTML fragment that are not followed by a registration call.
     *
     * @param string $html
     * @return string[]
     */
    public static function unregisteredInlineScripts(string $html): array
    {
        preg_match_all(
            '#<script\b([^>]*)>(.*?)</script>(\s*)(<\?php\s+\$hyvaCsp->registerInlineScript\(\)\s*;?\s*\?>)?#is',
            self::withoutComments($html),
            $all,
            PREG_SET_ORDER
        );
        $bad = [];
        foreach ($all as $m) {
            $isExternal = (bool) preg_match('#\bsrc\s*=#i', $m[1]);
            if (!$isExternal && empty($m[4])) {
                $bad[] = substr(trim($m[0]), 0, 120);
            }
        }

        return $bad;
    }

    /**
     * Alpine attributes whose value is an expression rather than a name.
     *
     * @param string $html
     * @return string[]
     */
    public static function alpineExpressions(string $html): array
    {
        preg_match_all(
            '#\s((?:x-[\w:.-]+|@[\w.:-]+|:[\w.-]+))="([^"]*)"#',
            self::withoutComments($html),
            $all,
            PREG_SET_ORDER
        );
        $bad = [];
        foreach ($all as [, $attr, $value]) {
            if (str_starts_with($attr, 'x-transition') || $attr === 'x-ref') {
                continue;
            }
            $value = trim($value);
            $ok = $attr === 'x-for'
                ? (bool) preg_match('#^' . self::NAME . '\s+in\s+' . self::NAME . '$#', $value)
                : ($value === '' || (bool) preg_match('#^' . self::NAME . '$#', $value));
            if (!$ok) {
                $bad[] = $attr . '="' . $value . '"';
            }
        }

        return $bad;
    }

    /**
     * @return array<string, array{string}>
     */
    public static function templates(): array
    {
        $out = [];
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::TEMPLATES));
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'phtml') {
                $out[substr($file->getPathname(), strlen(self::TEMPLATES) + 1)] = [$file->getPathname()];
            }
        }
        ksort($out);

        return $out;
    }

    public function testEveryTemplateIsChecked(): void
    {
        self::assertSame(
            ['cart/export-button.phtml'],
            array_keys(self::templates())
        );
    }

    /**
     * @dataProvider templates
     * @param string $path
     */
    #[DataProvider('templates')]
    public function testEveryInlineScriptIsRegistered(string $path): void
    {
        $html = (string) file_get_contents($path);
        $scripts = preg_match_all('#^\s*<script\b#im', self::withoutComments($html));
        self::assertSame(1, $scripts, 'the check must see the script');
        self::assertSame([], self::unregisteredInlineScripts($html));
        self::assertSame([], self::scriptsMentioningScriptTags($html));
    }

    /**
     * Inline scripts whose body contains "<script". Hyvä's helper puts its nonce on (or hashes
     * from) the LAST "<script" in the output so far, so one in a comment or string inside the
     * script takes the nonce and the real tag is refused.
     *
     * @param string $html
     * @return string[]
     */
    public static function scriptsMentioningScriptTags(string $html): array
    {
        preg_match_all('#^\s*<script\b[^>]*>(.*?)</script>#ims', self::withoutComments($html), $all, PREG_SET_ORDER);
        $bad = [];
        foreach ($all as $m) {
            if (stripos($m[1], '<script') !== false) {
                $bad[] = substr(trim($m[1]), 0, 60);
            }
        }

        return $bad;
    }

    /**
     * @dataProvider templates
     * @param string $path
     */
    #[DataProvider('templates')]
    public function testNoAlpineAttributeHoldsAnExpression(string $path): void
    {
        $html = (string) file_get_contents($path);
        self::assertStringContainsString('x-data=', $html, 'the check must see Alpine markup');
        self::assertSame([], self::alpineExpressions($html));
    }

    public function testTheChecksCatchWhatTheyAreFor(): void
    {
        // Positive controls: each rule must flag a planted violation, and pass its fixed form.
        self::assertCount(1, self::unregisteredInlineScripts('<script>var a = 1;</script><div></div>'));
        self::assertSame([], self::unregisteredInlineScripts(
            "<script>var a = 1;</script>\n<?php \$hyvaCsp->registerInlineScript() ?>"
        ));
        self::assertSame([], self::unregisteredInlineScripts('<script src="/x.js"></script>'));
        self::assertCount(1, self::scriptsMentioningScriptTags("<script>\n// strips <script> tags\n</script>"));
        self::assertSame([], self::scriptsMentioningScriptTags("<script>\n// strips script tags\n</script>"));
        // A comment mentioning <script> must not swallow an unregistered script after it.
        self::assertCount(1, self::unregisteredInlineScripts(
            "<!-- x-html does not run <script> tags --><script>var a;</script>\n<p></p>"
            . "<script>var b;</script>\n<?php \$hyvaCsp->registerInlineScript() ?>"
        ));

        self::assertSame(
            [
                '@mouseenter="canHoverOpen(); openPanel(5)"',
                'x-if="isPanelLoaded(5)"',
                'x-show="!open"',
                ':style="lockWrapperScroll()"',
                ':key="\'p-\' + product.id"',
                'x-for="(column, columnIndex) in panel.columns"',
                'x-data="{ panel: panelCache[5] }"',
            ],
            self::alpineExpressions(
                '<b @mouseenter="canHoverOpen(); openPanel(5)" x-if="isPanelLoaded(5)" x-show="!open"'
                . ' :style="lockWrapperScroll()" :key="\'p-\' + product.id"'
                . ' x-for="(column, columnIndex) in panel.columns" x-data="{ panel: panelCache[5] }"></b>'
            )
        );
        self::assertSame([], self::alpineExpressions(
            '<b @mouseenter="hoverOpen" x-show="open" :href="child.url" x-cloak :key="product.id"'
            . ' x-for="child in column.items" x-data="megaMenuDesktop" x-ref="openSubMenuBtn"'
            . ' x-transition:enter-start="opacity-0 translate-x-8"></b>'
        ));
    }
}
