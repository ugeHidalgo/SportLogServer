<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class PassHash {
  
    private static $algo = '$2a';
    private static $cost = '$10';
    
    //For internal use when generating the hash
    public static function unique_salt() {
        return substr(sha1(mt_rand()), 0, 22);
    }
    
    //Generate a hash
    public static function hash($password) {
        
        return crypt($password, self::$algo .
                self::$cost .
                '$' . self::unique_salt());
    }
    
    //Compare a password against a hask
    public static function check_password($hash, $password) {
        $full_salt = substr($hash, 0, 29);
        $new_hash = crypt($password, $full_salt);
        return ($hash == $new_hash);
    }
}

?>

