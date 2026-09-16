<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use PHPUnit\Framework\TestCase;

/**
 * This package is sold. Until this fix it shipped `LICENSE` containing the
 * **GNU GENERAL PUBLIC LICENSE, Version 3** — a copyleft licence that grants a
 * worldwide right to run, study, redistribute and modify, and requires any
 * redistributed derivative to be licensed under the GPL too — while
 * `composer.json` declared `"license": ["proprietary"]`. The two statements
 * contradicted each other and the open one was the one in the merchant's
 * `vendor/` directory. Found in the estate-wide sweep recorded in
 * module-jira-hyva's own version of this test (2026-09-11): "3 declare
 * proprietary, ship the GNU GPL (cart-products x3)" — this package was one of
 * the three, and its two companions (`-graphql`, `-hyva`) share the fix.
 *
 * Nothing here is a legal opinion; this test asserts only that the two
 * declarations agree and that the shipped text is the house instrument.
 *
 * Ported from module-jira-hyva's `TheLicenceAMerchantReceivesTest.php`, the
 * reference cure for this exact defect shape (M2MAP-500 lineage).
 */
class TheLicenceAMerchantReceivesTest extends TestCase
{
    /**
     * Grant language from the licences actually found in this estate. Matching
     * any of these means the shipped text gives the merchant rights the sale does
     * not.
     */
    private const OPEN_SOURCE_GRANTS = [
        'Open Software License',
        'GNU GENERAL PUBLIC LICENSE',
        'Academic Free License',
        'royalty-free, non-exclusive, sublicensable license',
    ];

    private const HOUSE_MARKERS = [
        'BLUE FAIRY DEVELOPMENT LLC',
        'END USER LICENSE AGREEMENT',
        'It is licensed, not sold.',
    ];

    private static function packageRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function licencePath(): string
    {
        $found = glob(self::packageRoot() . '/LICEN[CS]E*') ?: [];
        self::assertCount(
            1,
            $found,
            'expected exactly one licence file in the package root, found: '
            . (implode(', ', array_map('basename', $found)) ?: 'none')
        );

        return $found[0];
    }

    public function testAProprietaryPackageShipsALicenceFile(): void
    {
        $path = self::licencePath();
        self::assertFileExists($path);
        self::assertGreaterThan(
            1000,
            (int) filesize($path),
            'the licence file is too short to be terms — a stub is not a licence'
        );
    }

    public function testTheComposerDeclarationAndTheShippedTextAgree(): void
    {
        $composer = json_decode((string) file_get_contents(self::packageRoot() . '/composer.json'), true);
        self::assertIsArray($composer);

        $declared = (array) ($composer['license'] ?? []);
        self::assertSame(
            ['proprietary'],
            $declared,
            'composer.json no longer declares this package proprietary; if that is deliberate the '
            . 'shipped licence text has to change with it, and this test is where you say so'
        );

        $text = (string) file_get_contents(self::licencePath());
        foreach (self::OPEN_SOURCE_GRANTS as $grant) {
            self::assertStringNotContainsStringIgnoringCase(
                $grant,
                $text,
                sprintf(
                    'the shipped licence contains "%s" while composer.json says proprietary. '
                    . 'A merchant reading vendor/ is told they may redistribute this.',
                    $grant
                )
            );
        }
    }

    public function testTheShippedTextIsTheHouseInstrument(): void
    {
        $text = (string) file_get_contents(self::licencePath());
        foreach (self::HOUSE_MARKERS as $marker) {
            self::assertStringContainsString(
                $marker,
                $text,
                sprintf('the licence does not carry "%s", so it is not the house EULA', $marker)
            );
        }
    }

