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
	const MAX_PAGE_ID = 5;

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

		return $matches;
	}

	public function getItemsByPageId($pageId)
	{
		try {
			while ($pageId < self::MAX_PAGE_ID) {
				$content = $this->getListingPage($pageId);

				foreach ($this->getPageItems($content) as $item) {
					$items[] = $item[1];
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
		$curl->get('http://188.254.71.82/rds_ts_pub/?show=view&id_object=' . $itemId);

		$content = iconv('cp1251', 'utf-8', $curl->response);

		if (preg_match_all(self::CAPTCHA_REGEX, $content, $captchaMatches)) {
			$captchaUrl = 'http://188.254.71.82/rds_ts_pub/' . $captchaMatches[0];
			$captchaCode = $this->getCaptchaCode($captchaUrl);

			$curl = new \Curl\Curl();
			$curl->post('http://188.254.71.82/rds_ts_pub/reg.php', [
					'captcha' => $captchaCode,
				]);

			$curl = new \Curl\Curl();
			$curl->get('http://188.254.71.82/rds_ts_pub/?show=view&id_object=' . $itemId);

			$content = iconv('cp1251', 'utf-8', $curl->response);
		}

		$data = $this->parseItemContent($content);

		return $data;
	}

	public function parseItemContent($content)
	{
		$parser = new ItemParser($content);

		return $parser->getData();
	}


	public function getCaptchaCode($captchaUrl)
	{
		$fileContent = file_get_contents($captchaUrl);

		return $this->captchaRecognizer->getCode($fileContent);
	}
}