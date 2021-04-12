<?php


namespace HelperMail\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;
use function Couchbase\defaultDecoder;

class AmazonSES
{
    protected string $locale = 'pt-br';
    protected string $resource_path = __DIR__ . '/../Resources/';
    protected string $templateHtml = __DIR__ . '/../Resources/';
    protected array $translatable = [];
    protected array $layoutTranslatable = ['see_on_browser', 'contact_us', 'terms_of_use', 'privacy_policy',];
    protected string $templateFile = '';
    protected string $translateKey = '';
    protected Translator $translator;

    /**
     * Trigger emails
     * @param string $toEmail
     * @param string $toName
     * @param string $emailType
     * @param array $untranslatable
     * @return string
     * @throws Exception
     */
    public function dispatcher(string $toEmail, string $toName, string $emailType, array $untranslatable): string
    {
        $this->setTranslation();

        $email = $this->getEmail($emailType);

        /** Todo (Daniel) => add set properties by array from origins;
         * $this->setProperties(array $properties);
         */

        return $this->translator->trans('test.first');
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
     * @return string
     * @throws Exception
     */
    private function getEmail(string $emailType): string
    {
        /**
         * [X] -> get email for replace fields
         * [X] -> replace fields in file HTML
         * [] -> returns a changed file string
         */

        try {
            $layout = file_get_contents($this->resource_path . 'Templates/layout.html');
            $this->templateHtml = file_get_contents($this->resource_path . 'Templates/' . ucfirst($this->templateFile) . '/' . $emailType . '.html');
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }

        $this->translatedFields($layout, $emailType);

        var_dump($this->templateHtml);
        die();
    }

    /**
     * @param string $layout
     * @param string $emailType
     */
    private function translatedFields(string $layout, string $emailType)
    {
        $this->replaceLayout($layout);

        foreach ($this->translatable as $key => $value) {
            if (is_array($value)) {
                $attributes = $value;
                $field = $key;
            } else {
                $attributes = [];
                $field = $value;
            }

            $textTranslated = $this->translator->trans($this->translateKey . '.' . $emailType . '.' . $field, $attributes);

            $this->templateHtml = str_replace('{{' . strtoupper($field) . '}}', $textTranslated, $this->templateHtml);
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
}