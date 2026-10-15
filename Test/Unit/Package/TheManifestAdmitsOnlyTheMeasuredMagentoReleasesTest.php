<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use Composer\Semver\Semver;
use PHPUnit\Framework\TestCase;

/**
 * THE REQUIREMENTS A
 * MERCHANT READS ARE WHAT COMPOSER ADMITS AND WHAT WAS MEASURED - BOTH, AND THE SAME.
 *
 * What has been measured is Magento 2.4.8-p5 (framework 103.0.8-p5, PHP 8.4) and 2.4.9 (framework
 * 103.0.9, PHP 8.5): this package's unit suite, phpcs and phpstan (it has no integration tests of its own). `^103.0.8` also admitted a 103.1 framework,
 * which nothing pinned. The constraint is now `~103.0.8` (>= 103.0.8, < 103.1.0) and NEVER_RUN carries
 * 103.1.0. `~103.0.8` still admits 103.0.10 and up (a future 2.4.10): capping below it would make
 * Composer refuse a merchant's Magento upgrade to 2.4.10 until this package is released again, which is
 * a product decision and not this test's (the decision is not to cap). So the
 * README says it, and testEachReadmeNamesTheConstraintAndTheUnrunReleasesItStillAdmits pins that it
 * does.
 *
 * Read with Composer's own constraint parser (composer/semver, which every Magento root carries), so
 * `103.0.8-p5` is judged the way `composer require` judges it. No skip: a root without it is an error,
 * not a pass.
 */
class TheManifestAdmitsOnlyTheMeasuredMagentoReleasesTest extends TestCase
{
    /** Releases the suites have been run on: release => its magento/framework version. */
    private const MEASURED = ['2.4.8-p5' => '103.0.8-p5', '2.4.9' => '103.0.9'];

    /** Releases nobody has run this package on, and a framework major that does not exist yet. */
    private const NEVER_RUN = [
        '2.4.7-p5' => '103.0.7-p5',
        '2.4.6' => '103.0.6',
        '2.4.0' => '103.0.0',
        'a 103.1 framework (none released)' => '103.1.0',
        'next major' => '104.0.0',
    ];

    /**
     * Releases the constraint still admits although nobody has run this package on them: a later 2.4
     * release on framework 103.0.10 or above (none released yet). Each must be named in every README's
     * Requirements, beside the constraint itself.
     */
    private const ADMITTED_NOT_RUN = ['2.4.10' => '103.0.10'];

    /** README files (relative to the package root) whose Requirements section a merchant reads. */
    private const READMES = [
        'README.md',
    ];

    private static function packageRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function frameworkConstraint(): string
    {
        $manifest = json_decode(
            (string) file_get_contents(self::packageRoot() . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertIsString(
            $manifest['require']['magento/framework'] ?? null,
            'composer.json requires no magento/framework at all'
        );

        return $manifest['require']['magento/framework'];
    }

    public function testTheFrameworkConstraintAdmitsEveryMeasuredRelease(): void
    {
        $constraint = self::frameworkConstraint();

        foreach (self::MEASURED as $release => $framework) {
            self::assertTrue(
                Semver::satisfies($framework, $constraint),
                sprintf('magento/framework "%s" refuses %s (framework %s), which was measured', $constraint, $release, $framework)
            );
        }
    }

    public function testTheFrameworkConstraintAdmitsNoReleaseThatWasNeverRun(): void
    {
        $constraint = self::frameworkConstraint();

        foreach (self::NEVER_RUN as $release => $framework) {
            self::assertFalse(
                Semver::satisfies($framework, $constraint),
                sprintf(
                    'magento/framework "%s" admits %s (framework %s), which this package has never been run on. '
                    . 'Measure it first, then widen the constraint and the README together',
                    $constraint,
                    $release,
                    $framework
                )
            );
        }
    }

    public function testEachReadmeNamesTheReleasesTheConstraintAdmits(): void
    {
        foreach (self::READMES as $readme) {
            $text = (string) file_get_contents(self::packageRoot() . '/' . $readme);

            self::assertSame(
                1,
                preg_match('/^#{2,3} Requirements\s*$(.*?)(?=^#{1,3} |\z)/ms', $text, $section),
                $readme . ' has no "## Requirements" section to read'
            );

            self::assertStringContainsString(
                '2.4.8 or 2.4.9',
                $section[1],
                $readme . ' Requirements does not say "2.4.8 or 2.4.9", the releases composer.json admits'
            );
            self::assertDoesNotMatchRegularExpression(
                '/2\.4\.x/',
                $section[1],
                $readme . ' Requirements still says "2.4.x", which composer.json does not admit'
            );
        }
    }

    public function testEachReadmeNamesTheConstraintAndTheUnrunReleasesItStillAdmits(): void
    {
        $constraint = self::frameworkConstraint();

        foreach (self::ADMITTED_NOT_RUN as $release => $framework) {
            self::assertTrue(
                Semver::satisfies($framework, $constraint),
                sprintf(
                    'magento/framework "%s" no longer admits %s (framework %s): remove it from ADMITTED_NOT_RUN '
                    . 'and from the README sentence that names it',
                    $constraint,
                    $release,
                    $framework
                )
            );
        }

        foreach (self::READMES as $readme) {
            $text = (string) file_get_contents(self::packageRoot() . '/' . $readme);

            self::assertSame(
                1,
                preg_match('/^#{2,3} Requirements\s*$(.*?)(?=^#{1,3} |\z)/ms', $text, $section),
                $readme . ' has no "## Requirements" section to read'
            );

            self::assertStringContainsString(
                $constraint,
                $section[1],
                $readme . ' Requirements does not quote composer.json\'s magento/framework constraint ' . $constraint
            );

            foreach (array_keys(self::ADMITTED_NOT_RUN) as $release) {
                self::assertMatchesRegularExpression(
                    '/(?<![\d.])' . preg_quote($release, '/') . '(?![\d])/',
                    $section[1],
                    $readme . ' Requirements does not say that the constraint also admits ' . $release
                    . ', on which this package has never been run'
                );
            }
        }
    }
}
