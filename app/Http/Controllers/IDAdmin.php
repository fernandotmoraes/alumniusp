<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class IDAdmin
{
    var $client;

    function __construct()
    {
        $this->client = $this->login();
    }

    private function login()
    {
        $login = getenv('IDADMIN_LOGIN');
        $pass = getenv('IDADMIN_PASSWORD');
        
        $cookies = new CookieJar();
        $client = new Client(['cookies' => $cookies]);
        $response = $client->get("https://id-admin.internuvem.usp.br/portal/");

        # get jsessionid
        $cookie = $cookies->getCookieByName("JSESSIONID");
        $jsessionid = $cookie->toArray()['Value'];

        # generate SAML request
        $response = $client->post("https://idpcafe.usp.br/idp/profile/SAML2/Redirect/SSO;jsessionid=$jsessionid?execution=e1s1", [
            'form_params' => [
                'j_username' => $login,
                'j_password' => $pass,
                '_eventId_proceed' => '',
            ]
        ]);

        # extract SAML data from response
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getBody());
        $xpath = new \DOMXpath($dom);

        $relaystate = $xpath->query("//html/body/form/div/input[@name='RelayState']/@value")[0]->textContent;
        $samlresponse = $xpath->query("//html/body/form/div/input[@name='SAMLResponse']/@value")[0]->textContent;

        # SAML authentication
        $response = $client->post("https://id-admin.internuvem.usp.br/Shibboleth.sso/SAML2/POST", [
            'form_params' => [
                'RelayState' => $relaystate,
                'SAMLResponse' => $samlresponse,
            ]
        ]);

        # Allow id-admin to access user attributes
        $response = $client->post("https://idpcafe.usp.br/idp/profile/SAML2/Redirect/SSO?execution=e1s2;jsessionid=$jsessionid", [
            'form_params' => [
                '_shib_idp_consentOptions' => '_shib_idp_globalConsent',
                '_eventId_proceed' => 'Aceitar',
            ]
        ]);

        return $client;
    }

    function get_vinculos($nusp)
    {
        $response = $this->client->get("https://id-admin.internuvem.usp.br/sybase/json/$nusp/vinculos/");
        return $response->getBody();
    }

}

?>
