<?php

include(__DIR__ . '/../src/NalogRu.php');

$nalogRu = new AlexanderLogachev\NalogRu([]);

$tokenId = $nalogRu->getToken();

$imgLink = $nalogRu->getCaptchaImg($tokenId);

echo '$tokenId = ' . $tokenId . PHP_EOL;
echo '$imgLink = ' . $imgLink . PHP_EOL;