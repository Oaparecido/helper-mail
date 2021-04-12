<?php


namespace HelperMail\Services\Mailer;


use HelperMail\Services\AmazonSES;

class ActionsMailing extends AmazonSES
{
    protected string $templateFile = 'actions';
    protected string $translateKey = 'actions-mailing';

    /**
     * Function to send e-mail for actions approved by administrator
     * @param $toName
     * @param $toEmail
     * @param $link
     * @param $action_title
     * @param $entity
     * @return string
     */
    public function actionApproved($toName, $toEmail, $link, $action_title)
    {
        $untranslatable = ['name' => $toName, 'link' => $link];
        $this->translatable  = [
            'greeting',
            'message_approve_default' => ['action_title' => $action_title],
            'message_invite_volunteers',
            'message_attention_notification',
            'cta',
            'button_label'
        ];
//        $this->entity_artisan = $entity;

        return $this->dispatcher($toEmail, $toName, __FUNCTION__, $untranslatable);
    }
}