<?php

require 'vendor/autoload.php';

use Goutte\Client;

$ini = parse_ini_file('./config.ini');

if(!is_dir('data')){
    mkdir('data');
}

$client = new Client();

$crawler = $client->request('GET', $ini['url']);
$form = $crawler->selectButton('Вход')->form();
$crawler = $client->submit($form, array(
    'vb_login_username' => $ini['login'], 
    'vb_login_password' => $ini['password']));
try{ //check if logged
    $check = $crawler->filter('p.restore')->text();
    if($check){
        echo "successfully logged in".PHP_EOL;
    }
} catch(Exception $e) {

}

$crawler = $client->request('GET', $ini['theme_page']);
$theme = $crawler->filter('li.lastnavbit')->text();

$blocks = $crawler->filter('li.postbitlegacy')->each(function($node){
    //use try-catch because of advertising
    try{
        $num = $node->filter('span.nodecontrols a~a')->text();
        $name = $node->filter('a.username')->text();
        $date_with_space = $node->filter('span.date')->text();
        $date = preg_replace('/\s+/u','_',$date_with_space);
        //because of messeges's quotes we need to get html
        $message_with_tags = $node->filter('blockquote.postcontent')->html();
        $message = cleanMessage($message_with_tags);

        return compact('num','name','date','message'); 

    }catch(Exception $e) {
            // Node list is empty
    }    
});

//write data to files
foreach ($blocks as $block){
    if($block == NULL){
        continue;
    }
    $file_name = "data/".$theme.'_'.$block['date'];

    $handle = fopen($file_name, "w");
    fwrite($handle, $block['num'].PHP_EOL);
    fwrite($handle, $block['name'].PHP_EOL);
    fwrite($handle, $block['date'].PHP_EOL);
    fwrite($handle, $block['message'].PHP_EOL);
    fclose($handle);
}

function cleanMessage($string){
    $patterns = [];
    $patterns[] = '/\s{2,}|^\s/'; //delete double spaces
    $patterns[] = '/\n/'; //delete new line
    $patterns[] = '/<div\s.*div>/'; //delete quotes
    $cleanM = preg_replace($patterns, '', $string);
    $cleanM = trim(strip_tags($cleanM)); //delete html tags and spaces
    return $cleanM;
}
