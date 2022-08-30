<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App\Provider;

use Everest\App\DelegateProviderInterface;
use Everest\App\Options;
use Everest\Container\FactoryProviderInterface;
use RuntimeException;

class OptionsProvider implements FactoryProviderInterface, DelegateProviderInterface
{
    private const STATE_IDLE = 0;

    private const STATE_INITIALIZED = 1;

    /**
     * Provider state
     */
    private int $state;

    /**
     * Options instances
     */
    private array $options;

    /**
     * Initial options instance
     */
    private readonly Options $initialOptions;

    public function __construct(Options $initialOptions = null)
    {
        $this->state = self::STATE_IDLE;
        $this->options = [];
        $this->initialOptions = $initialOptions ?: new Options();
    }

    /**
     * Adds new options to this provider
     */
    public function add(Options $options, string $namespace = null)
    {
        if ($this->state === self::STATE_INITIALIZED) {
            throw new RuntimeException('You cant add new options if provider is already initialized.');
        }

        $this->options[] = [$options, $namespace];
    }


    public function getFactory()
    {
        $this->state = self::STATE_INITIALIZED;

        return [
            fn () => array_reduce($this->options, function ($carry, $optionsAndNamespace) {
                        [$options, $namespace] = $optionsAndNamespace;
                        return $carry->merge($options, $namespace);
                    }, $this->initialOptions),
        ];
    }


    public function getDelegates(): array
    {
        return [
            'options' => function ($options, string $namespace = null) {
                $this->add(Options::from($options), $namespace);
            },
        ];
    }
}
