<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

/**
 * A SIBLING REQUIREMENT IS A CLAIM ABOUT EVERY BASE RELEASE IT ADMITS.
 *
 * M2MAP-650 review round 2, R2-1. Until this change composer.json required
 * bluefairy/module-cart-products at "^1.0". Base releases 1.0.0 - 1.0.3 declare
 * `magento/framework: "*"`, and 1.0.4 / 1.0.5 require the 2.4.9 line
 * (`^103.0.9`). So on Magento 2.4.8 Composer could satisfy this package only
 * with base 1.0.3 - a release from before M2MAP-151, whose archive still shipped
 * Test/ (the Playwright test carrying a live admin password). Our rig could not
 * see it: it installs the base from a path repository at a forced version.
 *
 * The rule held here: this package's base requirement admits NO released base
 * whose composer.json leaves magento/framework unconstrained - missing, or a
 * constraint that admits Magento 2.0.0's framework (100.0.0), which `*` does.
 *
 * Two sources of the released tags, so the rule holds wherever the suite runs:
 *
 * - RELEASED_BASE_FRAMEWORK, a record of every base tag's requirement, read via
 *   git on 2026-10-07. Always checked.
 * - The base repository's own git history, when one is reachable: every tag
 *   Composer would offer as a release is read (`git tag -l`, then
 *   `git show <tag>:composer.json`), the rule is applied to all of them, and the
 *   record is compared with what git says, so the record cannot drift from
 *   history. "Every tag Composer would offer" is every tag its VersionParser can
 *   normalise, with or without a leading "v" - NOT `git tag -l 'v*'`, which is
 *   what this read until review round 3 (L1): an unprefixed `1.0.6` tag is a
 *   release to Composer and was invisible here. Looked for at $CART_PRODUCTS_GIT (which must then
 *   be usable), $CART_PRODUCTS_SRC, next to this repository's main checkout,
 *   and next to this package root. With none reachable (a clone of this package
 *   alone) that one test is skipped, naming where it looked; the recorded check
 *   above still runs. On the rig's gate it runs: since rig c2a1b94 each package
 *   is uploaded as a git bundle carrying every tag, and the base is the sibling
 *   clone next to this package root (gate run 20261007-215732-36460: the git
 *   case passed on 2.4.9 and 2.4.8, not skipped).
 */
class TheBaseRequirementExcludesUnconstrainedBaseReleasesTest extends TestCase
{
    private const BASE = 'bluefairy/module-cart-products';

    /** Magento 2.0.0's magento/framework: admitting it constrains nothing a store runs today. */
    private const OLDEST_FRAMEWORK = '100.0.0';

    /**
     * Every released base tag => its composer.json `require.magento/framework`
     * (null = not declared). Read via git on 2026-10-07 from module-cart-products.
     *
     * Tags v1.0.0, v1.0.1 and v1.0.2 were DELETED on 2026-10-08 (Jennifer's decision:
     * superseded, never install), on GitHub and in the local clones, so they are no
     * longer releases Composer can offer and are no longer recorded here: the git
     * comparison below fails on a record naming a tag the history does not have
     * (seen red with them still listed: rig-gate-runs/20261008-s7-cart-tags/). v1.0.3
     * stays, with `magento/framework: "*"` - the unconstrained release this rule must
     * still exclude, and the case that keeps the recorded check able to fail.
     */
    private const RELEASED_BASE_FRAMEWORK = [
        'v1.0.3' => '*',
        'v1.0.4' => '^103.0.9',
        'v1.0.5' => '^103.0.9',
    ];

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private static function ownRequirement(): string
    {
        $composer = json_decode((string) file_get_contents(self::root() . '/composer.json'), true);
        self::assertIsArray($composer, 'composer.json does not parse');
        $requirement = $composer['require'][self::BASE] ?? null;
        self::assertIsString($requirement, 'composer.json no longer requires ' . self::BASE);

        return $requirement;
    }

    /** True when a base release's framework requirement leaves Magento unconstrained. */
    private static function constrainsNothing(?string $framework): bool
    {
        return $framework === null
            || trim($framework) === ''
            || Semver::satisfies(self::OLDEST_FRAMEWORK, $framework);
    }

    /**
     * The version Composer reads out of a tag, or null when Composer would not offer the tag
     * as a release at all. Composer's VCS repositories normalise every tag with VersionParser
     * and skip the ones that do not parse; "v1.0.5" and "1.0.6" both parse.
     */
    private static function versionOfTag(string $tag): ?string
    {
        try {
            return (new VersionParser())->normalize($tag);
        } catch (UnexpectedValueException) {
            return null;
        }
    }