    public function testTheLicenceNamesThisProductAndNotAnother(): void
    {
        $text = (string) file_get_contents(self::licencePath());

        self::assertStringContainsString(
            'Cart Export for Adobe Commerce',
            $text,
            'the licence does not name this product'
        );

        // Derived from the Jira EULA lineage. A derivation that kept the
        // source product's name would be a licence for a different product,
        // shipped inside this one, and it would read as correct.
        foreach (['Jira Integration', 'Printify', 'Product Grid Export'] as $otherProduct) {
            self::assertStringNotContainsString(
                $otherProduct,
                $text,
                sprintf(
                    'the licence still names %s — the derivation did not finish, and every '
                    . 'substantive clause looks right regardless',
                    $otherProduct
                )
            );
        }

        // The licence is shared across the set (base, GraphQL, Hyvä), so a
        // derivation that dropped a package name would licence two of three.
        foreach (['BlueFairy_CartProducts,', 'BlueFairy_CartProductsGraphQl', 'BlueFairy_CartProductsHyva'] as $pkg) {
            self::assertStringContainsString(
                $pkg,
                $text,
                sprintf('the Software definition does not list %s', $pkg)
            );
        }
    }

    /**
     * The controls. Each assertion above is an assertion about prose, and prose
     * assertions are the ones that quietly cannot fail — so each predicate is
     * run here against a specimen chosen to make it fire.
     */
    public function testEachPredicateFiresOnAPlantedSpecimen(): void
    {
        $osl = 'Open Software License ("OSL") v. 3.0' . "\n"
            . 'Licensor grants You a worldwide, royalty-free, non-exclusive, sublicensable license';

        $hits = 0;
        foreach (self::OPEN_SOURCE_GRANTS as $grant) {
            if (stripos($osl, $grant) !== false) {
                $hits++;
            }
        }
        self::assertSame(
            2,
            $hits,
            'the open-source-grant predicates do not match the exact OSL-3.0 header other packages '
            . 'in this estate shipped — so their absence from the real file proves nothing'
        );

        $gpl = 'GNU GENERAL PUBLIC LICENSE Version 3';
        self::assertTrue(
            stripos($gpl, self::OPEN_SOURCE_GRANTS[1]) !== false,
            'the GPL predicate does not match a GPL header — the exact defect this package shipped'
        );

        // And the house markers must be absent from an open licence, or
        // testTheShippedTextIsTheHouseInstrument would pass on OSL-3.0 too.
        foreach (self::HOUSE_MARKERS as $marker) {
            self::assertStringNotContainsString(
                $marker,
                $osl,
                sprintf('"%s" appears in OSL-3.0 text, so it does not discriminate', $marker)
            );
        }
    }

    /**
     * THE LICENCE CARRIES A HARD-CODED VERSION NUMBER, AND NOTHING WAS COMPARING IT.
     *
     * `LICENSE.txt` line 5 reads "Version X.Y.Z". composer.json has no version key here -
     * Packagist takes the version from the git tag - so that line is the only version string
     * a merchant receives in their vendor/ directory, and it is maintained by hand.
     *
     * The comparand is the newest `## X.Y.Z` heading in CHANGELOG.md, deliberately NOT `git
     * describe` — a tag does not exist yet at the moment this test needs to pass, so a gate
     * keyed on one could never be green before a release.
     */
    public function testTheLicenceVersionMatchesTheNewestChangelogEntry(): void
    {
        $changelog = self::packageRoot() . '/CHANGELOG.md';
        self::assertFileExists($changelog, 'no CHANGELOG.md to compare the licence version against');

        self::assertSame(
            1,
            preg_match('/^## \[?(\d+\.\d+\.\d+)\]?/m', (string) file_get_contents($changelog), $newest),
            'CHANGELOG.md has no `## X.Y.Z` heading, so there is nothing to compare the licence '
            . 'version with and this check would pass over an empty search space'
        );

        $licence = (string) file_get_contents(self::licencePath());
        self::assertSame(
            1,
            preg_match('/^Version (\d+\.\d+\.\d+)\s*$/m', $licence, $stated),
            'LICENSE.txt has no "Version X.Y.Z" line.'
        );

        self::assertSame(
            $newest[1],
            $stated[1],
            sprintf(
                'LICENSE.txt says Version %s and the newest CHANGELOG.md entry is %s. That line is '
                . 'the only version string a merchant receives, because composer.json carries no '
                . 'version key and Packagist reads the git tag.',
                $stated[1],
                $newest[1]
            )
        );
    }
}
