<?php
/**
 * Created by PhpStorm.
 * User: VisioN
 * Date: 22.04.2015
 * Time: 11:56
 *
 * В случае успешного прохождения платежа
 * выполнится логика в методе IntercassaAction::successPay()
 * метод должен вернуть false|true
 */

namespace vision\interkassa\actions;

use \yii\base\Action;
use vision\interkassa\exceptions\IntercassaException;


class IntercassaAction extends Action {


    /**
     * В данном методе прописываем логику в случае удачного платежа,
     * в аргументах передаются данные платежа,
     *
     * @param $model_intercassa
     * @return bool
     */
    public function successPay($model_intercassa){
        if(is_callable(\Yii::$app->intercassa->successPay)){
            return call_user_func (\Yii::$app->intercassa->successPay, $model_intercassa);
        }
        return false;
    }


    /**
     * @throws \yii\db\Exception
     */
    public function run()
    {
        $response = \Yii::$app->response;
        $connection = \Yii::$app->db;

        $status_answer = 501;

        try {
            $transaction = $connection->beginTransaction();
            $model_intercassa = $this->updatePay();

            if($model_intercassa && $model_intercassa->invoice_state == 'success') {
                $this->runBusinessLogic($model_intercassa);
                $status_answer = 200;
                $response->content = 'Ok';
            }
        } catch(IntercassaException $e) {
            $transaction->rollback();
        }

        $response->statusCode = $status_answer;
        $response->send();
    }


    /**
     * @param $model_intercassa
     * @throws IntercassaException
     */
    protected function runBusinessLogic($model_intercassa) {
        if(!$this->successPay($model_intercassa)) {
            throw new IntercassaException('error in business logic');
        }
    }


    /**
     * @return mixed
     */
    protected function updatePay() {
        $dataPost = \Yii::$app->request->post();
        $userIp   = \Yii::$app->request->userIP;
        return \Yii::$app->intercassa->updatePay($userIp, $dataPost);

    }
} 