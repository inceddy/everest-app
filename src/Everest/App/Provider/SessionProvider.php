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

use Everest\App\Options;
use Everest\App\Session;
use Everest\Container\FactoryProviderInterface;

class SessionProvider implements FactoryProviderInterface
{
    private const STATE_IDLE = 0;

    private const STATE_INITIALIZED = 1;

    /**
     * Provider state
     */
    private int $state;

    public function __construct()
    {
        $this->state = self::STATE_IDLE;
    }


    public function getFactory()
    {
        $this->state = self::STATE_INITIALIZED;

        return ['Options', function (Options $options) {
            $session = new Session($options('session.options', []));

            if ($options('session.auto_start', true)) {
                $session->start();
            }

            return $session;
        }];
    }
}
