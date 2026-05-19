<?php 

namespace App\Services;

class CookieDS {

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

    public function set($key, $val){
        setcookie($key, $val, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $this->isSecure(),
            'samesite' => 'Lax',
        ]);
    }

    public function get($key = null){ 
        if($key){
            if(isset($_COOKIE[$key])){
                return $_COOKIE[$key];
            }
            return false;
        }else{
            return $_COOKIE;
        }
    }

    public function check($key = null){
        if(isset($_COOKIE[$key])){
            return true;
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
