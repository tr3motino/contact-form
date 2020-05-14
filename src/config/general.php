<?php

return [
    '*' => [
        'useProjectConfigFile' => true,
    ],

    return [
        'ssoUrl' => $env === 'prod'
          ? 'https://sso.comjoo.com/auth/realms/comjoo-hub/protocol/openid-connect/token'
          : 'https://sso.comjoo.net/auth/realms/comjoo-hub/protocol/openid-connect/token',
        'ssoCredentials' => [
          'form_params' => [
               'client_id' => 'sanecum-order-form',
               'grant_type' => 'password',
               'client_secret' => (($env === 'prod')
                    ? '49e76eb4-cd11-425f-bef6-6633f2069807'
                    : 'a0826724-365b-4a47-badb-88ff96e932a1'),
               'scope=openid',
               'username' => getenv('JOOBN'),
               'password' => getenv('JOOPW'),
          ]],
        'pmApiRegistrationUrl' => (($env === 'prod')
          ? "https://pm-doc.comjoo.com/onboarding/forCoronaRecipe"
          : (($env === 'dev')
               ? "https://pm-doc.api.comjoo.net/onboarding/forCoronaRecipe"
               # in docker the clientIp is the host-adress where the ts-node srv is running
               : "http://host.docker.internal:3335/onboarding/forCoronaRecipe"))
    ];

];
