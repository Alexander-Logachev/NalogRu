# NalogRu
Получаем ИНН из [ФНС](https://service.nalog.ru/inn.do)

##Пример запроса

### Получаем ИНН
```

$post = [
    'fam'          => 'Иванов',
    'nam'          => 'Иван',
    'otch'         => 'Иванович',
    'bdate'        => '12.12.2000',
    'doctype'      => 21,
    'docno'        => '00 00 000000',
    'captcha'      => '000000',
    'captchaToken' => '2DC18CD8041371F3FC8CD595AE13A91112BEFD7EC0DD1E23986979EA13DE77C324CF2480CB33DDCBD4AF3FB26011178A',
];

$nalogRu = new AlexanderLogachev\NalogRu($post);

```

### Получаем Токен и картинку
```
$nalogRu = new AlexanderLogachev\NalogRu([]);

$tokenId = $nalogRu->getToken();

$nalogRu->getCaptchaImg($tokenId)

```

### Получаем Токен из HTML формы [ФНС](https://service.nalog.ru/inn.do)
```
$nalogRu = new AlexanderLogachev\NalogRu([]);
$nalogRu->getTokenHtml();
```