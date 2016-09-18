<?php
namespace app\commands;

use yii\console\Controller;
use app\services\ParserManager;

class ParserController extends Controller
{
    public function actionRun()
    {
    	$i = 0;

    	while (true) {
	    	$manager = new ParserManager();

	    	if (!$manager->isLoading() || $i > 10) {
	    		return;
	    	}

	    	$manager->run(); 

	    	$i++;   		
    	}
    }
}
