<?php

include(__DIR__ . '/../src/NalogRu.php');

$post = [
    'fam'          => 'Иванов',
    'nam'          => 'Иван',
    'otch'         => 'Иванович',
    'bdate'        => '12.12.2000',
    'doctype'      => 21,
    'docno'        => '00 00 000000',
    'captcha'      => '',
    'captchaToken' => '',
];

$nalogRu = new AlexanderLogachev\NalogRu($post);

echo "<pre>";
print_r($nalogRu->getData());
echo "</pre>";