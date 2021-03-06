<?php
namespace app\services;

use app\models\ParserState;

class ParserManager
{
    const STEP_INIT = 100;
    const STEP_LOAD_LISTING = 200;
    const STEP_LOAD_ITEMS = 300;
    const STEP_FINISHED = 400;

    const LOCK_FILE_PATH = '/../runtime/state/running.state';

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

    public function isFinished()
    {
        $this->loadState();

        $step = $this->state->getStateParam('step');

        return $step == self::STEP_FINISHED;
    }

    public function run()
    {
        $this->log('Start parser...');

        if ($this->isLocked()) {
            return;
        }

        $this->lock();

        try {
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
        } catch (\Exception $e) {
            \Yii::trace($e->getMessage());
        }

        $this->unlock();
    }

    public function getStepTitle()
    {
        $stepId = $this->getStepId();

        if ($stepId == null) {
            return;
        }

        $list = $this->getStepsTitles();

        if (isset($list[$stepId])) {
            return $list[$stepId];
        }

        return;
    }

    protected function getStepId()
    {
        $this->loadState();

        return $this->state->getStateParam('step');
    }

    protected function getStepsTitles()
    {
        return [
            self::STEP_INIT => 'Инициализация',
            self::STEP_LOAD_LISTING => 'Загрузка списка',
            self::STEP_LOAD_ITEMS => 'Обработка полученных данных',
            self::STEP_FINISHED => 'Готово',
        ];
    }

    protected function loadState()
    {
        $this->log('Load state');

        $this->state = new ParserState();

        return $this;
    }

    protected function stepInit()
    {
        $this->log('Initial step');

        $filterData = $this->state->getStateParam('filter');

        if ($filterData == null) {
            $this->state->setStateParam('step', null);

            return;
        }

        (new ParserWriter())->resetFile();

        $this->state->setStateParam('pageId', 0);
        $this->state->setStateParam('step', self::STEP_LOAD_LISTING);
    }

    protected function stepLoadListing()
    {
        $this->log('Load listing');

        $items = $this->state->getStateParam('items');

        if ($items == null) {
            $items = [];
        }

        $filterData = $this->state->getStateParam('filter');

        $pageId = $this->state->getStateParam('pageId');

        $this->log('Load page #' . $pageId);

        $parser = new Parser($filterData);

        $newItems = $parser->getItemsByPageId($pageId);

        $this->log(sizeof($newItems) . ' items parsed');

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
        $this->log('Load items');

        $items = $this->state->getStateParam('items');

        $itemId = array_pop($items);

        $this->log('Parse item #' . $itemId);

        $itemParsed = false;
        for ($i = 0; $i < 3; $i++) {
            if ($this->parseItem($itemId)) {
                $itemParsed = true;
                break;
            }
        }

        if (!$itemParsed) {
            $this->parseFailed($itemId);
        }

        $this->state->setStateParam('items', $items);

        if (sizeof($items) == 0) {
            $this->state->setStateParam('step', self::STEP_FINISHED);

            return;
        }
    }

    public function stop()
    {
        $this->state->setStateParam('step', null);
        $this->state->setStateParam('items', []);
        $this->state->setStateParam('pageId', 0);
        $this->unlock();
    }

    public function isLocked()
    {
        $this->log('Check is locked?');

        return file_exists(__DIR__ . self::LOCK_FILE_PATH);
    }

    public function lock()
    {
        $this->log('Locks');

        sleep(1);

        touch(__DIR__ . self::LOCK_FILE_PATH);
    }

    public function unlock()
    {
        $this->log('Unlock');

        sleep(1);

        if ($this->isLocked()) {
            unlink(__DIR__ . self::LOCK_FILE_PATH);
        }
    }

    protected function parseFailed($itemId)
    {
        $this->log('Parse failed item id = ' . $itemId);

        $data = [(new Parser())->getItemUrl($itemId)];

        (new ParserWriter())->write($data);
    }

    protected function parseItem($itemId)
    {
        if (!$itemId) {
            return;
        }

        $this->log('Parse item id = ' . $itemId);

        $parsedData = (new Parser())->getViewPage($itemId);

        if (sizeof($parsedData) == 0) {
            return;
        }

        $convertedData = (new ItemDataConverter($parsedData))->getData();

        if (sizeof($convertedData) == 0) {
            return;
        }

        (new ParserWriter())->write($convertedData);

        return true;
    }

    protected function log($msg, $type = 'info')
    {
        \Yii::trace('[' . $type . ']: ' . $msg);
    }
}
