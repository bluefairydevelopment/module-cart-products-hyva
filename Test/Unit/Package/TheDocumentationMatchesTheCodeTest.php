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
        $path = 'Stores → Configuration → ' . ucfirst((string) $section->tab) . ' → ' . (string) $section->label;

        self::assertStringContainsString($path, self::read('README.md'));
    }

    public function testConfigurationLocationsUseTheHouseShorthand(): void
    {
        $checked = 0;
        foreach (['README.md', 'FEATURES.md', 'CHANGELOG.md', 'docs/MARKETING.txt'] as $file) {
            if (!is_file(self::root() . '/' . $file)) {
                continue;
            }
            foreach (explode("\n", self::read($file)) as $number => $line) {
                $checked++;
                $where = $file . ':' . ($number + 1) . ': ' . $line;
                // A configuration location is always written Stores → Configuration → <tab> → <section>.
                self::assertDoesNotMatchRegularExpression('/Stores\s*>\s*Configuration/', $line, $where);
                // The long form is the Role Resources tree path, and only appears when it says so.
                if (preg_match('/Stores\s*(?:>|→)\s*Settings\s*(?:>|→)\s*Configuration/', $line) === 1) {
                    self::assertMatchesRegularExpression('/Role Resources|role-resource/i', $line, $where);
                }
            }
        }
        self::assertGreaterThan(50, $checked);
    }

    public function testTheShorthandCheckRejectsTheLongAndAngleBracketForms(): void
    {
        self::assertSame(1, preg_match('/Stores\s*>\s*Configuration/', 'Navigate to Stores > Configuration > Sales'));
        self::assertSame(0, preg_match('/Stores\s*>\s*Configuration/', 'Navigate to Stores → Configuration → Sales'));
        self::assertSame(1, preg_match('/Stores\s*(?:>|→)\s*Settings\s*(?:>|→)\s*Configuration/', 'Open Stores → Settings → Configuration'));
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
            'narration' => '/\\b(?:measured on|measurement|tried first|this ticket|lesson|observed|learned)\\b|\\bused to (?:carry|install|find|be|read)\\b/i',
            'person or session' => '/Jennifer|\bthe parent(?:\'s)? (?:decision|session)\b/',
        ];
        if (!$strict) {
            // Existing test files may use rig and Luma vocabulary; the wider patterns cover the rest.
            unset($markers['rig or certification'], $markers['test site name'], $markers['narration']);
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
        self::assertCount(2, self::historyHits('MEASURED on a box.'));
        self::assertCount(1, self::historyHits('the luma site'));
        self::assertCount(1, self::historyHits('Measured on the local store.'));
        self::assertCount(1, self::historyHits('What was tried first, and the lesson.'));
        self::assertCount(1, self::historyHits('The log line used to carry the message.'));
        self::assertCount(1, self::historyHits('the attribute this ticket exists for'));
        self::assertSame([], self::historyHits('Luma Cart and luma-cart-export-button.png'));
        self::assertSame([], self::historyHits('A clean sentence about a cart export.'));
    }

    public function testTheHyvaThemeClaimIsScopedToTheVersionItWasCheckedAgainst(): void
    {
        // Checked by reading the layout and cart form template of hyva-themes/magento2-default-theme 1.5.2.
        self::assertStringNotContainsString('validated against', self::read('view/frontend/layout/hyva_checkout_cart_index.xml'));
        foreach (['README.md', 'FEATURES.md'] as $document) {
            self::assertStringNotContainsString('order-actions row', self::read($document), $document);
        }
        foreach (['README.md', 'FEATURES.md'] as $document) {
            self::assertStringContainsString('1.5.2', self::read($document), $document);
            self::assertStringContainsString('a custom theme may place it differently', self::read($document), $document);
        }
    }

    public function testTheQuotedLoadingLabelIsTheTemplatesString(): void
    {
        $template = self::read('view/frontend/templates/cart/export-button.phtml');
        self::assertStringContainsString("__('Exporting…')", $template);
        foreach (['README.md', 'FEATURES.md'] as $document) {
            self::assertStringNotContainsString('Exporting...', self::read($document), $document);
            self::assertStringContainsString('"Exporting…"', self::read($document), $document);
        }
    }

    public function testNoDocumentClaimsFullPageCacheCompatibilityTheLayoutDoesNotShow(): void
    {
        self::assertStringContainsString('cacheable="false"', self::read('view/frontend/layout/hyva_checkout_cart_index.xml'));
        foreach (['README.md', 'FEATURES.md', 'CHANGELOG.md'] as $document) {
            self::assertDoesNotMatchRegularExpression('/full-page cach/i', self::read($document), $document);
        }
    }

    public function testTheButtonPositionTextStatesBothTheMarkupOrderAndTheToolbarDirection(): void
    {
        foreach (['README.md', 'FEATURES.md'] as $document) {
            self::assertStringContainsString('row-reverse', self::read($document), $document);
            self::assertStringContainsString('to the left of', self::read($document), $document);
        }
    }

    public function testTheReadmeSaysTheThemeCssMustBeRebuiltAsModuleCssDoes(): void
    {
        self::assertStringContainsString('npm run build', self::read('view/frontend/tailwind/module.css'));
        self::assertStringContainsString('npm run build', self::read('README.md'));
    }

    public function testThePhpStatementIsTheManifestsRequirement(): void
    {
        $composer = json_decode(self::read('composer.json'), true);
        self::assertStringContainsString('`php ' . $composer['require']['php'] . '`', self::read('README.md'));
        self::assertStringNotContainsString('8.3–8.5', self::read('README.md'));
    }

    public function testNoShippedDocumentCarriesAPriceFigure(): void
    {
        $files = array_merge(['README.md', 'FEATURES.md', 'CHANGELOG.md', 'LICENSE.txt'], []);
        foreach ($files as $file) {
            if (!is_file(dirname(__DIR__, 3) . '/' . $file)) {
                continue;
            }
            self::assertSame(0, preg_match('/[$€£]\s?\d|\d\s?(?:USD|EUR|GBP)\b/', (string) file_get_contents(dirname(__DIR__, 3) . '/' . $file)), "$file carries a price figure");
        }
        self::assertSame(1, preg_match('/[$€£]\s?\d|\d\s?(?:USD|EUR|GBP)\b/', 'Only $299 per year'), 'the price check must fire on a specimen');
        self::assertSame(1, preg_match('/[$€£]\s?\d|\d\s?(?:USD|EUR|GBP)\b/', 'costs 499 USD'), 'the price check must fire on a specimen');
        self::assertSame(0, preg_match('/[$€£]\s?\d|\d\s?(?:USD|EUR|GBP)\b/', 'The Price column is the unit price'), 'the price check must not fire on the Price column');
    }

    /** @return list<string> */
    private static function changelogNarrationHits(string $text): array
    {
        // Assembled from parts so that this file does not match its own patterns.
        $markers = [
            'a correction of earlier documents' => '/\b(?:README|Features Guide|install guide|marketing text|product description|MARKETING\.txt)\b[^.\n]{0,80}\b(?:said|listed|showed|named|placed|claimed|described|decoded|opened with|no longer|now (?:state|read))\b/i',
            'a tested-on claim' => '/\bwhat has been tested\b|\btested on\b|\bmeasured on\b/i',
            'an unproven framework release mapping' => '/from framework 103\.0\.10 up|103\.0\.10/',
            'an unproven cache claim' => '/FPC-safe/',
            'a prediction of Composer resolution' => '/falls back to (?:the )?1\.0\.3/',
            'comment-edit narration' => '/Comments in the shipped [^.\n]*were reworded/',
            'a wrongly named transport' => '/\bREST controller\b/',
        ];
        $hits = [];
        foreach ($markers as $name => $pattern) {
            if (preg_match($pattern, $text, $match) === 1) {
                $hits[] = $name . ': ' . $match[0];
            }
        }

        return $hits;
    }

    public function testTheChangelogIsCustomerFacingOnly(): void
    {
        $text = self::read('CHANGELOG.md');
        self::assertGreaterThan(5, preg_match_all('/^## \d/m', $text), 'the changelog has too few releases to mean anything');
        self::assertSame([], self::changelogNarrationHits($text));
    }

    public function testTheChangelogCheckFiresOnASpecimenOfEachKind(): void
    {
        self::assertCount(1, self::changelogNarrationHits('The Features Guide placed the button below the table.'));
        self::assertCount(1, self::changelogNarrationHits('The README listed it as "Quantity".'));
        self::assertCount(1, self::changelogNarrationHits('and what has been tested on each.'));
        self::assertCount(1, self::changelogNarrationHits('admits later releases from framework 103.0.10 up'));
        self::assertCount(1, self::changelogNarrationHits('with Alpine.js component and FPC-safe runtime form key'));
        self::assertCount(1, self::changelogNarrationHits('An unpinned require on 2.4.8 falls back to 1.0.3.'));
        self::assertCount(1, self::changelogNarrationHits('Comments in the shipped code were reworded; no behaviour changed.'));
        self::assertCount(1, self::changelogNarrationHits('duplicated in the REST controller'));
        self::assertSame([], self::changelogNarrationHits('The development test directory no longer ships in the published package.'));
    }

    public function testNoDocumentPredictsWhatComposerDoesForAnUnpinnedRequire(): void
    {
        foreach (['README.md', 'CHANGELOG.md'] as $file) {
            self::assertDoesNotMatchRegularExpression('/falls? back to (?:the )?1\.0\.3/', self::read($file), $file);
        }
        self::assertSame(1, preg_match('/falls? back to (?:the )?1\.0\.3/', 'a plain require on 2.4.8 falls back to 1.0.3'), 'the check must fire on a specimen');
        self::assertStringContainsString('Do not install', self::read('README.md'));
    }

    public function testNoShippedFileCallsAStorefrontControllerARestEndpoint(): void
    {
        $root = self::root();
        self::assertFileDoesNotExist($root . '/etc/webapi.xml', 'a webapi.xml makes REST wording true; revisit this test');
        $examined = 0;
        $hits = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if (preg_match('#^(?:Test|vendor|docs-build)/|(?:^|/)(?:node_modules|\.[^/]+)(?:/|$)#', $relative) === 1
                || !in_array($file->getExtension(), ['php', 'phtml', 'xml', 'js', 'css', 'md', 'txt'], true)
            ) {
                continue;
            }
            $examined++;
            if (preg_match('/\bREST\b/', (string) file_get_contents($file->getPathname())) === 1) {
                $hits[] = $relative;
            }
        }
        self::assertGreaterThan(5, $examined);
        self::assertSame([], $hits);
        self::assertSame(1, preg_match('/\bREST\b/', 'Shared by the REST controller'), 'the check must fire on a specimen');
    }

    public function testTheTailwindCommentsMakeOnlyClaimsTheCodeOrTheThemeShows(): void
    {
        $css = self::read('view/frontend/tailwind/module.css');
        $observer = self::read('Observer/RegisterModuleForHyvaConfig.php');
        $events = self::read('etc/frontend/events.xml');
        self::assertStringNotContainsString('hyva-sources', $css);
        self::assertStringNotContainsString('silently', $css . $events);
        self::assertStringNotContainsString('FreeGiftHyva', $observer);
        self::assertStringNotContainsString('safelist" comment', $observer);
        self::assertStringContainsString('hyva_config_generate_before', $events);
        self::assertStringContainsString("'extensions'", $observer);
    }
}
