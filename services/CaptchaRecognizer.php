<?php
namespace app\services;

class CaptchaRecognizer
{
	public $apiKey = '1d43fa2cc73a8f6e967c802c56abf7bd';
	public $sendhost = 'antigate.com';
	public $is_verbose = true;
	public $rtimeout = 5;
	public $mtimeout = 120;
	public $is_phrase = 0;
	public $is_regsense = 0;
	public $is_numeric = 0;
	public $min_len = 0;
	public $max_len = 0;
	public $is_russian = 0;
	public $ext = 'jpg';

	public function getCode($fileContent)
	{
	    $postdata = [
	        'method'    => 'base64', 
	        'key'       => $this->apikey, 
	        'body'      => base64_encode($fileContent),
	        'ext' 		=> $this->ext,
	        'phrase'	=> $this->is_phrase,
	        'regsense'	=> $this->is_regsense,
	        'numeric'	=> $this->is_numeric,
	        'min_len'	=> $this->min_len,
	        'max_len'	=> $this->max_len,
	        'is_russian'	=> $this->is_russian,
	    ];
	    
	    $poststr="";

	    while (list($name,$value) = each($postdata)) {
	    	if (strlen($poststr)>0) $poststr.="&";
	    	$poststr.=$name."=".urlencode($value);
	    }
	    
	    $fp=fsockopen($this->sendhost, 80);

	    if ($fp ==false)
	    {
	    	return false;
	    }

    	$header="POST /in.php HTTP/1.0\r\n";
    	$header.="Host: $sendhost\r\n";
    	$header.="Content-Type: application/x-www-form-urlencoded\r\n";
    	$header.="Content-Length: ".strlen($poststr)."\r\n";
    	$header.="\r\n$poststr\r\n";

    	fputs($fp,$header);
    	$resp="";
    	while (!feof($fp)) $resp.=fgets($fp,1024);
    	fclose($fp);
    	$result=substr($resp,strpos($resp,"\r\n\r\n")+4);

	    
	    if (strpos($result, "ERROR") ===false)
	    {
	        return false;
	    }


        $ex = explode("|", $result);
        $captcha_id = $ex[1];
    	$waittime = 0;
        sleep($this->rtimeout);
        $url = 'http://'.$this->sendhost . '/res.php?key=' . $this->apikey . '&action=get&id=' . $captcha_id;

        while(true) {
            $result = file_get_contents($url);

            if (strpos($result, 'ERROR') !== false) return false;

            if ($result !== "CAPCHA_NOT_READY") {
            	$ex = explode('|', $result);
            	if (trim($ex[0])=='OK') return trim($ex[1]);
            }

        	$waittime += $this->rtimeout;

        	if ($waittime > $this->mtimeout) break;

        	sleep($this->rtimeout);
        }
	        
	    return false;
	}
}