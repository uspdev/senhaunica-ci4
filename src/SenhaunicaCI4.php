<?php

namespace Uspdev\SenhaunicaCI4;

use Uspdev\SenhaunicaCI4\ServerUSP;
use Config\Services;

class SenhaunicaCI4
{
    public static function login($clientCredentials = [])
    {
        // Carregar o serviço de sessão do CodeIgniter
        $session = Services::session();

        if (empty($clientCredentials)) {
            $clientCredentials['identifier'] = getenv('SENHAUNICA_KEY');
            $clientCredentials['secret'] = getenv('SENHAUNICA_SECRET');
            $clientCredentials['callback_id'] = getenv('SENHAUNICA_CALLBACK_ID');
        }

        $server = new ServerUSP($clientCredentials);

        // step 3: tudo ok
        if ($session->has('token_credentials')) {
            $tokenCredentials = unserialize($session->get('token_credentials'));
            $session->set('oauth_user', $server->getUserDetails($tokenCredentials));
            return $session->get('oauth_user');
        }

        // step 2: recebendo o retorno do oauth
        if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
            $temporaryCredentials = unserialize($session->get('temporary_credentials'));
            $tokenCredentials = $server->getTokenCredentials($temporaryCredentials, $_GET['oauth_token'], $_GET['oauth_verifier']);
            
            // Atualizando as credenciais de sessão
            $session->remove('temporary_credentials');
            $session->set('token_credentials', serialize($tokenCredentials));
            $session->set('oauth_user', $server->getUserDetails($tokenCredentials));

            // Redirecionamento após autenticação
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // step 1: credenciais temporárias e redirecionamento para login USP
        $temporaryCredentials = $server->getTemporaryCredentials();
        $session->set('temporary_credentials', serialize($temporaryCredentials));
        $url = $server->getAuthorizationUrl($temporaryCredentials) . '&callback_id=' . $clientCredentials['callback_id'];
        
        // Redirecionamento para o URL de autorização
        header('Location: ' . $url);
        exit;
    }

    public static function getUserDetail()
    {
        // Carregar o serviço de sessão do CodeIgniter
        $session = Services::session();
        return $session->get('oauth_user');
    }

    public static function obterVinculo($campo, $valores)
    {
        // Carregar o serviço de sessão do CodeIgniter
        $session = Services::session();

        $oauthUser = $session->get('oauth_user');
        if (!isset($oauthUser['vinculo'])) {
            return null;
        }
        if (!is_array($valores)) {
            $valores = [$valores];
        }

        foreach ($valores as $valor) {
            foreach ($oauthUser['vinculo'] as $v) {
                if ($v[$campo] == $valor) {
                    return $v;
                }
            }
        }
        return false;
    }
}