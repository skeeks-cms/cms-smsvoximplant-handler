<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\sms\smsvoximplant;

use skeeks\cms\models\CmsSitePhone;
use skeeks\cms\models\CmsSmsMessage;
use skeeks\cms\sms\SmsHandler;
use skeeks\yii2\form\fields\FieldSet;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;

/**
 *
 * @see https://voximplant.ru/docs/guides/sms/sending
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class SmsvoximplantHandler extends SmsHandler
{
    public $api_key = "";
    public $account_id = "";
    public $src_number = "";

    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => "Voximplant",
        ]);
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['api_key', 'src_number', 'account_id'], 'required'],
            [['account_id'], 'integer'],
            [['api_key'], 'string'],
            [['src_number'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'api_key' => "API ключ",
            'account_id' => "ID аккаунта",
            'src_number'  => "Номер отправителя",

        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [

        ]);
    }


    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основные',
                'fields' => [
                    'account_id',
                    'api_key',
                    'src_number',
                ],
            ],
        ];
    }


    /**
     * @see https://voximplant.ru/docs/guides/sms/sending
     *
     * curl "https://api.voximplant.com/platform_api/A2PSendSms?api_key=API_KEY&account_id=1&src_number=447443332211&dst_numbers=447443332212;447443332213&text=Test%20message"
     * 
     * @param      $phone
     * @param      $text
     * @param      $sender
     * @return $message_id
     */
    public function send($phone, $text, $sender = null)
    {
        $phone = CmsSitePhone::onlyNumber($phone);
        $phone = str_replace("+", "", $phone);
            
        /*$queryString = http_build_query([
            'api_key'    => $this->api_key,
            'account_id'    => $this->account_id,
            'src_number'    => $this->src_number,
            'text'   => $text,
            'dst_numbers'   => $phone,
        ]);*/

        $queryString = http_build_query([
            'api_key'    => $this->api_key,
            'account_id'    => $this->account_id,
            'source'    => $this->src_number,
            'sms_body'   => $text,
            'destination'   => $phone,
        ]);

        /*$url = 'https://api.voximplant.com/platform_api/A2PSendSms?'.$queryString;*/
        $url = 'https://api.voximplant.com/platform_api/SendSmsMessage?'.$queryString;

        $client = new Client();
        $response = $client
            ->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setUrl($url)
            ->send();

        if (!$response->isOk) {
            throw new Exception($response->content);
        }

        return $response->data;
    }

    public function sendMessage(CmsSmsMessage $cmsSmsMessage)
    {
        try {
            $data = $this->send($cmsSmsMessage->phone, $cmsSmsMessage->message);

            if ($errorData = ArrayHelper::getValue($data, 'error')) {
                $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
                $cmsSmsMessage->error_message = Json::encode($errorData);
                return;
            }
            
            if (!ArrayHelper::getValue($data, 'result')) {
                $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
                $cmsSmsMessage->error_message = Json::encode(ArrayHelper::getValue($data, 'failed'));
                return;
            }

            $message_id = (string) ArrayHelper::getValue($data, 'message_id');

            if ($message_id) {
                $cmsSmsMessage->status = CmsSmsMessage::STATUS_DELIVERED;
                $cmsSmsMessage->provider_message_id = $message_id;
            } else {
                $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
                $cmsSmsMessage->error_message = "Сообщение не отправлено";
            }
        } catch (\Exception $exception) {
            $cmsSmsMessage->status = CmsSmsMessage::STATUS_ERROR;
            $cmsSmsMessage->error_message = $exception->getMessage();
            return;
        }
        
    }

    /**
     * @param $message_id
     * @return mixed
     */
    public function status($message_id)
    {

    }
    
}