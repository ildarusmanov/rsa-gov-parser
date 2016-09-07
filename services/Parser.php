<?php
namespace app\services;

use GuzzleHttp\Client;

class Parser
{
    protected $data;

    const URL_1 = 'http://public.fsa.gov.ru/table_rds_pub_ts/index.php';

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
        $curl = new \Curl\Curl();
        $curl->setUserAgent('');
        $curl->setReferrer('');

        $curl->setCookie('page_byid_', self::PAGE_LIMIT);

        foreach ($this->data as $key => $value) {
            $curl->setCookie($key, $value);
        }

        $curl->post(self::URL_1, ['ajax' => 'main', 'action' => 'search', 'pageGoid' => $pageId, 'page_noid_' => $pageId]);

        if ($curl->error) {
            throw new \Exception('Can not load page');
        }

        return iconv('cp1251', 'utf-8', $curl->response);
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
        ]);

        $response = $client->request('GET', $itemUrl);

        $content = iconv('cp1251', 'utf-8', $response->getBody());

        if (preg_match_all(self::CAPTCHA_REGEX, $content, $captchaMatches)) {
            $captchaUrl = 'http://188.254.71.82/rds_ts_pub/' . $captchaMatches[0][0];

            $captchaPath = __DIR__ . '/../runtime/state/captcha.png';
            $response = $client->request('GET', $captchaUrl, ['sink' => $captchaPath]);
            $captchaData = file_get_contents($captchaPath);
            unlink($captchaPath);
            $captchaCode = $this->getCaptchaCode($captchaData);

            echo $captchaData;
            echo 'Url: ' . $captchaUrl . "\r\n";
            echo 'Code: ' . $captchaCode . "\r\n";

            $captchaRegUrl = $baseUrl . 'reg.php';
            echo 'Url: ' . $captchaRegUrl . "\r\n";
            $response = $client->post($captchaRegUrl, [
                'allow_redirects' => true,
                'decode_content' => false,
                'form_params' => ['captcha' => $captchaCode],
            ]);

            $content = iconv('cp1251', 'utf-8', $response->getBody());
        }

        echo "\r\n==============\r\n" . $content . "\r\n==============\r\n";

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

        //$fileContent = file_get_contents($captchaUrl);

        return $captchaRecognizer->getCode($captchaData);
    }
}
