<?php
namespace app\commands;

use yii\console\Controller;
use app\services\ParserManager;

class ParserController extends Controller
{
    public function actionRun()
    {
    	for ($i = 0; $i < 100; $i++) {
    		(new ParserManager())->run();
    	}
    }
}
