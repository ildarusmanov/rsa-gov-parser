<?php
namespace app\services;

use app\models\ParserState;

class ParserManager
{
	const STEP_INIT = 100;
	const STEP_LOAD_LISTING = 200;
	const STEP_LOAD_ITEMS = 300;
	const STEP_FINISHED = 400;

	protected $state;

	public function __construct()
	{
		$this->loadState();
	}

	protected function loadState()
	{
		$this->state = new ParserState();

		return $this;
	}

	public function run()
	{
		$this->loadState();

		$step = $this->state->getStateParam('step');

		if ($step == null || $step == self::STEP_FINISHED) {
			return;
		}

		if ($step == self::STEP_INIT) {
			return $this->stepInit();
		}

		if ($step == self::STEP_LOAD_LISTING) {
			return $this->stepLoadListing();
		}

		if ($step == self::STEP_LOAD_ITEMS) {
			return $this->stepLoadItems();
		}
	}

	protected function stepInit()
	{
		$filterData = $this->state->getStateParam('filter');

		if ($filterData == null) {
			$this->state->setStateParam('step', null);
			return;
		}

		$this->state->setStateParam('pageId', 0);
		$this->state->setStateParam('step', self::STEP_LOAD_LISTING);
	}

	protected function stepLoadListing()
	{
		$filterData = $this->state->getStateParam('filter');

		$pageId = $this->state->getStateParam('pageId');
		$parser = new Parser($filterData);

		$items = $parser->getPageItems($pageId);

		$pageId++;

		$this->state->setStateParam('pageId', $pageId);

	}

	protected function stepLoadItems()
	{
		;
	}
}