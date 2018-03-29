<?php class WP_XMLRPCClient {
    var $xmlrpcurl;
    var $username;
    var $password;

    public function __construct($xmlrpcurl, $username, $password) {
        $this->xmlrpcurl = $xmlrpcurl;
        $this->username = $username;
        $this->password = $password;
    }

    public function send_request($requestname, $params) {
        $request = xmlrpc_encode_request($requestname, $params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->xmlrpcurl);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6"); // set browser/user agent č
        curl_setopt($ch, CURLOPT_REFERER, 'http://www.example.com');

        curl_setopt($ch, CURLOPT_HEADER, 0);

        $results = curl_exec($ch);
        $info = curl_getinfo($ch);

        curl_close($ch);
        return $results;
    }

    public function create_post($title, $body, $category='', $keywords='',$pictureid='',$allow_comments='closed',$post_status='draft',$post_type='post', $allow_pings='closed',$publish='true', $encoding='UTF-8') {
        $title = htmlentities($title, ENT_NOQUOTES, $encoding);
        $keywords = htmlentities($keywords, ENT_NOQUOTES, $encoding);
        $content = array(
            'title'=>$title,
            'description'=>$body,
            'mt_allow_comments'=>$allow_comments,
            'mt_allow_pings'=>$allow_pings,
            'post_type'=>$post_type,
            'mt_keywords'=>$keywords,
            'categories'=>$category,
            'post_status' => $post_status,
            'publish' => $publish
        );

        if (!empty($pictureid)) {
          $content['post_thumbnail'] = $pictureid;
          $content['wp_post_thumbnail'] = $pictureid;
        }

        //print_r($content);
        $params = array(0, $this->username, $this->password, $content, true);

        return $this->send_request('metaWeblog.newPost', $params);
    }

    public function create_post_image($image_full_path) {
        $filedata = file_get_contents($image_full_path) ;
        xmlrpc_set_type($filedata,'base64');

        $content = array(
            'name' => basename($image_full_path),
            'type' => mime_content_type($image_full_path),
//            'bits' => base64_encode(file_get_contents($image_full_path)),
            'bits' => $filedata,
            true
        );
        $params = array(0, $this->username, $this->password, $content, true);
        return $this->send_request('metaWeblog.newMediaObject', $params);
    }


}

?>