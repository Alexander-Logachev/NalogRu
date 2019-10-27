<?php

namespace AlexanderLogachev;

use DOMDocument;
use DOMXPath;

/**
 * Получаем ИНН из https://service.nalog.ru/inn.do
 * Class NalogRu
 */
class NalogRu
{
    const BASE_URL      = 'https://service.nalog.ru/';
    const BASE_URL_FORM = 'inn.do';                         // получить токен из html
    const TOKEN_URL     = 'static/captcha.html';            // получить токен
    const CAPTCHA_URL   = 'static/captcha.html?a=';         // получить картинку captcha
    const CHECK_URL     = 'inn-proc.do';                    // получить ИНН
    const SIGN_FORM     = 'static/personal-data-proc.json'; // подпись соглашения

    const DOC_TYPE_USSR                     = '01'; // Паспорт гражданина СССР
    const DOC_TYPE_BIRTH_LICENCE            = '03'; // Свидетельство о рождении
    const DOC_TYPE_FOREIGN_PASSPORT         = '10'; // Паспорт иностранного гражданина
    const DOC_TYPE_RESIDENCE_PERMIT         = '12'; // Вид на жительство в Российской Федерации
    const DOC_TYPE_RESIDENCE_TEMPORARY_     = '15'; // Разрешение на временное проживание в Российской Федерации
    const DOC_TYPE_TEMPORARY_ASYLUM         = '19'; // Свидетельство о предоставлении временного убежища на территории Российской Федерации
    const DOC_TYPE_PASSPORT                 = '21'; // Паспорт гражданина Российской Федерации
    const DOC_TYPE_BIRTH_LICENCE_FOREIGN    = '23'; // Свидетельство о рождении, выданное уполномоченным органом иностранного государства
    const DOC_TYPE_RESIDENCE_PERMIT_FOREIGN = '62'; // Вид на жительство иностранного гражданина

    const OPT_OTCH = 0; // если отсутвует отчество

    private $captchaToken;
    private $data = [
        'c'            => 'innMy',
        'fam'          => '', // Фамилия (required)
        'nam'          => '', // Имя (required)
        'otch'         => '', // Отчество (required) [ otch!='' || opt_otch=1 ]
        'opt_otch'     => '', // Отчество отсутсвует
        'bdate'        => '', // Дата рождения (required)
        'bplace'       => '', // Место рождения
        'doctype'      => '', // Вид документа, удостоверяющего личность (required)
        'docno'        => '', // Серия и номер документа (required)
        'docdt'        => '', // Дата выдачи документа
        'captcha'      => '',
        'captchaToken' => '',
    ];

    public function __construct (array $data)
    {

        $this->data = array_merge($this->data, $data);

        if ($data['otch']) {
            unset($this->$data['opt_otch']);
        }
        else {
            unset($this->$data['otch']);
            $this->data['opt_otch'] = self::OPT_OTCH;
        }

        $this->data['doctype'] = $this->data['doctype'] ?: self::DOC_TYPE_PASSPORT;

    }

    /**
     * Получаем сразу captchaToken
     *
     * @return false|string
     */
    public function getToken ()
    {
        $str = file_get_contents(self::BASE_URL . self::TOKEN_URL);
        if (($str != '') and ($this->captchaToken == ''))
            $this->captchaToken = $str;

        return $this->captchaToken;
    }

    /**
     * Парсим из html captchaToken
     *
     * @return string
     */
    public function getTokenHtml ()
    {
        $html = $this->getHtmlForm();

        // получаем captchaToken
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $tags  = $xpath->query('//input[@name="captchaToken"]');
        foreach ($tags as $tag) {
            $this->captchaToken = trim($tag->getAttribute('value'));
        }

        return $this->captchaToken;
    }

    /**
     * Получаем ссылку на картинку captcha
     *
     * @param string $captchaToken
     *
     * @return string
     */
    public function getCaptchaImg (string $captchaToken = '')
    {
        if ($captchaToken) {
            return self::BASE_URL . self::CAPTCHA_URL . $this->captchaToken;
        }
        else if (!$this->captchaToken) {
            $this->getToken();
        }

        return self::BASE_URL . self::CAPTCHA_URL . $this->captchaToken;
    }

    /**
     * Получаем ИНН
     *
     * @return mixed
     */
    public function getData ()
    {
        $url = self::BASE_URL . self::CHECK_URL;

        if ($curl = curl_init()) {

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $this->data);
            $res = curl_exec($curl);
            curl_close($curl);

            $out = json_decode($res, true);

            return $out;
        }
    }

    /**
     * Получаем куки для запроса, Подписываем "СОГЛАСИЕ НА ОБРАБОТКУ ПЕРСОНАЛЬНЫХ ДАННЫХ"
     *
     * @return array
     */
    private function prepareCoockie ()
    {
        // получаем куку upd_
        $ch = curl_init(self::BASE_URL . self::SIGN_FORM);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            [
                'from'         => '/inn.do',
                'svc'          => 'inn',
                'personalData' => 1,
            ]
        );
        $result = curl_exec($ch);
        curl_close($ch);

        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = [];
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }

        return $cookies;
    }

    /**
     * Получаем html с формой для заполнения данных
     *
     * @return bool|string
     */
    private function getHtmlForm ()
    {
        $cookies = $this->prepareCoockie();

        $cookiesStr = http_build_query($cookies);
        $cookiesStr = str_replace('&', ';', $cookiesStr);

        //получаем html
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, self::BASE_URL . self::BASE_URL_FORM);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_COOKIE, $cookiesStr);
        curl_setopt($curl, CURLOPT_POSTFIELDS, []);
        $html = curl_exec($curl);
        curl_close($curl);

        return $html;
    }

}