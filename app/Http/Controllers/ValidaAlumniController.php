<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Client;

use App\Http\Controllers\IDAdmin;

class ValidaAlumniController
{
    function validaAlumni(int $id)
    {
        if ($this->get_idadmin($id)) {
            return response(
                array(
                    'status' => '200',
                    'message' => 'Pode acessar o programa Alumni USP'
                ),
                200
            );
        }

        if ($this->get_alumni($id)) {
            return response(
                array(
                    'status' => '200',
                    'message' => 'Pode acessar o programa Alumni USP'
                ),
                200
            );
        }

        return response(
            array(
                'status' => '404',
                'message' => 'Usuário não encontrado'
            ),
            404
        );
    }

    function get_idadmin($nusp)
    {
        $idmail = new IDAdmin();
        $client = $idmail->get_vinculos($nusp);

        $vinculos = json_decode($client);

        if (sizeof($vinculos->result) == 0) {
            return false;
        }
        foreach ($vinculos->result as $vinculo) {
            if ($vinculo->tipvin == 'ALUMNI') {
                return true;
            }
        };

        return false;
    }

    function get_alumni($cpf): bool
    {
        $cookies = new CookieJar();
        $client = new Client(['cookies' => $cookies]);
        $response = $client->post("https://uspdigital.usp.br/alumni/index.php", [
            'form_params' => [
                'alumnicode' => $cpf,
            ]
        ]);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($response->getBody());
        $xpath = new \DOMXpath($dom);

        $status = $xpath->query("//*[contains(text(), 'Não foi encontrado')]")[0];

        return $status == null ? true : false;
    }
}
