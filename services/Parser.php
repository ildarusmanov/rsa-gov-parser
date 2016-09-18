<?php
namespace app\services;

use GuzzleHttp\Client;

class Parser
{
    protected $data;

    const PAGE_LIMIT = 50;
    const ITEM_REGEX = '/<tr id="id\_([^"]+)"/siU';
    const CAPTCHA_REGEX = '"captcha\.php\?sid=\d+"';
    const MAX_PAGE_ID = 2;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function getListingPage($pageId = 0)
    {
        $baseUrl = 'http://public.fsa.gov.ru/table_rds_pub_ts/index.php';

        $cookieData = $this->data;
        $cookieData['page_byid_'] = self::PAGE_LIMIT;

        $cookiesJar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookieData, 'public.fsa.gov.ru');

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 15.0,
            'cookies' => true,
        ]);

        $response = $client->request('POST', $baseUrl, [
            'allow_redirects' => true,
            //'decode_content' => false,
            'form_params' => [
                'ajax' => 'main',
                'action' => 'search',
                'pageGoid' => $pageId,
                'page_noid_' => $pageId,
            ],
            'cookies' => $cookiesJar,
        ]);

        return iconv('cp1251', 'utf-8', $response->getBody());
    }

    public function getPageItems($content)
    {
        if (!preg_match_all(self::ITEM_REGEX, $content, $matches)) {
            throw new \Exception('No items found');
        }

        return $matches[1];
    }

    public function getItemsByPageId($pageId)
    {
        $items = [];

        try {
            while ($pageId < self::MAX_PAGE_ID) {
                $content = $this->getListingPage($pageId);

                foreach ($this->getPageItems($content) as $item) {
                    $items[] = $item;
                }

                $pageId++;
            }
        } catch (\Exception $e) {
            return [];
        }

        if (sizeof($items) == 0) {
            return [];
        }

        return $items;
    }

    public function getViewPage($itemId)
    {
        $baseUrl = 'http://188.254.71.82/rds_ts_pub/';

        $itemUrl = $baseUrl . '?show=view&id_object=' . $itemId;

        $client = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 15.0,
            'cookies' => true,
        ]);

        $response = $client->request('GET', $itemUrl);

        $content = iconv('cp1251', 'utf-8', $response->getBody());

        if (preg_match_all(self::CAPTCHA_REGEX, $content, $captchaMatches)) {
            $captchaUrl = 'http://188.254.71.82/rds_ts_pub/' . $captchaMatches[0][0];
            //$captchaData = file_get_contents($captchaUrl);
            $captchaPath = __DIR__ . '/../runtime/state/captcha.png';
            $response = $client->request('GET', $captchaUrl, ['sink' => $captchaPath]);
            $captchaData = file_get_contents($captchaPath);
            unlink($captchaPath);

            $captchaCode = $this->getCaptchaCode($captchaData);

            $captchaRegUrl = $baseUrl . 'reg.php';

            $response = $client->request('POST', $captchaRegUrl, [
                'allow_redirects' => true,
                'decode_content' => false,
                'form_params' => ['captcha' => $captchaCode],
                'debug' => true,
                'headers' => ['Referer' => $itemUrl],
            ]);

            $content = iconv('cp1251', 'utf-8', $response->getBody());
        }

        $data = $this->parseItemContent($content);

        return $data;
    }

    public function parseItemContent($content)
    {
        $parser = new ItemParser($content);

        return $parser->getData();
    }

    public function getCaptchaCode($captchaData)
    {
        $captchaRecognizer = new CaptchaRecognizer();

        return $captchaRecognizer->getCode($captchaData);
    }
}
