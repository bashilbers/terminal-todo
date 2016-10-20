<?php
date_default_timezone_set('Europe/Amsterdam');


foreach(['defaults', 'debug'] as $a){
    $c = strtoupper($a); 
    define($c, in_array('--'.$a, $argv));    
}

/**
 * Dump 'n die!
 * @param $i
 */
function dd($i){ var_dump($i); die; }

/**
 * Print a success message
 * @param $s
 */
function s($s) { print '[+] ' . $s . PHP_EOL;};

/**
 * Show an error
 * @param $e
 */
function e($e) { print '[X] ' . $e . PHP_EOL; die;};

/**
 * Write an empty line to the cli;
 * @param $w
 */
function w($w) { print '' . $w . PHP_EOL;};

/**
 * Reads a line from the cli
 * @param $q
 * @return string
 */
function r($q, $default = false) { 

    if($default !== false){

        if(DEFAULTS){
            return $default;
        }
        
        $q .= ' (default:' . $default.')';
    }

    $in = readline($q  . PHP_EOL ); 
    if(empty($in) && $default !== false){
        return $default;
    }else{
        return $in;
    }
}

/**
 * Execute a bash command
 */
function cli_exec($cmd){
    if(DEBUG){
        w('CLI EXEC: ' . $cmd);
    }else{
        return shell_exec($cmd);
    }
}

/**
 * Do a CURL request, only wrapped in one function
 * @param string $url
 * @param array $headers
 * @param array $options
 * @return string
 */
function curl_request($type, $url, $headers = [], $options = [], $returninfo = false){

    $defaults = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers
    ];

    // set the custom request header
    if($type != 'GET'){
        $defaults[CURLOPT_CUSTOMREQUEST] = $type;
    }

    // apply the defaults
    foreach($defaults as $key=>$option){
        if(!isset($options[$key])){
            $options[$key] = $option;
        }
    }

    // execute the request
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if($returninfo){
        return ['body' => $result, 'info' => $info];
    }else{
        return $result;
    }
}
