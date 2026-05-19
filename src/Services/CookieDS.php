<?php 

namespace App\Services;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieDS {

    private string $secret;

    public function __construct(ParameterBagInterface $params)
    {
        $this->secret = $params->get('kernel.secret');
    }

    private function isSecure(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        return false;
    }

    private function sign(string $value): string
    {
        $hmac = hash_hmac('sha256', $value, $this->secret);
        return $value . '.' . $hmac;
    }

    private function verify(string $signed): string|false
    {
        $pos = strrpos($signed, '.');
        if ($pos === false) {
            return false;
        }
        $value    = substr($signed, 0, $pos);
        $hmac     = substr($signed, $pos + 1);
        $expected = hash_hmac('sha256', $value, $this->secret);
        if (!hash_equals($expected, $hmac)) {
            return false;
        }
        return $value;
    }

    public function set($key, $val){
        $signed = $this->sign((string) $val);
        setcookie($key, $signed, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $this->isSecure(),
            'samesite' => 'Lax',
        ]);
    }

    public function get($key = null){ 
        if ($key) {
            if (isset($_COOKIE[$key])) {
                return $this->verify($_COOKIE[$key]);
            }
            return false;
        } else {
            $all = [];
            foreach ($_COOKIE as $k => $v) {
                $verified = $this->verify((string) $v);
                if ($verified !== false) {
                    $all[$k] = $verified;
                }
            }
            return $all;
        }
    }

    /**
     * Résoudre le uid : cookie en priorité (signé HMAC), puis fallback sur le body POST.
     * Permet aux clients mobiles (Flutter) qui ne gèrent pas les cookies
     * d'envoyer le uid directement dans le corps de la requête.
     */
    public function getWithFallback(string $key, Request $request): string|false
    {
        // 1. Cookie signé — prioritaire (navigateur web)
        $fromCookie = $this->get($key);
        if ($fromCookie !== false && $fromCookie !== '') {
            return $fromCookie;
        }

        // 2. Fallback POST body — pour les clients mobiles sans cookie
        $fromPost = $request->request->get($key);
        if ($fromPost !== null && trim((string) $fromPost) !== '') {
            return trim((string) $fromPost);
        }

        return false;
    }

    public function check($key = null){
        if (isset($_COOKIE[$key])) {
            return $this->verify($_COOKIE[$key]) !== false;
        }
        return false;
    }

    public function remove($key){
        setcookie($key, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $this->isSecure(),
            'samesite' => 'Lax',
        ]);
    }
    
}
