<?php
namespace app\services;

class ItemDataConverter
{
	public $summary = [];
	public $declaration = [];
	public $declarantForeign = [];
	public $declarant = [];
	public $declarantIp = [];
	public $producerForeign = [];
	public $producer = [];
	public $producerIp = [];
	public $products = [];
	public $standarts = [];
	public $experts = [];
	public $documents = [];
	public $laboratory;
	public $certifierName;
	public $attachments;

	protected $data = [];

	public function __construct($data)
	{
		$this->convert($data);
	}

	public function getData()
	{
		return $this->data;
	}

	protected function convert($data)
	{
		print_r($data);
		
		$this->data = [];

		$iterator = new \RecursiveArrayIterator($data);

		foreach(new \RecursiveIteratorIterator($iterator) as $v) {
			$this->data[] = $v;
		}
	}
}