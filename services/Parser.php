<?php
namespace app\services;

use app\models\ParserFilterForm;

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
		} catch(\Exception $e) {
			return [];
		}

		if (sizeof($items) == 0) {
			return [];
		}

		return $items;
	}

	public function getViewPage($itemId)
	{
		$curl = new \Curl\Curl();
		$userAgent = 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:48.0) Gecko/20100101 Firefox/48.0';
		$curl->setHeader('User-Agent', $userAgent);
		$curl->setHeader('Referer', 'http://188.254.71.82/rds_ts_pub');

		$itemUrl = 'http://188.254.71.82/rds_ts_pub/?show=view&id_object=' . $itemId;
		$curl->get($itemUrl);
		$content = $curl->response;

		echo 'GET Url: ' . $itemUrl . "\r\n";

		$content = iconv('cp1251', 'utf-8', $curl->response);

		if (preg_match_all(self::CAPTCHA_REGEX, $content, $captchaMatches)) {

			$captchaUrl = 'http://188.254.71.82/rds_ts_pub/' . $captchaMatches[0][0];

			$curl->setHeader('Referer', $itemUrl);
			$curl->get($captchaUrl);
			$captchaCode = $this->getCaptchaCode($curl->response);

			echo 'Url: ' . $captchaUrl . "\r\n";

			$captchaRegUrl = 'http://188.254.71.82/rds_ts_pub/reg.php';
			echo 'Url: ' . $captchaRegUrl . "\r\n";

			$curl->setHeader('Referer', $itemUrl);
			$curl->post($captchaRegUrl, [
					'captcha' => $captchaCode,
				]);

			print_r($curl->response_headers);
			echo "\r\n";

			$cookies = $this->getResponseHeader('Set-Cookie', $curl->response_headers);

			if ($cookies) {
				$cookies = $this->parseCookies($cookies);
				foreach ($cookies as $k => $v) {
					$curl->setCookie($k, $v);
				}
			}

			print_r($cookies);
			echo "\r\n";

			$curl->get($itemUrl);

			$content = iconv('cp1251', 'utf-8', $curl->response);
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

	protected function getResponseHeader($header, $headers) {
	  foreach ($headers as $key => $r) {
	     if (stripos($r, $header) !== FALSE) {
	        list($headername, $headervalue) = explode(":", $r, 2);
	        return trim($headervalue);
	     }
	  }
	}

	protected function parseCookies($str)
	{
		$cookies = [];

		foreach(explode('; ',$str) as $k => $v){
            preg_match('/^(.*?)=(.*?)$/i',trim($v),$matches);
            $cookies[trim($matches[1])] = urldecode($matches[2]);
        }

        return $cookies;
	}
}