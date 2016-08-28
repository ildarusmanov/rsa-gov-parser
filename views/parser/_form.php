<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

?>

<?php $form = ActiveForm::begin(['id' => 'parser-filter-form']); ?>

    <?= $form->field($model, 'number') ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'vDateFrom') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'vDateTo') ?>
        </div>
    </div>


    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'sDateFrom') ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'sDateTo') ?>
        </div>
    </div>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'producer') ?>

    <?= $form->field($model, 'product') ?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
    </div>

<?php ActiveForm::end(); ?>
