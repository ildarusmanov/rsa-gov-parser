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

    /*
    public function actionParse()
    {
        $url = __DIR__ . '/page.html';
        $content = file_get_contents($url);
        $content = iconv('cp1251', 'utf-8', $content);
        $content = preg_replace('/<head>(.*)<\/head>/siU', '<head><meta http-equiv="content-type" content="text/html; charset=utf-8" /></head>', $content);

        $doc = new \DOMDocument();
        @$doc->loadHTML($content);
        $xpath = new \DOMXpath($doc);

        $steps = $xpath->query('//div[contains(@class, "step-content")]');//*[@class="step-content"]');

        $stepElements = [];

        $types = [
            'Label' => 'type-label',
            'Group' => 'type-group_element',
            'Object' => 'type-object',
            'Attribute' => 'type-attribute',
        ];

        $table = [];
        $currentLabel = null;

        foreach ($steps as $i => $step) {
            $stepElements = $xpath->query('.//div[contains(@class, "fsa-element")]', $step);

            foreach ($stepElements as $el) {
                $class = $el->attributes->getNamedItem('class')->value;

                foreach ($types as $typeName => $typeClass) {
                    if (strpos($class, $typeClass) !== FALSE) {
                        $type = $typeName;
                        continue;
                    }
                }

                if ($type == 'Label') {
                    $currentLabel = trim($el->textContent);
                    continue;
                }

                if ($currentLabel == null) {
                    continue;
                }

                if ($type == 'Group') {
                    $table[$currentLabel][] = trim($el->textContent);
                }

                if ($type == 'Object') {
                    $type = 'Attribute';
                }

                if ($type == 'Attribute') {
                    $names = $xpath->query('.//div[contains(@class, "form-left-col")]', $el);
                    $values = $xpath->query('.//div[contains(@class, "form-right-col")]', $el);

                    $attributes = [];

                    foreach ($names as $i => $name) {
                        $n = trim($name->textContent);
                        $v = $values->item($i)->textContent;

                        if (empty($n) || empty($v)) {
                            continue;
                        }

                        $attributes[$n] = $v;
                    }

                    $table[$currentLabel][] = $attributes;
                }
            }
        }

        print_r($table);


    }*/
}
