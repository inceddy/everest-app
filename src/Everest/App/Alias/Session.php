<?php

/*
 * This file is part of Everest.
 *
 * (c) 2019 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\App\Alias;
use Everest\App\Alias;

/**
 * Alias for session
 */

class Session extends Alias {
	protected static function getName() : string
	{
		return 'Session';
	}
}
