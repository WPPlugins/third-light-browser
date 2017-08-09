<?php
class IMSApiClientException extends Exception {}
class IMSApiPrerequisiteException extends IMSApiClientException {}
class IMSApiActionException extends IMSApiClientException {}
class IMSApiUsageException extends IMSApiClientException {}

class IMSApiClient
{
    private $m_strSessionKey;
    private $m_strIMSUrl;
    private $m_rCurl;
    private $m_arrExtraParams;
    private $m_Debug;

    public function __construct($strIMSUrl, $strApiKey = null, $arrOpts = null)
    {
        if(!function_exists("curl_init"))
        {
            throw new IMSApiPrerequisiteException("cURL extension not available");
        }
        if (0 !== strpos($strIMSUrl, "http"))
        {
            $strIMSUrl = "http://".$strIMSUrl;
        }
        $arrParts = parse_url($strIMSUrl);
        if(!$arrParts || empty($arrParts["host"]))
        {
            throw new IMSApiClientException("Unable to parse IMS URL");
        }
        $arrParts["scheme"] = empty($arrParts["scheme"]) ? "http" : $arrParts["scheme"];
        $arrParts["path"] = (empty($arrParts["path"]) ? "" : preg_replace("|/api.json.tlx$|","",$arrParts["path"]))."/api.json.tlx";

        $strFinalURL = $arrParts["scheme"]."://".$arrParts["host"].(empty($arrParts["port"]) ? "" : ":".$arrParts["port"]).$arrParts["path"];

        $this->m_arrExtraParams = array();

        $this->m_rCurl = curl_init($strFinalURL);
        $arrHeaders = array(
            'Accept: application/json',
            'Content-Type: application/json'
        );
        curl_setopt($this->m_rCurl, CURLOPT_HTTPHEADER, $arrHeaders);
        curl_setopt($this->m_rCurl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->m_rCurl, CURLOPT_POST, true);

        $this->m_Debug = null;

        if(is_array($arrOpts))
        {
            if(!empty($arrOpts["NO_VERIFY_SSL"]))
            {
                curl_setopt($this->m_rCurl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($this->m_rCurl, CURLOPT_SSL_VERIFYPEER, false);
            }
            if(!empty($arrOpts["EXTRA_PARAMS"]))
            {
                $this->m_arrExtraParams = $arrOpts["EXTRA_PARAMS"];
            }
            if(!empty($arrOpts["CURL_SETUP"]))
            {
                if(!is_callable($arrOpts["CURL_SETUP"]))
                {
                    throw new IMSApiClientException("Supplied cURL setup callback is not callable");
                }
                call_user_func($arrOpts["CURL_SETUP"],$this->m_rCurl);
            }
			if(!empty($arrOpts["DEBUG"]))
			{
                if(!is_callable($arrOpts["DEBUG"]))
                {
                    throw new IMSApiClientException("Supplied DEBUG callback is not callable");
                }
				$this->m_Debug = $arrOpts["DEBUG"];
			}
        }
        
        if(isset($strApiKey))
        {
            $this->LoginWithKey(array("apikey"=>$strApiKey));
        }
    }

    public function __call($method,$args)
    {
        if(preg_match("/^([a-zA-Z]+)(_)(.+)$/", $method, $arrMatches))
        {
            $module = strtolower($arrMatches[1]);
            $method = $arrMatches[3];
        }
        else
        {
            $module = "core";
        }

        $arrRequest = array(
            "apiVersion" => "1.0.1",
            "action" => "$module.$method",
            "inParams" => empty($args[0]) ? array() : $args[0]
        );
        if(isset($this->m_strSessionKey))
        {
            $arrRequest["sessionId"] = $this->m_strSessionKey;
        }

        curl_setopt($this->m_rCurl, CURLOPT_POSTFIELDS, json_encode(array_merge($arrRequest,$this->m_arrExtraParams)));

        $arrResponse = @json_decode(curl_exec($this->m_rCurl), true);
        
		if($this->m_Debug)
		{
			call_user_func($this->m_Debug, $arrRequest,$arrResponse);
		}

        if(!$arrResponse || empty($arrResponse["result"]) || empty($arrResponse["result"]["api"]))
        {
            throw new IMSApiClientException("Invalid server response");
        }
        if($arrResponse["result"]["api"] != "OK")
        {
            throw new IMSApiUsageException($arrResponse["result"]["api"]);
        }
        if($arrResponse["result"]["action"] != "OK")
        {
            throw new IMSApiActionException($arrResponse["result"]["action"]);
        }
        if($module == "core")
        {
            if(!empty($arrResponse["outParams"]) && isset($arrResponse["outParams"]["sessionId"]))
            {
                $this->m_strSessionKey = $arrResponse["outParams"]["sessionId"];
            }
        }
        return isset($arrResponse["outParams"]) ? $arrResponse["outParams"] : null;
    }
    
    public function GetSessionKey()
	{
		if(isset($this->m_strSessionKey))
        {
			return $this->m_strSessionKey;
		}
	}
}
