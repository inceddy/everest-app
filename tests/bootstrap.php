<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Prevents error caused by send headers due to output by PHPUnit.
session_start();
