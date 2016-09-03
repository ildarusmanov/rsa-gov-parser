<?php
namespace app\models;

class ParserState
{
	const STATE_FILE_PATH = '/../runtime/state/parser_state.data';

	protected $stateData = [];

	public function __construct()
	{
		$this->load();
	}

	public function setStateParam($key, $value)
	{
		$this->stateData[$key] = $value;

		$this->save();

		return $this;
	}

	public function getStateParam($key)
	{
		if (isset($this->stateData[$key])) {
			return $this->stateData[$key];
		}

		return;
	}

	protected function save()
	{
		$fp = fopen(__DIR__ . self::STATE_FILE_PATH, "w+");
		fwrite($fp, serialize($this->stateData));
		fclose($fp);
	}

	protected function load()
	{
		if (!file_exists(__DIR__ . self::STATE_FILE_PATH)) {
			$this->stateData = [];
			return;
		}

		$data = file_get_contents(__DIR__ . self::STATE_FILE_PATH);

		if (empty($data)) {
			return;
		}

		$this->stateData = unserialize($data);
	}
}