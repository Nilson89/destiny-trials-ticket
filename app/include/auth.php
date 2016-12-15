<?php
/**
 * Created by PhpStorm.
 * User: mase008
 * Date: 05.12.16
 * Time: 08:33
 */
function do_webauth($method, $username, $password, $cookie_file) {

    $user_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";

    $methods = array(
        'psn' => 'Psnid',
        'xbox' => 'Xuid'//'Wlid'
    );
    $dest = 'Wlid'; if (isset($methods[$method])) $dest = $methods[$method];
    $url = BUNGIE_URL.'/en/User/SignIn/'.$dest;

    $default_options = array(
        CURLOPT_USERAGENT => $user_agent,
        CURLOPT_COOKIEJAR => $cookie_file,
        CURLOPT_COOKIEFILE => $cookie_file,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    );

    // Get Third Party Authorization URL
    $ch = curl_init();
    curl_setopt_array($ch, $default_options);
    curl_setopt_array($ch, array(
        CURLOPT_URL => $url,
    ));
    curl_exec($ch);
    $curl_info = curl_getinfo($ch);
    #echo "<pre>";
    #var_dump($curl_info);
    #echo "</pre>";
    $redirect_url = $curl_info['redirect_url'];
    curl_close($ch);

    // Bungie Cookies are still valid
    if (!$redirect_url) return true;

    // Try to authenticate with Third Party
    $ch = curl_init();
    curl_setopt_array($ch, $default_options);
    curl_setopt_array($ch, array(
        CURLOPT_URL => $redirect_url,
    ));
    $auth_result = curl_exec($ch);
    $auth_info = curl_getinfo($ch);
    $auth_url = $auth_info['redirect_url'];

    // Normally authentication will produce a 302 Redirect, but Xbox is special...
    if ($auth_info['http_code'] == 200) $auth_url = $auth_info['url'];

    curl_close($ch);

    // No valid cookies
    if (strpos($auth_url, $url.'?code') !== 0) {
        $result = false;
        switch($method) {
            case 'psn':
                $login_url = 'https://auth.api.sonyentertainmentnetwork.com/login.do';

                // Login to PSN
                $ch = curl_init();
                curl_setopt_array($ch, $default_options);
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $login_url,
                    CURLOPT_POST => 3,
                    CURLOPT_POSTFIELDS => http_build_query(array(
                        'j_username' => $username,
                        'j_password' => $password,
                        'rememberSignIn' => 1 // Remember signin
                    )),
                ));
                curl_exec($ch);
                $redirect_url = curl_getinfo($ch)['redirect_url'];
                curl_close($ch);

                if (strpos($redirect_url, 'authentication_error') !== false) return false;

                // Authenticate with Bungie
                $ch = curl_init();
                curl_setopt_array($ch, $default_options);
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $redirect_url,
                    CURLOPT_FOLLOWLOCATION => true
                ));
                curl_exec($ch);
                $result = curl_getinfo($ch);
                curl_close($ch);
                break;
            case 'xbox':
                $login_url = 'https://login.live.com/ppsecure/post.srf?'.substr($redirect_url, strpos($redirect_url, '?')+1);
                preg_match('/id\="i0327" value\="(.*?)"\//', $auth_result, $ppft);

                if (count($ppft) == 2) {
                    $ch = curl_init();
                    curl_setopt_array($ch, $default_options);
                    curl_setopt_array($ch, array(
                        CURLOPT_URL => $login_url,
                        CURLOPT_POST => 3,
                        CURLOPT_POSTFIELDS => http_build_query(array(
                            'login' => $username,
                            'passwd' => $password,
                            'KMSI' => 1, // Stay signed in
                            'PPFT' => $ppft[1]
                        )),
                        CURLOPT_FOLLOWLOCATION => true
                    ));
                    $auth_result = curl_exec($ch);
                    $auth_url = curl_getinfo($ch)['url'];
                    curl_close($ch);

                    if (strpos($auth_url, $url.'?code') === 0) {
                        return true;
                    }
                }
                return false;
                break;
        }
        $result_url = $result['url'];
        if ($result['http_code'] == 302) $result_url = $result['redirect_url'];

        // Account has not been registered with Bungie
        if (strpos($result_url, '/Register') !== false) return false;

        // Login successful, "bungleatk" should be set
        // Facebook/PSN should return with ?code=
        // Xbox should have ?wa=wsignin1.0
        if (empty($result_url)
            or strpos($result_url, $url) === 0) {
            return true;
        }
        return false;
    }
    // Valid Third Party Cookies, re-authenticating Bungie Login
    $ch = curl_init();
    curl_setopt_array($ch, $default_options);
    curl_setopt_array($ch, array(
        CURLOPT_URL => $auth_url,
    ));
    curl_exec($ch);
    curl_close($ch);
    return true;
}
