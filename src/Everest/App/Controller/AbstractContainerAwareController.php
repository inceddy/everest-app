<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2018 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App\Controller;

use Everest\Container\Container;
use Everest\Container\ContainerAwareInterface;
use LogicException;

abstract class AbstractContainerAwareController implements ContainerAwareInterface
{
    protected $container;


    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Requires one or more dependencies from container.
     * If more than one dependeny is required an array is returned
     *
     * @param string $depedencies
     *   Dependecy names
     *
     * @return mixed|array
     */
    protected function require(string ...$depedencies)
    {
        if (empty($depedencies)) {
            throw new LogicException('No depedency required.');
        }

        if (count($depedencies) === 1) {
            return $this->container[$depedencies[0]];
        }

        return array_map(fn (string $depedency) => $this->container[$depedency], $depedencies);
    }
}
