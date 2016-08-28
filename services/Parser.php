<?php
namespace app\services;

use app\models\ParserFilterForm;

class Parser
{
	protected $data;
	protected $items;

	const URL_1 = 'http://public.fsa.gov.ru/table_rds_pub_ts/index.php';
	const URL_2 = '';
	const PAGE_LIMIT = 50;
	const ITEM_REGEX = '/<tr id="id\_([^"]+)"/siU';
	const CAPTCHA_REGEX = '"captcha\.php\?sid=\d+"';
	const MAX_PAGE_ID = 100000;
	const RESULT_CSV_PATH = 'csv/parsed.csv';


	public function __construct($data)
	{
		$this->data = $data;
		$this->items = [];
		$this->initFile();
	}

	public function run()
	{
		$pageId = 0;
		$page = [];

		try {
			while ($pageId < self::MAX_PAGE_ID) {
				$content = $this->getListingPage($pageId);

				foreach ($this->getPageItems($content) as $item) {
					$this->items[] = $item[1];
				}

				$pageId++;				
			}
		} catch(\Exception $e) {
			echo $e->getMessage();
		}

		if (sizeof($this->items) == 0) {
			return;
		}

		foreach ($this->items as $itemId) {
			$this->getViewPage($itemId);
		}
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

		$this->writeData($data);
	}

	public function parseItemContent($content)
	{
		$parser = new ItemParser($content);

		return $parser->getData();
	}

	public function initFile()
	{
		$handle = fopen(self::RESULT_CSV_PATH, "w+");
		fclose($handle);
	}

	public function writeData($data)
	{
		$handle = fopen(self::RESULT_CSV_PATH, "a+");
		fputscsv($handle, $data);
		fclose($handle);	
	}

	public function getCaptchaCode($captchaUrl)
	{
		$fileContent = file_get_contents($captchaUrl);

		return $this->captchaRecognizer->getCode($fileContent);
	}
}