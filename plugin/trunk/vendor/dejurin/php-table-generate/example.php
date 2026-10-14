<?php

require_once './src/PHPTableGenerate.php';

$table = new Dejurin\PHPTableGenerate();

$table->set_heading('Name', 'Color', 'Size');

$data = [
    [
        [
            'data' => 'Euro',
            'td' => 'th',
            'scope' => 'row',
            'class' => 'style',
        ],
        '#000000',
        100,
    ],
];

echo $table->generate($data);
