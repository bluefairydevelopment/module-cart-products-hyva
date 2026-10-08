<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * WHAT A MERCHANT RECEIVES IS THE `git archive` OF THE TAG - so read that archive, not the
 * .gitattributes file that is supposed to shape it.
 *
 * M2MAP-650 review round 3, R3-8: the archive of this package shipped jest.config.js,
 * package.json and package-lock.json - the Jest harness for the Alpine.js component, which
 * no Magento install runs. Nothing secret, but a merchant's vendor/ received an npm manifest
 * and a 100 KB lockfile of development dependencies, and nothing checked what the archive
 * carried beyond Test/.
 *
 * The archive is built from HEAD with --worktree-attributes, so the .gitattributes being
 * changed is the one applied; at a release HEAD is the tag and the tree is clean (the
 * pre-push gate refuses otherwise), so this is the archive that ships.
 */
#[CoversNothing]
class TheArchiveShipsNoDevelopmentToolingTest extends TestCase
{
    /** Paths (exact, or a directory prefix ending in "/") that must never be in the archive. */
    private const NEVER_SHIPPED = [
        'Test/',
        'node_modules/',
        '.githooks/',
        'jest.config.js',
        'package.json',
        'package-lock.json',
        'phpunit.xml.dist',
        'phpstan.neon.dist',
        'phpcs.xml.dist',
        '.gitattributes',
    ];

    /** Files the module cannot run without: if these are missing, the read examined nothing real. */
    private const ALWAYS_SHIPPED = [
        'composer.json',
        'registration.php',
        'etc/module.xml',
        'view/frontend/templates/cart/export-button.phtml',
    ];

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * Every file path in `git archive --worktree-attributes HEAD` of this package.
     *
     * @return list<string>
     */
    private static function archivedPaths(): array
    {
        $process = proc_open(
            ['git', '-C', self::root(), 'archive', '--format=tar', '--worktree-attributes', 'HEAD'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        if (!is_resource($process)) {
            throw new RuntimeException('cannot start git');
        }
        $tar = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $rc = proc_close($process);
        self::assertSame(0, $rc, "git archive failed in " . self::root() . ": $err");

        return self::tarPaths($tar);
    }

    /**
     * The regular-file paths in a ustar/pax stream, as git archive writes it.
     *
     * @return list<string>
     */
    private static function tarPaths(string $tar): array
    {
        $paths = [];
        $offset = 0;
        $paxPath = null;
        $length = strlen($tar);
        while ($offset + 512 <= $length) {
            $header = substr($tar, $offset, 512);
            if (trim($header, "\0") === '') {
                break;
            }
            $name = rtrim(substr($header, 0, 100), "\0");
            $prefix = rtrim(substr($header, 345, 155), "\0");
            $size = (int) octdec(trim(substr($header, 124, 12), "\0 "));
            $type = $header[156];
            $body = substr($tar, $offset + 512, $size);
            $offset += 512 + (int) (ceil($size / 512) * 512);

            if ($type === 'x') {
                // A pax extended header: its "path=" record names the NEXT entry.
                $paxPath = preg_match('/\d+ path=([^\n]*)\n/', $body, $m) === 1 ? $m[1] : null;
                continue;
            }
            if ($type === 'g') {
                continue; // git's global header (the commit id), not a file
            }
            $path = $paxPath ?? ($prefix !== '' ? $prefix . '/' . $name : $name);
            $paxPath = null;
            if ($type === '0' || $type === "\0") {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private static function forbidden(array $paths): array
    {
        $bad = [];
        foreach ($paths as $path) {
            foreach (self::NEVER_SHIPPED as $rule) {
                $hit = str_ends_with($rule, '/') ? str_starts_with($path, $rule) || str_contains($path, '/' . $rule)
                    : ($path === $rule || str_ends_with($path, '/' . $rule));
                if ($hit) {
                    $bad[] = $path;
                    break;
                }
            }
        }

        return $bad;
    }

    public function testTheArchiveShipsNoDevelopmentTooling(): void
    {
        $paths = self::archivedPaths();
        foreach (self::ALWAYS_SHIPPED as $required) {
            self::assertContains($required, $paths, 'the archive read lost its subject - ' . count($paths) . ' files read');
        }

        self::assertSame(
            [],
            self::forbidden($paths),
            'the package archive (git archive --worktree-attributes HEAD, ' . count($paths) . ' files) ships '
            . 'development files a merchant never runs - add them to .gitattributes as export-ignore'
        );
    }

    /** CONTROL: the matcher catches each kind of rule, and passes what ships. */
    public function testTheMatcherCatchesWhatShippedBeforeR38(): void
    {
        self::assertSame(
            ['jest.config.js', 'package.json', 'package-lock.json', 'Test/Jest/x.test.js', 'a/node_modules/b.js'],
            self::forbidden([
                'composer.json', 'jest.config.js', 'package.json', 'package-lock.json', 'Test/Jest/x.test.js',
                'a/node_modules/b.js', 'view/frontend/templates/cart/export-button.phtml', 'docs/package.json.md',
            ])
        );
    }
}
