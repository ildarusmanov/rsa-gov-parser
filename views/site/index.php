<?php
use yii\helpers\Url;

$this->title = 'Parser';
?>
<div class="site-index">

    <div class="jumbotron">
        <h1>Добро пожаловать</h1>

        <p><a class="btn btn-lg btn-success" href="<?= Url::toRoute(['/parser']) ?>">Перейти к парсингу</a></p>
    </div>
</div>