    /**
     * The tags among $tags that Composer would offer as releases, in the order given.
     *
     * @param list<string> $tags
     * @return list<string>
     */
    private static function releaseTags(array $tags): array
    {
        return array_values(array_filter($tags, static fn (string $tag): bool => self::versionOfTag($tag) !== null));
    }

    /**
     * The tags among $tagFramework that $requirement admits although they leave
     * magento/framework unconstrained.
     *
     * @param array<string, ?string> $tagFramework
     * @return list<string>
     */
    private static function admittedUnconstrained(string $requirement, array $tagFramework): array
    {
        $admitted = [];
        foreach ($tagFramework as $tag => $framework) {
            $version = self::versionOfTag((string) $tag);
            if ($version === null) {
                throw new RuntimeException("cannot read a version out of base tag \"$tag\"; this check would be blind");
            }
            if (self::constrainsNothing($framework) && Semver::satisfies($version, $requirement)) {
                $admitted[] = (string) $tag;
            }
        }

        return $admitted;
    }

    /**
     * Every tag of $repo that Composer would offer as a release - read with `git tag -l` and
     * no pattern, so an unprefixed tag counts as much as a "v" one.
     *
     * @return array{0: int, 1: list<string>, 2: string} git's exit status, the release tags, stderr
     */
    private static function releaseTagsOf(string $repo): array
    {
        [$rc, $list, $err] = self::git($repo, ['tag', '-l']);

        return [$rc, self::releaseTags(array_values(array_filter(array_map('trim', explode("\n", $list))))), $err];
    }

