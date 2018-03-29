<?php
  // http://www.emnix.com
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
 "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">

<html>

<head>
  <title>Wordpress Auto Poster</title>
 <meta charset="utf-8">


<link rel="stylesheet" type="text/css" href="style.css" />


</head>

<body>

<div id="main">

 <h1>WP post article</h1>

 <form action="" method="post" name="myform">
   <fieldset>
    <legend>Website</legend>
    <table border="0" cellpadding="2"><tr><td><label>URL</label></td><td colspan="3"><input type="text" class="inp1" name="host" style="width: 600px;" required='required' value="" /><i> root domain, ex: <b>https://www.mysite.com/blog/</b></i></td></tr><tr><td><label>username</label></td><td><input type="text" class="inp1" name="username" style="width: 194px;" required='required' value="" /></td><td align="right"><label>password</label></td><td align="right"><input type="text" class="inp1" name="pass" style="width: 194px;" required='required' value="" /></td></tr></table>
  </fieldset>

   <fieldset>
    <legend>Post data</legend>
    <table border="0" cellpadding="2">
        <tr><td><label></label><select name="post_type"><option value="post" selected="selected">post</option><option value="page">page</option></select></td></tr>
        <tr><td><label>Title</label><input type="text" class="inp1" name="title" style="width: 690px;" required='required' value="" /></td></tr>
        <tr><td><label>Categories <i>(*separated by comma , ex: fun,animals,dogs)</i></label><input type="text" class="inp1" name="categories"  value="" /></td></tr>
        <tr><td><label>Tags <i>(*separated by comma , ex: tag1,tag2,tag3)</i></label><input type="text" class="inp1" name="tags"  value="" /></td></tr>
        <tr><td><label>Featured image url</label><input type="text" class="inp1" name="featured_image"   /> </td></tr>
        <tr><td><label>Content body <i>(*html)</i></label><textarea rows="5" cols="45" name="content" class="txt1" required='required'></textarea></td></tr>
        <tr><td><label style="display: inline;">post status </label> <select name="post_status"><option value="draft">draft</option><option value="publish" selected="selected">publish</option><option value="pending" selected="selected">pending</option></select>
        <label style="display: inline;">comment status </label> <select name="comment_status"><option>settings default</option><option value="open">open</option><option value="closed">closed</option></select></td></tr>

        <tr><td align="right"><input type="submit" name="sbmSave" class="sbm1" value="SAVE" onclick="this.value='Please wait..'" /></td></tr>

    </table>
  </fieldset>


 </form>

<?php
    //error_reporting(E_ALL); ini_set("display_errors",1);
    ini_set('allow_url_fopen',1);
    include 'xmlrpc.class.php';

    //if allow_url_fopen is disbled on server conf
    function grab_image($url,$saveto){
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $raw=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($saveto)){
            unlink($saveto);
        }
        $fp = fopen($saveto,'x');
        fwrite($fp, $raw);
        fclose($fp);
    }


 if (!empty($_POST['sbmSave'])) {
    echo "<hr />";
    $host_input      = $_POST['host'] ;
    $host = trim($host_input,'/') . '/xmlrpc.php';
    $username        = $_POST['username'] ;
    $pass            = $_POST['pass'] ;
    $post_type       = $_POST['post_type'];
    $title           = $_POST['title'] ;
    $category        = $_POST['categories'] ;
    $keywords        = $_POST['tags'] ;
    $featured_image  = $_POST['featured_image'] ;
    $content         = $_POST['content'] ;
    $post_status     = $_POST['post_status'] ;
    $allow_comments  = $_POST['comment_status'] ;

    if (empty($host) OR empty($username) OR empty($pass) OR empty($title) OR empty($content )) {
        die("<h3 class='err'>error. field empty. š</h3>");
    }

    $client = new WP_XMLRPCClient($host,$username,$pass);

    $pictureID=0;
    if (!empty($featured_image)) {
        $rnd = md5(time());
        $ext = pathinfo($featured_image, PATHINFO_EXTENSION);
        if ($ext=='jpg'){
            $random_filename = $rnd . ".jpg";
        } else {
            if ($ext=='png') {
                $random_filename = $rnd . ".png";
            }   else {
                die("<h3 class='err'>invalid image: $ext </h3>");
            }
        }

        //echo "$featured_image ==> $random_filename \r\n";
        $r=copy($featured_image,'tmp/' . $random_filename);

        if (!$r) {
            echo("<h3 class='err'>Error with featured image copy </h3>");
            echo("<h3>Try curl method.... </h3>");
            grab_image($featured_image,'tmp/' . $random_filename);
            if (filesize('tmp/' . $random_filename) > 0) {
                echo("<h3 class='ok'>featured image OK uploaded </h3> \r\n");
                echo "<img src=\"tmp/$random_filename\" width=100 alt=\"tmp/$random_filename\" />";
            } else {
                echo("<h3 class='err'>Error with featured image CURL copy </h3>");
            }
        } else {
            echo("<h3 class='ok'>featured image OK uploaded </h3> \r\n");
            echo "<img src=\"tmp/$random_filename\" width=100 alt=\"tmp/$random_filename\" />";
        }

        if (filesize('tmp/' . $random_filename) > 0) {
               $image_full_path =  'tmp/' . $random_filename ;
               //echo base64_encode(file_get_contents($image_full_path)); die();
               $media_response = $client->create_post_image($image_full_path);
               $media_response_decoded=xmlrpc_decode($media_response);
               $attachment_id=$media_response_decoded['attachment_id'];
               $attachment_url=$media_response_decoded['url'];
               $faultCode=$media_response_decoded['faultCode'];
               $faultString=$media_response_decoded['faultString'];

                if (!empty($attachment_id)) {
                    echo("<h3 class='ok'>WP media SAVED. ID: $attachment_id </h3> \r\n");
                    echo "<img src=\"$attachment_url\" width=100 alt=\"$attachment_url\" />";
                    $pictureID = $attachment_id;
                } else {
                        echo("<h3 class='err'>Error with WP media. #$faultCode - $faultString</h3>");
                }
       }
       unlink('tmp/' . $random_filename);// remove from tmp folder


    } // image upload and save to wp media


    $content = stripslashes($content); $category = explode(",",$category);
    $response = $client->create_post($title, $content,$category,$keywords,$pictureID,$allow_comments,$post_status,$post_type);
    $response_decoded = xmlrpc_decode($response);

    if (empty($response_decoded['faultCode']) and !empty($response_decoded)) {
        $postID = $response_decoded;
        $link = trim($host1,'/') . "/?p=$postID";
        echo("<h3 class='ok'>Your Data Has Been Successfully Saved! PostID: $postID <br /><a class=\"link_view\" href=\"$link\" title=\"view online\" target=\"_blank\">View online</a></h3>");
    } else {
        $faultCode=$response_decoded['faultCode'];
        $faultString=$response_decoded['faultString'];
        echo "<h3 class='err'>*ERROR. #$faultCode - $faultString</h3> ";
        //echo "<pre>";print_r($response_decoded);echo "</pre>";
        //echo "<pre>";print_r($response);echo "</pre>";
    }


 }

?>


</div><!-- /main -->

<br /><br /><br /><br />
</body>

</html>