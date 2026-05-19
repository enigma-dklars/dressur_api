<?php 

namespace App\Services;

class CookieDS {

    public function set($key,$val){
        setcookie($key, $val, [
            'expires'  => time() + 365 * 24 * 3600,
            'path'     => '/',
            'httponly' => true,
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
        setcookie($key, '', time(), '/');
    }
    
}