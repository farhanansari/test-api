<?php

namespace App\Controller\Component;

use Cake\Controller\Component;

class BullhornCurlComponent extends Component {

    /**
     * for handling single curl request
     * @param type $url
     * @param type $post_params
     * @param type $req_method
     * @return type
     */
    public function curlFunction($url, $post_params, $req_method) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $req_method);   // for Create -> "PUT"  & Update ->"POST" //
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($post_params)));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($curl);
        $response = json_decode($result, true);
        return $response;
    }

    /**
     * for handling multi curl request
     * @param type $data => curl data for send
     * @param type $options => if any extra data to send
     * @return type
     */
    function multiRequest($data, $options = array()) {

        // array of curl handles
        $curly = array();
        // data to be returned
        $result = array();

        // multi handle
        $mh = curl_multi_init();

        // check for header
        // loop through $data and create curl handles
        // then add them to the multi-handle
        foreach ($data as $id => $d) {

            $curly[$id] = curl_init();

            $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
            curl_setopt($curly[$id], CURLOPT_URL, $url);
            curl_setopt($curly[$id], CURLOPT_HEADER, 0);
            curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, 1);

            // post?
            if (is_array($d)) {          
                if (!empty($d['post_data'])) {
                 //   curl_setopt($curly[$id], CURLOPT_POST, 1);
                    curl_setopt($curly[$id], CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
                    curl_setopt($curly[$id], CURLOPT_CUSTOMREQUEST, $d['req_method']);
                    curl_setopt($curly[$id], CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($d['post_data'])));
                    curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post_data']);
                }
            }

            // extra options?
            if (!empty($options)) {
                curl_setopt_array($curly[$id], $options);
            }

            curl_multi_add_handle($mh, $curly[$id]);
        }

        // execute the handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);


        // get content and remove handles
        foreach ($curly as $id => $c) {
            $result[$id] = curl_multi_getcontent($c);
            curl_multi_remove_handle($mh, $c);
        }

        // all done
        curl_multi_close($mh);

        return $result;
    }

}
