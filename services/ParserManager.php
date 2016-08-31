<?php
namespace app\services;

use app\models\ParserState;

class ParserManager
{
	const STEP_INIT = 100;
	const STEP_LOAD_LISTING = 200;
	const STEP_LOAD_ITEMS = 300;
	const STEP_FINISHED = 400;

	const LOCK_FILE_PATH = __DIR__ . '/../runtime/state/running.state';

	protected $state;

	public function start($data)
	{
		$this->loadState();

		$this->state->setStateParam('filter', $data);
		$this->state->setStateParam('step', self::STEP_INIT);

		$this->unlock();
	}

	public function isLoading()
	{
		$this->loadState();

		$step = $this->state->getStateParam('step');

		if ($step == null || $step == self::STEP_FINISHED) {
			return false;
		}

		return true;
	}

	public function run()
	{
		if ($this->isLocked()) {
			return;
		}

		$this->lock();

		$this->loadState();

		$step = $this->state->getStateParam('step');

		if ($step == null || $step == self::STEP_FINISHED) {
			return;
		}

		if ($step == self::STEP_INIT) {
			$this->stepInit();
		} elseif ($step == self::STEP_LOAD_LISTING) {
			$this->stepLoadListing();
		} elseif ($step == self::STEP_LOAD_ITEMS) {
			$this->stepLoadItems();
		}

		$this->unlock();
	}

	protected function loadState()
	{
		$this->state = new ParserState();

		return $this;
	}

	protected function stepInit()
	{
		$filterData = $this->state->getStateParam('filter');

		if ($filterData == null) {
			$this->state->setStateParam('step', null);
			return;
		}

		(new ParserWriter)->resetFile();

		$this->state->setStateParam('pageId', 0);
		$this->state->setStateParam('step', self::STEP_LOAD_LISTING);
	}

	protected function stepLoadListing()
	{
		$items = $this->state->getStateParam('items');

		if ($items == null) {
			$items = [];
		}

		$filterData = $this->state->getStateParam('filter');

		$pageId = $this->state->getStateParam('pageId');

		$parser = new Parser($filterData);

		$newItems = $parser->getItemsByPageId($pageId);

		if (sizeof($newItems) == 0) {
			$this->state->setStateParam('step', self::STEP_LOAD_ITEMS);
			$this->state->setStateParam('pageId', 0);
			return;
		}

		$items = array_merge($items, $newItems);

		$pageId++;

		$this->state->setStateParam('pageId', $pageId);
		$this->state->setStateParam('items', $items);
	}

	protected function stepLoadItems()
	{
		$items = $this->state->getStateParam('items');

		$itemId = array_pop($items);

		$this->parseItem($itemId);

		$this->state->setStateParam('items', $items);

		if (sizeof($items) == 0) {
			$this->state->setStateParam('step', self::STEP_FINISHED);
			return;
		}
	}

	public function isLocked()
	{
		return file_exists(self::LOCK_FILE_PATH);
	}

	public function lock()
	{
		sleep(1);

		touch(self::LOCK_FILE_PATH);
	}

	public function unlock()
	{
		sleep(1);

		if ($this->isLocked()) {
			unlink(self::LOCK_FILE_PATH);
		}
	}

	protected function parseItem($itemId)
	{
		$parsedData = (new Parser())->getViewPage($itemId);

		$converter = new ItemDataConverter($parseData);

		(new ParserWriter())->write($converter->getData());
	}
}