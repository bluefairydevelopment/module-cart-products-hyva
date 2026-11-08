<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Package;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * UNDER STATIC ANALYSIS, AN UNRESOLVED FACTORY OF ANY NAMESPACE IS A REFUSAL.
 *
 * The bootstrap synthesises a Factory/Extension class composer cannot find, and the
 * stand-in's create() returns null. Under phpstan that silences everything
 * downstream and the run exits 0. It used to refuse only for this package's OWN
 * namespace, so a Magento factory missing from a sparse generated/code (measured
 * 2026-09-25: "1 unresolved", 0 errors) slipped through.
 *
 * This runs the REAL Test/bootstrap.php in a PHP subprocess against a fake Magento
 * root and a fake package whose one shipped file imports Magento\Nowhere\ThingFactory.
 * Analysis mode is triggered the way the bootstrap detects it: argv[0] contains
 * "phpstan" (the subprocess runs a wrapper named phpstan-wrapper.php).
 */
class BootstrapRefusesAnyUnresolvedFactoryUnderAnalysisTest extends TestCase
{
    private const BOOTSTRAP = 'Test/bootstrap.php';
    private const SHIPPED_FILE = 'Model/Thing.php';
    private const SENTINELS = ['Magento/Catalog/Api/Data/ProductExtensionInterface.php'];
    private const FOREIGN = 'Magento\\Nowhere\\ThingFactory';

    /** @var string the fixture directory, removed in tearDown() */
    private string $tmp = '';

    protected function setUp(): void
    {
        $realRoot = (string) getenv('MAGENTO_ROOT');
        $classLoader = $realRoot . '/vendor/composer/ClassLoader.php';
        if (!is_file($classLoader)) {
            self::fail('MAGENTO_ROOT has no vendor/composer/ClassLoader.php: ' . $classLoader);
        }

        $this->tmp = sys_get_temp_dir() . '/bfd-bootstrap-' . bin2hex(random_bytes(6));
        $root = $this->tmp . '/magento';
        $pkg = $this->tmp . '/package';

        $this->write($root . '/vendor/autoload.php', sprintf(
            "<?php\nrequire_once %s;\nreturn new \\Composer\\Autoload\\ClassLoader();\n",
            var_export($classLoader, true)
        ));
        foreach (self::SENTINELS as $sentinel) {
            $this->write($root . '/generated/code/' . $sentinel, "<?php\n");
        }

        $realBootstrap = dirname(__DIR__, 3) . '/' . self::BOOTSTRAP;
        $this->write($pkg . '/' . self::BOOTSTRAP, (string) file_get_contents($realBootstrap));
        $this->write($pkg . '/' . self::SHIPPED_FILE, "<?php\nnamespace Fixture;\n\nuse " . self::FOREIGN . ";\n");

        $bootstrap = var_export($pkg . '/' . self::BOOTSTRAP, true);
        $this->write($this->tmp . '/phpstan-wrapper.php', "<?php\nrequire {$bootstrap};\n");
        $this->write($this->tmp . '/unit-wrapper.php', "<?php\nrequire {$bootstrap};\n");
    }

    protected function tearDown(): void
    {
        if ($this->tmp === '' || !is_dir($this->tmp)) {
            return;
        }
        $walk = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->tmp, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($walk as $entry) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- test only: removes the fixture it built
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- test only: removes the fixture it built
        rmdir($this->tmp);
    }

    public function testAnalysisRefusesAForeignUnresolvedFactoryAndNamesIt(): void
    {
        [$status, $stderr] = $this->runBootstrap('phpstan-wrapper.php');

        self::assertNotSame(0, $status, "the bootstrap let analysis run over a synthesised factory:\n" . $stderr);
        self::assertStringContainsString(self::FOREIGN, $stderr);
        self::assertStringContainsString('bin/magento setup:di:compile', $stderr);
    }

    public function testTheUnitRunStillSynthesises(): void
    {
        [$status, $stderr] = $this->runBootstrap('unit-wrapper.php');

        self::assertSame(0, $status, $stderr);
        self::assertStringContainsString('1 unresolved', $stderr, 'the denominator line is missing');
    }

    public function testAnalysisRunsWhenTheFactoryResolves(): void
    {
        $this->write(
            $this->tmp . '/magento/generated/code/' . str_replace('\\', '/', self::FOREIGN) . '.php',
            "<?php\nnamespace Magento\\Nowhere;\nclass ThingFactory {}\n"
        );

        [$status, $stderr] = $this->runBootstrap('phpstan-wrapper.php');

        self::assertSame(0, $status, $stderr);
        self::assertStringContainsString('0 unresolved', $stderr);
    }

    /**
     * @return array{int, string}
     */
    private function runBootstrap(string $wrapper): array
    {
        $command = 'MAGENTO_ROOT=' . escapeshellarg($this->tmp . '/magento') . ' '
            . escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($this->tmp . '/' . $wrapper)
            . ' 2>&1 1>/dev/null';
        $out = [];
        $status = 0;
        // phpcs:ignore Magento2.Security.InsecureFunction -- test only: runs the bootstrap as a child process; no request input reaches the command
        exec($command, $out, $status);

        return [$status, implode("\n", $out)];
    }

    private function write(string $path, string $contents): void
    {
        if (!is_dir(dirname($path))) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction -- test only: builds a fixture in a temp dir
            mkdir(dirname($path), 0777, true);
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction -- test only: builds a fixture in a temp dir
        file_put_contents($path, $contents);
    }
}
