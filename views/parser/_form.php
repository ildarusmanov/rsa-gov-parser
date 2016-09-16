<?php
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use dosamigos\datepicker\DatePicker;

?>

<?php $form = ActiveForm::begin(['id' => 'parser-filter-form']); ?>

    <?= $form->field($model, 'number') ?>

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'vDateFrom')
                ->widget(
                    DatePicker::className(), [
                        'inline' => false,
                        'clientOptions' => [
                            'autoclose' => true,
                            'format' => 'dd.mm.yyyy',
                        ],
                ]); ?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'vDateTo')
                ->widget(
                    DatePicker::className(), [
                        'inline' => false,
                        'clientOptions' => [
                            'autoclose' => true,
                            'format' => 'dd.mm.yyyy',
                        ],
                ]);?>
        </div>
    </div>


    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'sDateFrom')
                ->widget(
                    DatePicker::className(), [
                        'inline' => false,
                        'clientOptions' => [
                            'autoclose' => true,
                            'format' => 'dd.mm.yyyy',
                        ],
                ]);?>
        </div>
        <div class="col-md-6">
            <?= $form->field($model, 'sDateTo')
                ->widget(
                    DatePicker::className(), [
                        'inline' => false,
                        'clientOptions' => [
                            'autoclose' => true,
                            'format' => 'dd.mm.yyyy',
                        ],
                ]);?>
        </div>
    </div>

    <?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'producer') ?>

    <?= $form->field($model, 'product') ?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary', 'name' => 'contact-button']) ?>
    </div>

<?php ActiveForm::end(); ?>
