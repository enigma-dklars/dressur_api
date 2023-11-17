<?php

namespace App\Services;

class SessionWP
{
    /**
     * Check if session is enable else enable it 
     *
     * @return void
     */
    public static function check(){
        if(session_status() === PHP_SESSION_NONE){
            session_start();
        }
    }


    /**
     * set if session
     *
     * @return void
     */
    public function set($key,$val){
        self::check();
        $_SESSION[$key] = $val;
    }


    /**
     * Destroy session associat key value or all value
     *
     * @return void
    */
    public static function destroy($key=null){
        self::check();
        if($key){
            unset($_SESSION[$key]);
        }else{
            //unset($_SESSION);
            session_destroy();
        }
    }


    /**
     * Get session a guived key value or all value
     *
     * @return mixed
    */
    public static function get($key){
        self::check();
        if($key){
            return $_SESSION[$key];
        }else{
            return $_SESSION;
        }
    }
    
    
    /**
     * Push data to session variable
     * @param mixed $data
     * @return mixed
    */
    public static function push($data){
        self::check();
        is_array($data) ? array_map(function($value) use ($data){$_SESSION[array_search($value,$data)] = $value;},$data) : $_SESSION[] = $data;
    }


    public static function checkSessionVariable($key){
        self::check();
        if(isset($_SESSION[$key])){
            return true;
        }
        return false;
    }
}
