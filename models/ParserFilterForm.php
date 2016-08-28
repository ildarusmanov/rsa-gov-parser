<?php
namespace app\models;

use Yii;
use yii\base\Model;

class ParserFilterForm extends Model
{
    public $number;
    public $vDateFrom;
    public $vDateTo;
    public $sDateFrom;
    public $sDateTo;
    public $name;
    public $producer;
    public $product;

    public function rules()
    {
        return [
            [['number', 'name', 'producer', 'product'], 'string'],
            [['vDateFrom', 'vDateTo', 'sDateFrom', 'sDateTo'], 'date', 'format' => 'd.m.Y'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'number' => 'Номер',
            'vDateFrom' => 'Выдан от',
            'vDateTo' => 'Выдан до',
            'sDateFrom' => 'Срок действия от',
            'sDateTo' => 'Срок действия до',
            'name' => 'Заявитель',
            'producer' => 'Изготовитель',
            'product' => 'Продукция',
        ];
    }

    public function getCookieData()
    {
        return [
            'input_1' => $this->number,
            'input_2_begin' => $this->vDateFrom,
            'input_2_end' => $this->vDateTo,
            'input_3_begin' => $this->sDateFrom,
            'input_3_end' => $this->sDateTo,
            'input_4' => $this->name,
            'input_5' => $this->producer,
            'input_11' => $this->product,
        ];
    }
}
