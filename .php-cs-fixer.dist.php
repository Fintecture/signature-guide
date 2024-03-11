<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('src/assets')
    ->in('src')
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
    ])
    ->setFinder($finder)
;