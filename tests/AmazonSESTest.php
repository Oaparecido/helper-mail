<?php

use HelperMail\Services\Mailer\ActionsMailing;

test('View HTML file', function () {
    $settings = [
        'to_name' => 'mongodb name',
        'to_email' => 'daniel.aparecido@maquinadobem.com',
        'link' => 'mongodb link',
        'action_title' => 'ação mongodb',
    ];

    $mailing = new ActionsMailing();

    $mailer = $mailing->actionApproved($settings['to_name'], $settings['to_email'], $settings['link'], $settings['action_title']);

    expect($mailer)->toBe(['status' => true, 'message' => 'e-mail enviado com sucesso']);
});