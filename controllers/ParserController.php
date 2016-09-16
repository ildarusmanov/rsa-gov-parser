<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\services\ParserManager;
use app\models\ParserFilterForm;

class ParserController extends Controller
{
    public function actionIndex()
    {
        $manager = new ParserManager();
        if ($manager->isLoading() || $manager->isFinished()) {
            return $this->redirect(['view']);
        }

        $request = \Yii::$app->request;

        $model = new ParserFilterForm();

        if ($request->isPost && $model->load($request->post())) {
            $cookieData = $model->getCookieData();
            (new ParserManager())->start($cookieData);

            return $this->redirect(['view']);
        }

        return $this->render('index', ['model' => $model]);
    }

    public function actionView()
    {
        $manager = new ParserManager();

        $isLoading = $manager->isLoading();
        $isFinished = $manager->isFinished();
        $stepTitle = $manager->getStepTitle();

        return $this->render('view', [
            'isLoading' => $isLoading,
            'isFinished' => $isFinished,
            'stepTitle' => $stepTitle,
        ]);
    }

    public function actionStop()
    {
        $manager = new ParserManager();

        if ($manager->isLoading()
            || $manager->isFinished()
        ) {
            $manager->stop();
        }

        return $this->redirect(['index']);
    }

    public function actionRun()
    {
        (new ParserManager())->run();
    }
}
