<?php
namespace app\commands;

use yii\console\Controller;
use app\services\ParserManager;

class ParserController extends Controller
{
    public function actionRun()
    {
    	(new ParserManager())->run();
    }
}
