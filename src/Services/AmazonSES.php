<?php


namespace HelperMail\Services;

use Illuminate\Support\Facades\Cache;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Translator;

class AmazonSES
{
    protected string $locale = 'pt-br';
    protected Translator $translator;

    /**
     * @param string $toName
     */
    public function dispatcher(string $toEmail, string $toName): string
    {
        $this->setTranslation();

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

        $file = require __DIR__ . '/../Resources/translations/' . $this->locale . '/mail.php';

        $this->translator->addLoader('array',  new ArrayLoader());
        $this->translator->addResource('array', $file, $this->locale);
    }
}