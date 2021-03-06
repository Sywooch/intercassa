<?php
/**
 * Created by PhpStorm.
 * User: VisioN
 * Date: 20.04.2015
 * Time: 11:42
 *
 * Виджет для работы с Интеркассой
 * пример:
 *  echo IntercassaWidget::widget([
 *       'nameForm' => 'nameForm',
 *       'classForm' => 'cssClassName',
 *       'classButton' => 'cssClassName',
 *       'classInput' => 'cssClassName',
 *       'nameButton' => 'nameButton',
 *       'labelButton' => 'Отправить',
 *       'amount' => 550,
 *       'description' => 'Пополнение баланса 2',
 *       'labelButton' => 'Оплатить',
 *       'config_fields' => [
 *       .....
 *      ]
 *  ]);
 */

namespace common\widgets;

use yii\base\Widget;
use yii\helpers\Html;
use vision\interkassa\exceptions\IntercassaException;

class IntercassaWidget extends Widget {

    public $amount;
    public $nameForm    = 'payment';
    public $description = null;
    public $nameButton  = 'send_pay';
    public $labelButton = 'Отправить';
    public $classForm   = 'payment';
    public $classInput  = 'form-control';
    public $classButton = 'btn btn-primary';

    public $accept_charset = 'UTF-8';
    public $config_fields;
    public $is_edit_amount = true;

    private $method      = 'post';


    /**
     * @return array
     * @throws IntercassaException
     */
    protected function getConfigFields() {
        $_config_fields = \Yii::$app->intercassa->configFields;
        $config_fields = array_merge(
            $_config_fields,
            isset($this->config_fields) && is_array($this->config_fields) ? $this->config_fields :[]
        );

        if(!$this->is_edit_amount && !isset($this->amount) && !$this->amount) {
            throw new IntercassaException('Не указано сумму транзакции');
        }

        $this->amount = $this->amount ? $this->amount : 0;
        $newPay = \Yii::$app->intercassa->newPay($this->amount);

        $config_fields['ik_pm_no'] = $newPay['id'];
        $config_fields['ik_am']    = $this->amount;

        if($this->description ) {
            $config_fields['ik_desc']  = $this->description;
        }

        if(isset($_config_fields->interAction) && $_config_fields->interAction) {
            $config_fields['ik_ia_u'] = \Yii::$app->urlManager ->createAbsoluteUrl($_config_fields->interAction);
        }

        return $config_fields;
    }


    /**
     * @return string
     * @throws IntercassaException
     */
    protected function getFields() {
        $contentFields = '';
        $configFields = $this->getConfigFields();
        foreach($configFields as $name => $val){
            if($val == null || $name == null) {
                continue;
            }
            if(in_array($name, ['ik_am']) && $this->is_edit_amount) {

                $params = ['class' => $this->classInput];
                if(!$this->is_edit_amount) {
                    $params['disabled'] = 1;
                }
                $contentFields .= Html::input('number', $name, $val, $params);
                if($this->is_edit_amount) {
                    continue;
                }
            }
            $contentFields .= Html::hiddenInput($name, $val);
        }
        return $contentFields;
    }


    protected function getMainContent() {
        $content = '';
        $content .= Html::beginForm(\Yii::$app->intercassa->actionUrl, $this->method, [
            'class'  => $this->classForm,
            'name'   => $this->nameForm,
            'accept-charset' =>$this->accept_charset
        ]);
        $content .= $this->getFields();
        $content .= Html::submitButton($this->labelButton, [
            'class' => $this->classButton,
            'name'  => $this->nameButton
        ]);
        $content .= Html::endForm();
        return $content;
    }


    public function run()
    {
        try{
            $return = $this->getMainContent();
        }catch(IntercassaException $e){
            $return = $e->getMessage();
        }
        return $return;
    }

} 