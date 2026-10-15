<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * What the README and the Features Guide tell a merchant must be what the layout and the
 * manifest say: the block names, the container the button is injected into, the Hyvä theme
 * requirement, and the admin path of the core package's settings.
 *
 * Also: no development history in the package, and the licence version equals the newest
 * CHANGELOG heading.
 */
class TheDocumentationMatchesTheCodeTest extends TestCase
{
    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function read(string $relative): string
    {
        $path = self::root() . '/' . $relative;
        self::assertFileExists($path);

        return (string) file_get_contents($path);
    }

    private static function layout(): \SimpleXMLElement
    {
        $xml = simplexml_load_string(self::read('view/frontend/layout/hyva_checkout_cart_index.xml'));
        self::assertInstanceOf(\SimpleXMLElement::class, $xml);

        return $xml;
    }

    public function testTheContainerTheButtonIsInjectedIntoIsTheOneDocumented(): void
    {
        $injected = null;
        foreach (self::layout()->body->referenceBlock as $reference) {
            if (isset($reference->block) && (string) $reference->block['name'] === 'bluefairy.cartproducts.hyva.export') {
                $injected = (string) $reference['name'];
            }
        }
        self::assertSame('checkout.cart.order.actions', $injected, 'the layout no longer injects the button where this test expects');

        foreach (['README.md', 'FEATURES.md'] as $document) {
            $text = self::read($document);
            self::assertStringContainsString("`$injected`", $text, $document);
            // The Luma button sits in checkout.cart.form; the Hyvä button must not be documented as injected there.
            self::assertDoesNotMatchRegularExpression('/injected\*\* into `checkout\.cart\.form`/', $text, $document);
            self::assertStringNotContainsString('within the `checkout.cart.form` container', $text, $document);
        }
    }

    public function testTheDocumentedBlockNamesAreTheLayoutsBlockNames(): void
    {
        $layout = self::read('view/frontend/layout/hyva_checkout_cart_index.xml');
        foreach (['bluefairy.cartproducts.export', 'bluefairy.cartproducts.hyva.export'] as $name) {
            self::assertStringContainsString('name="' . $name . '"', $layout);
            self::assertStringContainsString("`$name`", self::read('README.md'));
            self::assertStringContainsString("`$name`", self::read('FEATURES.md'));
        }
    }

    public function testTheReadmeNamesTheHyvaThemeRequirementTheManifestDeclares(): void
    {
        $composer = json_decode(self::read('composer.json'), true, 8, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('hyva-themes/magento2-theme-module', $composer['require']);
        $constraint = $composer['require']['hyva-themes/magento2-theme-module'];

        self::assertStringContainsString("`hyva-themes/magento2-theme-module` `$constraint`", self::read('README.md'));
    }

    public function testTheConfigurationPathIsTheCorePackagesAdminPath(): void
    {
        $source = getenv('CART_PRODUCTS_SRC') ?: '';
        $core = $source !== '' ? $source : (getenv('MAGENTO_ROOT') ?: '') . '/vendor/bluefairy/module-cart-products';
        self::assertFileExists($core . '/etc/adminhtml/system.xml', 'the core package was not found to compare the admin path with');

        $system = simplexml_load_string((string) file_get_contents($core . '/etc/adminhtml/system.xml'));
        self::assertInstanceOf(\SimpleXMLElement::class, $system);
        $section = $system->system->section;
        $path = 'Stores > Configuration > ' . ucfirst((string) $section->tab) . ' > ' . (string) $section->label;

        self::assertStringContainsString($path, self::read('README.md'));
    }

    public function testTheLicenceVersionIsTheNewestChangelogHeading(): void
    {
        preg_match('/^## \[?(\d+\.\d+\.\d+)/m', self::read('CHANGELOG.md'), $heading);
        preg_match('/^Version (\d+\.\d+\.\d+)$/m', self::read('LICENSE.txt'), $licence);

        self::assertNotEmpty($heading);
        self::assertSame($heading[1], $licence[1] ?? null);
    }

    /** @return list<string> every text file in the package working tree, relative to the root */
    private static function textFiles(): array
    {
        $root = self::root();
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveCallbackFilterIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
                static fn (\SplFileInfo $f): bool => !in_array($f->getFilename(), ['.git', 'node_modules', 'vendor', 'coverage'], true)
            )
        );
        foreach ($iterator as $file) {
            if (in_array($file->getExtension(), ['png', 'pdf', 'jpg', 'gif', 'svg', 'lock', 'cache'], true)
                || $file->getFilename() === 'package-lock.json'
            ) {
                continue;
            }
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }

        return $files;
    }

    /** @return list<string> the kinds of development history the text carries */
    private static function historyHits(string $text, bool $strict = true): array
    {
        // Assembled from parts so that this file does not match its own patterns.
        $markers = [
            'ticket key' => '/\b(?:M2' . 'MAP|WP' . 'MAP|BFD' . 'SH|BFD' . 'PROD)-\d+/',
            'review vocabulary' => '/(?<!read as )\b(?:cure[ds]?|mutation-verified|review round|multi-agent|estate|bin\/rig)\b/',
            'local path' => '#/Users/|/private/tmp|~/Sites#',
            'rig or certification' => '/\\brig\\b|\\b(?:rig|certification) gate\\b|MEASURED/',
            'test site name' => '/(?<![-\\/])\\bluma\\b(?![-.])/',
            'person or session' => '/Jennifer|\bthe parent(?:\'s)? (?:decision|session)\b/',
        ];
        if (!$strict) {
            // Existing test files may use rig and Luma vocabulary; the wider patterns cover the rest.
            unset($markers['rig or certification'], $markers['test site name']);
        }
        $hits = [];
        foreach ($markers as $name => $pattern) {
            if (preg_match($pattern, $text, $match) === 1) {
                $hits[] = $name . ': ' . $match[0];
            }
        }

        return $hits;
    }

    public function testNoFileCarriesDevelopmentHistory(): void
    {
        $examined = 0;
        $hits = [];
        foreach (self::textFiles() as $relative) {
            // The licence is a legal instrument ("does not cure the breach"), not development prose.
            if ($relative === 'LICENSE.txt' || $relative === 'Test/Unit/Package/' . basename(__FILE__)) {
                continue;
            }
            $examined++;
            foreach (self::historyHits((string) file_get_contents(self::root() . '/' . $relative), !str_starts_with($relative, 'Test/')) as $hit) {
                $hits[] = $relative . ' (' . $hit . ')';
            }
        }

        self::assertGreaterThan(10, $examined, 'the examination covered too few files to mean anything');
        self::assertSame([], $hits);
    }

    public function testTheHistoryPatternsFireOnASpecimenOfEachKind(): void
    {
        self::assertCount(4, self::historyHits('See M2' . 'MAP-1, a cure round, /Users/x, Jennifer.'));
        self::assertCount(1, self::historyHits('Run on the rig.'));
        self::assertCount(1, self::historyHits('MEASURED on a box.'));
        self::assertCount(1, self::historyHits('the luma site'));
        self::assertSame([], self::historyHits('Luma Cart and luma-cart-export-button.png'));
        self::assertSame([], self::historyHits('A clean sentence about a cart export.'));
    }
}
