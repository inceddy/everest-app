<?php

/*
 * This file is part of ieUtilities HTTP.
 *
 * (c) 2016 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App;

interface DelegateProviderInterface
{
	/**
	 * Returns an array of delegates
	 * [string $methodname => callable $handler]
	 *
	 * @return array
	 */
	
	public function getDelegates() : array;
}