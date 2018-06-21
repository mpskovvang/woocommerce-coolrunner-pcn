<?php

class CR_Curl {

    public $destination;
    public $username;
    public $token;
    public $data;
    public $json;

    function __contruct() {
        $destination = "";
        $username = "";
        $token = "";
        $data = array();
        $json = false;
    }

    public function sendCurl($destination, $username, $token, $data = array(), $recieve_header = false, $json) {

        //Linket til funktionen den skal ramme
        $url = 'https://api.coolrunner.dk/' . $destination;

        // Start curl
        $ch = curl_init();

        // Sæt curl url
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_USERPWD, $username . ":" . $token);

        // Output ikke responset
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($data)) {

            curl_setopt($ch, CURLOPT_POST, 1);

            if ($json == true) {

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen(json_encode($data)),
                    "X-Developer-Id", COOLRUNNER_NAME . ' v' . COOLRUNNER_VERSION,
                ));

            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }

        }

        // Kør curl
        $result = curl_exec($ch);

        // I tilfælde af fejl printer den fejlen
        if (curl_errno($ch)) {
            echo 'Error: ' . curl_error($ch);
        }

        if ($recieve_header == true) {
            $responsecode = curl_getinfo($ch);
        }

        // Luk curl
        curl_close($ch);

        if ($recieve_header == true) {
            return $responsecode;
        }

        $result = json_decode($result);

        return $result;
    }
}

?>