<?php


namespace HelperMail\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class AmazonSES
{
    /**
     * Set locale to translation e-mail
     * @var string
     */
    protected string $locale = 'pt-br';

    /**
     * Template HTML to sender e-mail | This var is changed according to the mailing class
     * @var string
     */
    protected string $templateHtml = '';

    /**
     * String to search file from the translation
     * @var string
     */
    protected string $templateFile = '';

    /**
     * Key to translate the template, search in file mail.php into translations path
     * @var string
     */
    protected string $translateKey = '';

    /**
     * Fields translatable from layout HTML
     * @var array|string[]
     */
    protected array $layoutTranslatable = ['see_on_browser', 'contact_us', 'terms_of_use', 'privacy_policy',];

    /**
     * Fields into mailing class, that is translatable into file HTML
     * @var array
     */
    protected array $translatable = [];

    /**
     * Fields that into origins translatable
     * @var array
     */
    protected array  $mailerSettings = [];

    /**
     * Path to resources
     * @var string
     */
    private string   $resource_path = __DIR__ . '/../Resources/';

    /**
     * Name default from sender
     * @var string
     */
    private string   $fromName = 'Transform Robot';

    /**
     * e-mail default to sender
     * @var string
     */
    private string   $senderDefault = 'no-replay@transform.click';

    /**
     * e-mail subject
     * @var string
     */
    private string   $subject = '';

    /**
     * @var array
     */
    private array    $filesToUnlink = [];

    /**
     * @var bool
     */
    private bool     $isUnlink = false;

    /**
     * Variable to translate files
     * @var Translator
     */
    protected Translator $translator;

    /**
     * Trigger emails
     * @param string $toEmail
     * @param string $toName
     * @param string $emailType
     * @param array $untranslatable
     * @param array $attachments
     * @return array
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws Exception
     */
    public function dispatcher(string $toEmail, string $toName, string $emailType, array $untranslatable, $attachments = []): array
    {
        /**
         * Todo (Daniel) => add set properties by array from origins;
         * $this->setProperties(array $properties);
         */
        $this->setTranslation();

        $this->setTemplate($emailType, $untranslatable);

        /**
         * Todo (Daniel) => add fromName by origins
         */
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Port = 2525;
        $mail->Username = '8b9256a7d74c85';
        $mail->Password = '986bf3e32695cd';
        $mail->setFrom($this->senderDefault, $this->fromName);
        $mail->addReplyTo($this->senderDefault, $this->fromName);
        $mail->addAddress('daniel.aparecido@maquinadobem.com', 'Daniel Aparecido');
        $mail->Subject = $this->subject;
        $mail->CharSet = 'UTF-8';
        $mail->msgHTML($this->templateHtml);

        if (!$mail->send())
            return ['status' => false, 'error' => $mail->ErrorInfo];

        //$this->setAttachments($attachments, $mail);

        return ['status' => true, 'message' => 'e-mail enviado com sucesso'];
    }

    /**
     * Setting the translation configuration of the file where the translation comes from
     */
    private function setTranslation(): void
    {
        $this->translator = new Translator($this->locale);

        $file = require $this->resource_path . 'translations/' . $this->locale . '/mail.php';

        $this->translator->addLoader('array', new ArrayLoader());
        $this->translator->addResource('array', $file, $this->locale);
    }

