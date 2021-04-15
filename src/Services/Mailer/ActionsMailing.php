<?php


namespace HelperMail\Services\Mailer;


use HelperMail\Services\AmazonSES;
use PHPMailer\PHPMailer\Exception;

class ActionsMailing extends AmazonSES
{
    protected string $templateFile = 'actions';
    protected string $translateKey = 'actions-mailing';
    protected array  $mailerSettings = [];

    public function __construct(array $origins)
    {
        $this->mailerSettings = $origins;
    }

    /**
     * Function to send e-mail for actions approved by administrator
     * @param $toName
     * @param $toEmail
     * @param $link
     * @param $action_title
     * @return array
     * @throws Exception
     */
    public function actionApproved($toName, $toEmail, $link, $action_title): array
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