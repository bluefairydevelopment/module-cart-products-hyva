<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Test\Unit\Observer;

use BlueFairy\CartProductsHyva\Observer\RegisterModuleForHyvaConfig;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BlueFairy\CartProductsHyva\Observer\RegisterModuleForHyvaConfig
 */
class RegisterModuleForHyvaConfigTest extends TestCase
{
    private const MODULE_NAME = 'BlueFairy_CartProductsHyva';

    /** @var ComponentRegistrar&MockObject */
    private ComponentRegistrar&MockObject $componentRegistrar;

    private RegisterModuleForHyvaConfig $observer;

    protected function setUp(): void
    {
        if (!defined('BP')) {
            define('BP', '/var/www/magento');
        }

        $this->componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $this->observer = new RegisterModuleForHyvaConfig($this->componentRegistrar);
    }

    private function createEvent(?DataObject $config): Observer&MockObject
    {
        $event = $this->createMock(Observer::class);
        $event->method('getData')->with('config')->willReturn($config);

        return $event;
    }

    public function testDoesNothingWhenConfigIsNull(): void
    {
        $this->componentRegistrar->expects($this->never())->method('getPath');

        $this->observer->execute($this->createEvent(null));
    }

    public function testDoesNothingWhenModulePathIsNull(): void
    {
        $this->componentRegistrar
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::MODULE_NAME)
            ->willReturn(null);

        $config = $this->createMock(DataObject::class);
        $config->expects($this->never())->method('setData');

        $this->observer->execute($this->createEvent($config));
    }

    public function testAppendsThisModuleToEmptyExtensionsList(): void
    {
        $this->componentRegistrar
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::MODULE_NAME)
            ->willReturn(BP . '/vendor/bluefairy/module-cart-products-hyva');

        $config = $this->createMock(DataObject::class);
        $config->method('hasData')->with('extensions')->willReturn(false);
        $config->expects($this->once())
            ->method('setData')
            ->with('extensions', [['src' => 'vendor/bluefairy/module-cart-products-hyva']]);

        $this->observer->execute($this->createEvent($config));
    }

    public function testAppendsToExistingExtensionsListWithoutDroppingOthers(): void
    {
        $this->componentRegistrar
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::MODULE_NAME)
            ->willReturn(BP . '/vendor/bluefairy/module-cart-products-hyva');

        $existing = [['src' => 'vendor/bluefairy/module-free-gift-hyva']];

        $config = $this->createMock(DataObject::class);
        $config->method('hasData')->with('extensions')->willReturn(true);
        $config->method('getData')->with('extensions')->willReturn($existing);
        $config->expects($this->once())
            ->method('setData')
            ->with('extensions', [
                ['src' => 'vendor/bluefairy/module-free-gift-hyva'],
                ['src' => 'vendor/bluefairy/module-cart-products-hyva'],
            ]);

        $this->observer->execute($this->createEvent($config));
    }

    public function testStripsLeadingSlashFromRelativePath(): void
    {
        // getPath() returns an absolute path; the leading "/" after BP must not survive
        // into the extension entry, or hyva-sources resolves it against the filesystem
        // root instead of the Magento root.
        $this->componentRegistrar
            ->method('getPath')
            ->with(ComponentRegistrar::MODULE, self::MODULE_NAME)
            ->willReturn(BP . '/vendor/bluefairy/module-cart-products-hyva');

        $config = $this->createMock(DataObject::class);
        $config->method('hasData')->willReturn(false);

        $captured = null;
        $config->method('setData')
            ->willReturnCallback(function (string $key, array $value) use (&$captured): void {
                $captured = $value;
            });

        $this->observer->execute($this->createEvent($config));

        $this->assertNotNull($captured);
        $this->assertStringStartsNotWith('/', $captured[0]['src']);
    }
}
