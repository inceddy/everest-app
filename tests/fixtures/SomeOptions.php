<?php

$options = new stdClass;
$options->a = 1;
$options->b = new stdClass;
$options->b->c = 'foo';
$options->d = [
	'e' => 'bar'
];

return $options;