    /**
     * @param string $emailType
     * @param array $untranslatable
     * @throws Exception
     */
    private function setTemplate(string $emailType, array $untranslatable)
    {
        try {
            $layout = file_get_contents($this->resource_path . 'Templates/layout.html');
            $this->templateHtml = file_get_contents($this->resource_path . 'Templates/' . ucfirst($this->templateFile) . '/' . $emailType . '.html');
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $this->translatedFields($layout, $emailType, $untranslatable);

    }

    /**
     * @param string $layout
     * @param string $emailType
     * @param array $untranslatable
     */
    private function translatedFields(string $layout, string $emailType, array $untranslatable)
    {
        $this->replaceLayout($layout);
        $this->setSubject($emailType, $untranslatable);

        foreach ($this->translatable as $key => $value) {
            if (is_array($value)) {
                $attributes = $value;
                $field = $key;
            } else {
                $attributes = [];
                $field = $value;
            }

            $text_translated = $this->translator->trans($this->translateKey . '.' . $emailType . '.' . $field, $attributes);

            $this->templateHtml = str_replace('{{' . strtoupper($field) . '}}', $text_translated, $this->templateHtml);
        }

        if (isset($untranslatable) && !empty($untranslatable)) {
            $untranslatable['icon_bg_ligth'] = (isset($untranslatable['icon_bg_ligth'])) ? $untranslatable['icon_bg_ligth'] : 'iconBgLigth';

            foreach ($untranslatable as $key => $value)
                $this->templateHtml = str_replace('{{' . strtoupper($key) . '}}', $value, $this->templateHtml);
        }
    }

    /**
     * @param string $layout
     */
    private function replaceLayout(string $layout)
    {
        /**
         * Todo (Daniel) => $url_defaul must be captured from origins or from a constant defined
         */
        $url_default = (defined('TBR_URL_DEFAULT')) ? TBR_URL_DEFAULT : 'https://transformabrasil.com.br';

        $this->layoutTranslatable['url'] = [
            'url_contact_us' => $this->translator->trans('layout.url_contact_us', ['platform_url' => $url_default]),
            'url_terms_of_use' => $this->translator->trans('layout.url_terms_of_use', ['platform_url' => $url_default]),
            'url_privacy_policy' => $this->translator->trans('layout.url_privacy_policy', ['platform_url' => $url_default]),
        ];

        $this->templateHtml = str_replace('{{CONTENT}}', $this->templateHtml, $layout);

        foreach ($this->layoutTranslatable as $field) {
            if (is_array($field))
                foreach ($field as $additional => $value_additional)
                    $this->templateHtml = str_replace('{{' . strtoupper($additional) . '}}', $value_additional, $this->templateHtml);
            else
                $this->templateHtml = str_replace('{{' . strtoupper($field) . '}}', $this->translator->trans('layout.' . $field), $this->templateHtml);
        }
    }

    /**
     * @param string $emailType
     * @param array $untranslatable
     */
    private function setSubject(string $emailType, array $untranslatable)
    {
        if (isset($untranslatable['name']))
            $this->subject = $this->translator->trans($this->translateKey . '.' . $emailType . '.subject', ['name' => $untranslatable['name']]);
        else
            $this->subject = $this->translator->trans($this->translateKey . '.' . $emailType. '.subject');
    }

//    private function setAttachments(array $attachments, PHPMailer $mail): void
//    {
//        if (isset($attachments) && !empty($attachments)) {
//            foreach ($attachments as $attachment) {
//                $fileName = basename($attachment);
//                if (substr($attachment, 0, 4) === 'http') {
//                    $this->isUnlink = true;
//                    $attachment = $this->tempLoadFile($attachment, $fileName);
//                    $this->filesToUnlink[] = $attachment;
//                }
//                $mail->addAttachment($attachment, $fileName);
//            }
//        }
//    }
//
//    private static function getObj()
//    {
//        return $SesClient = new SesClient([
//            'profile' => 'default',
//            'version' => '2010-12-01',
//            'region' => env('AWS_SES_REGION', 'us-east-1')
//        ]);
//    }
//    private function setSocialNetworks()
//    {
//        if (isset($this->mailerSettings['social_networks']) && !empty($this->mailerSettings['social_networks']))
//            foreach ($this->mailerSettings['social_networks'] as $social_network) {
//                $this->templateHtml = str_replace('{{' . strtoupper($social_network['network']) . '_URL}}', $social_network['url'], $this->templateHtml);
//                $this->templateHtml = str_replace('{{' . strtoupper($social_network['network']) . '_ICON}}', $social_network['icon'], $this->templateHtml);
//            }
//    }
//
//    private function setSettingsLayout()
//    {
//        if (isset($this->mailerSettings['title'], $this->mailerSettings['logo']) && !empty($this->mailerSettings['title']) && !empty($this->mailerSettings['logo']))
//            foreach ($this->mailerSettings as $field => $value)
//                if (gettype($value) === 'string')
//                    $this->templateHtml = str_replace('{{' . strtoupper($field) . '}}', $value, $this->templateHtml);
//    }
//
//    private function setColors()
//    {
//        if (isset($this->mailerSettings['colors']) && !empty($this->mailerSettings['colors']))
//            foreach ($this->mailerSettings['colors'] as $field => $value) {
//                $this->templateHtml = str_replace('"{{' . strtoupper($field) . '}}"', $value, $this->templateHtml);
//            }
//    }
}