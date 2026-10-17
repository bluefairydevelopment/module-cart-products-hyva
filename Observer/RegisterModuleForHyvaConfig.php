<?php

/**
 * Copyright © 2026 Blue Fairy Development. All rights reserved.
 */

declare(strict_types=1);

namespace BlueFairy\CartProductsHyva\Observer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Tell Hyvä's `hyva-config` generator where this module's Tailwind sources live.
 *
 * Follows Hyva\Theme\Observer\RegisterModuleForHyvaConfig, which registers Hyva_Theme
 * itself: it appends this module's root-relative path to the config's `extensions` list on
 * the hyva_config_generate_before event, so that Hyvä's config generator lists this module
 * as a source.
 */
class RegisterModuleForHyvaConfig implements ObserverInterface
{
    private const MODULE_NAME = 'BlueFairy_CartProductsHyva';

    /**
     * @param ComponentRegistrar $componentRegistrar
     */
    public function __construct(
        private readonly ComponentRegistrar $componentRegistrar
    ) {
    }

    /**
     * Append this module's root-relative path to the Hyvä extension list.
     *
     * @param Observer $event
     * @return void
     */
    public function execute(Observer $event): void
    {
        $config = $event->getData('config');
        if ($config === null) {
            return;
        }

        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, self::MODULE_NAME);
        if ($path === null) {
            return;
        }

        // BP is Magento's root-path constant, defined at bootstrap; without it a
        // relative path cannot be formed, and a wrong one is worse than none.
        if (!defined('BP')) {
            return;
        }

        $root = (string) constant('BP');
        $extensions = $config->hasData('extensions') ? (array) $config->getData('extensions') : [];
        $extensions[] = ['src' => substr($path, strlen($root) + 1)];
        $config->setData('extensions', $extensions);
    }
}