    /**
     * @param list<string> $args passed as an argv array: nothing goes through a shell
     * @return array{0: int, 1: string, 2: string} exit status, stdout, stderr
     */
    private static function git(string $repo, array $args): array
    {
        $process = proc_open(
            array_merge(['git', '-C', $repo], $args),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new RuntimeException('cannot start git');
        }
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $out, $err];
    }

    /**
     * @return array{0: ?string, 1: list<string>} the base repository with tags (or null), and every place looked
     */
    private static function findBaseHistory(): array
    {
        $candidates = [];
        $explicit = getenv('CART_PRODUCTS_GIT') ?: '';
        if ($explicit !== '') {
            $candidates[] = $explicit;
        }
        $src = getenv('CART_PRODUCTS_SRC') ?: '';
        if ($src !== '') {
            $candidates[] = $src;
        }
        [$rc, $common] = self::git(self::root(), ['rev-parse', '--path-format=absolute', '--git-common-dir']);
        if ($rc === 0 && trim($common) !== '') {
            $candidates[] = dirname(trim($common), 2) . '/module-cart-products';
        }
        $candidates[] = dirname(self::root()) . '/module-cart-products';

        $looked = [];
        foreach (array_values(array_unique($candidates)) as $candidate) {
            $looked[] = $candidate;
            [$rc, $tags] = is_dir($candidate) ? self::releaseTagsOf($candidate) : [-1, []];
            if ($rc === 0 && $tags !== []) {
                return [$candidate, $looked];
            }
            if ($candidate === $explicit) {
                self::fail("CART_PRODUCTS_GIT=$explicit has no release tags git can list (exit $rc)");
            }
        }

        return [null, $looked];
    }

    public function testTheRequirementAdmitsNoRecordedBaseReleaseThatLeavesMagentoUnconstrained(): void
    {
        $unconstrained = array_filter(self::RELEASED_BASE_FRAMEWORK, [self::class, 'constrainsNothing']);
        self::assertNotEmpty($unconstrained, 'the record names no unconstrained release, so this check could not fail');

        $requirement = self::ownRequirement();
        self::assertSame(
            [],
            self::admittedUnconstrained($requirement, self::RELEASED_BASE_FRAMEWORK),
            sprintf(
                'composer.json requires %s "%s", which admits these base releases (of %d recorded) whose '
                . 'composer.json does not constrain magento/framework - Composer can pick one on any Magento.',
                self::BASE,
                $requirement,
                count(self::RELEASED_BASE_FRAMEWORK)
            )
        );
    }

    public function testEveryReleasedBaseTagInGitHistoryIsExcludedAndMatchesTheRecord(): void
    {
        [$repo, $looked] = self::findBaseHistory();
        if ($repo === null) {
            self::markTestSkipped(
                'no module-cart-products git history with release tags is reachable; looked at: ' . implode(', ', $looked)
                . '. Set CART_PRODUCTS_GIT to a base clone with its tags. The recorded check still ran.'
            );
        }

        [$rc, $tags, $err] = self::releaseTagsOf($repo);
        self::assertSame(0, $rc, "git tag in $repo: $err");

        [$rc, $json, $err] = self::git($repo, ['show', 'HEAD:composer.json']);
        self::assertSame(0, $rc, "git show HEAD:composer.json in $repo: $err");
        self::assertSame(self::BASE, (json_decode($json, true) ?: [])['name'] ?? null, "$repo is not " . self::BASE);

        // Every tag counts, whatever name its composer.json carries (v1.0.0 says
        // blue-fairy/module-cart-products): counting one Composer might not offer
        // can only make this stricter.
        $fromGit = [];
        foreach ($tags as $tag) {
            [$rc, $json, $err] = self::git($repo, ['show', $tag . ':composer.json']);
            self::assertSame(0, $rc, "git show $tag:composer.json in $repo: $err");
            $composer = json_decode($json, true);
            self::assertIsArray($composer, "$tag:composer.json does not parse");
            $fromGit[$tag] = $composer['require']['magento/framework'] ?? null;
        }
        self::assertNotEmpty($fromGit, "read no base tags out of $repo");

        $requirement = self::ownRequirement();
        self::assertSame(
            [],
            self::admittedUnconstrained($requirement, $fromGit),
            sprintf(
                'composer.json requires %s "%s", which admits these base tags (of %d read from %s) whose '
                . 'composer.json does not constrain magento/framework.',
                self::BASE,
                $requirement,
                count($fromGit),
                $repo
            )
        );

        foreach (self::RELEASED_BASE_FRAMEWORK as $tag => $framework) {
            self::assertArrayHasKey($tag, $fromGit, "the record names $tag, which $repo has no tag for");
            self::assertSame($framework, $fromGit[$tag], "the record of $tag's magento/framework is not what git says");
        }
    }

    /**
     * CONTROL for review round 3, L1: the git read sees an UNPREFIXED release tag. A throwaway
     * repository gets a "v1.0.5" tag, an unprefixed "1.0.6" tag and a "nightly" tag; the read
     * must return the first two and not the third. `git tag -l 'v*'` - what this read was -
     * returns only v1.0.5 and fails here.
     */
    public function testTheGitReadSeesUnprefixedReleaseTagsAndSkipsNonVersionTags(): void
    {
        $repo = sys_get_temp_dir() . '/cart-products-tags-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($repo), "cannot create $repo");
        try {
            $id = ['-c', 'user.name=tag-read-control', '-c', 'user.email=tag-read-control@invalid',
                '-c', 'commit.gpgsign=false'];
            foreach ([
                ['init', '-q'],
                [...$id, 'commit', '-q', '--allow-empty', '-m', 'control'],
                ['tag', 'v1.0.5'],
                ['tag', '1.0.6'],
                ['tag', 'nightly'],
            ] as $args) {
                [$rc, , $err] = self::git($repo, $args);
                self::assertSame(0, $rc, 'git ' . implode(' ', $args) . ": $err");
            }

            [$rc, $tags, $err] = self::releaseTagsOf($repo);
            self::assertSame(0, $rc, $err);
            sort($tags);
            self::assertSame(['1.0.6', 'v1.0.5'], $tags);
        } finally {
            self::removeTree($repo);
        }
    }

    private static function removeTree(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($dir);
    }

    /** CONTROLS: the predicate rejects the requirement that shipped, and reads constraints the way Composer does. */
    public function testTheCheckRejectsTheRequirementThatShippedUpTo105(): void
    {
        self::assertSame(['v1.0.3'], self::admittedUnconstrained('^1.0', self::RELEASED_BASE_FRAMEWORK));
        self::assertSame(['v1.0.3'], self::admittedUnconstrained('^1.0.4 || 1.0.3', self::RELEASED_BASE_FRAMEWORK));
        self::assertSame([], self::admittedUnconstrained('^1.0.4', self::RELEASED_BASE_FRAMEWORK));
        self::assertSame(['1.0.6'], self::admittedUnconstrained('^1.0.4', ['1.0.6' => '*', 'v1.0.7' => '^103.0.8']));
        self::assertSame(['v1.0.5', '1.0.6'], self::releaseTags(['v1.0.5', '1.0.6', 'nightly', 'release-candidate']));

        self::assertTrue(self::constrainsNothing(null));
        self::assertTrue(self::constrainsNothing('*'));
        self::assertTrue(self::constrainsNothing('>=100.0'));
        self::assertFalse(self::constrainsNothing('^103.0.9'));
        self::assertFalse(self::constrainsNothing('^103.0.8'));
    }
}
