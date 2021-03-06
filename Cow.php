<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Cow {
  /* 
   * $config array();
   * table : user account table name
   * id_column : user ID column name on table
   * pw_column : user Password column name on table
   * message_no_id : incorrect ID message
   * message_no_pw : incorrect Password message
   * message_success : Login success message
   * session_field : A field name the last action time recorded in session
   * expire : session expire time. default 600 second.
   *
   */

  private $config = array(
                        'id_column' => null,
                        'pw_column' => null,
                        'table' => null,
                        'message_no_id' => '아이디가 없습니다',
                        'message_no_pw' => '패스워드가 틀렸습니다',
                        'message_success' => '로그인 되었습니다',
                        'expire' => 600
                      );
  private $CI;
  private $db;

  public function __construct()
  {
    $this->CI = get_instance();
    $this->CI->load->database();
    $this->db = $this->CI->db;
  }

  private function config_validation()
  {
    foreach( $this->config as $key => $value )
    {
      if( !$this->config[$key] )
      {
        show_error( 'Configuration value \''.$key.'\' is required. Please set a value through set_config() function.', 500);
      }
    }
  }

  /*
   * Check php version.
   */

  private function check_php_version( $minimum = null )
  {
    $version = phpversion();
    $version = (int)(preg_replace('/\./', '', $version));

    if( $minimum ) 
    {
      if( $version < $minimum )
      {
        return array(
            'result' => false,
            'message' => 'Check your PHP version. You need PHP5 >= '.$minimum
          );
      }
      else
      {
        return array(
          'result' => true
          );
      }
    }

    return $version;
  }

  /*
   * Hash Generator.
   */

  public function pw_gen( $plain_input = null, $detail = false )
  {
    if( !$plain_input )
    {
      show_error( '1st parameter( plain text to crypt ) must be required', 500 );
    }

    $version = check_php_version();

    if( $version < 530 ) 
    {
      show_error( 'Sorry, but You need PHP5 >= 5.3.0 to using Password helper.', 500 );
    }
    else if( $version >= 550 )
    {
      return crypt_by_bcrypt( $plain_input, $detail );
    }
    else if( $version < 532 )
    {
      return crypt_by_md5( $plain_input, $detail );
    }
    else
    {
      return crypt_by_sha256( $plain_input, $detail );
    }
  }

  /*
   * Hash by BCRYPT.  PHP version >= 5.5.0
   */

  public function crypt_by_bcrypt( $plain_input = null, $detail =false )
  {
    $check = $this->$this->check_php_version(550);

    if( !$check['result'] )
    {
      show_error( $check['message'] );
    }

    $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
    $options = array('salt' => $salt);
    $hash = password_hash($plain_input, PASSWORD_BCRYPT, $options);

    if( $detail )
    {
      $result = array(
                  'result'=>$hash,
                  'php_version'=> phpversion(),
                  'crypted_by'=> 'BCRYPT'
                  );

      return $result;
    }
    else
    {
      return $hash;
    }
  }

  /*
   * Hash by SHA256.  PHP version >= 5.3.2
   */
    
  public function crypt_by_sha256( $plain_input = null, $detail = false, $salt_length = 16, $iteration = 9000 )
  {
    $check = $this->check_php_version(532);

    if( !$check['result'] )
    {
      show_error( $check['message'] );
    }

    $salt = mcrypt_create_iv($salt_length, MCRYPT_DEV_URANDOM);
    $salt = base64_encode($salt);
    $hash = crypt($plain_input, '$5$rounds='.$iteration.'$'.$salt.'$');

    if( $detail )
    {
      $result = array(
                  'result'=>$hash,
                  'php_version'=>phpversion(),
                  'crypted_by'=>'SHA256'
                  );

      return $result;
    }
    else
    {
      return $hash;
    }
  }

  /*
   * Hash by MD5.  PHP version >= 5.3.0
   */

  public function crypt_by_md5( $plain_input = null, $detail = fasle, $salt_length = 8 )
  {
    $check = $this->check_php_version(530);

    if( !$check['result'] )
    {
      show_error( $check['message'] );
    }

    $salt = mcrypt_create_iv($salt_length, MCRYPT_DEV_URANDOM);
    $salt = base64_encode($salt);
    $hash = crypt($plain_input, '$1$'.$salt.'$');

    if( $detail )
    {
      $result = array(
                  'result'=> $hash,
                  'php_version'=> phpversion(),
                  'crypted_by'=> 'MD5'
                  );

      return $result;
    }
    else
    {
      return $hash;
    }
  }

  /*
   * Password Verification.
   */

  public function pw_verify( $plain_input, $hash )
  {
    if( preg_match('/^\$2y\$/', $hash) )
    {
      if( password_verify( $plain_input, $hash ) )
      {
        return true;
      }
      else
      {
        return false;
      }
    }
    else
    {
      $_temp = explode('$', $hash);
      array_shift( $_temp );
      array_pop( $_temp );

      $header = '$'.implode('$', $_temp).'$';
      $userinput = crypt( $plain_input, $header );

      if( $userinput === $hash )
      {
        return true;
      }
      else
      {
        return false;
      }
    }
  }

  /*
   * Config 값 사용자 정의
   */

  public function set_config( $config )
  {
    foreach( $config as $key => $value )
    {
      $this->config[$key] = $value;
    }
  }

  /*
   * $id : submitted user id
   * $pw : submitted user password
   * $success_callback : it's callback function. if login success, this function called.
   * $failed_callback : it's callback function. if login failed, this function called.
   *
   */

  public function login( $id, $pw, $success_callback, $failed_callback )
  {
    $this->config_validation();
    $table = & $this->config['table'];
    $id_column = & $this->config['id_column'];
    $pw_column = & $this->config['pw_column'];

    $res = array();

    // ID Check

    $this->db->where($id_column, $id);
    $idCheck = $this->db->count_all_results($table);

    if( $idCheck === 0 )
    {
      $res['result'] = false;
      $res['code'] = '01';
      $res['message'] = $this->config['message_no_id'];

      // 아이디 없음 > 실패 콜백함수로 전달
      call_user_func( $failed_callback, $res );
      return false;
    }

    $this->db->select($pw_column);
    $this->db->where($id_column, $id);
    $hashed_pass = $this->db->get($table)->result_array()[0][$pw_column];

    $pw_check = $this->pw_verify( $pw, $hashed_pass );

    // Password Check

    if( !$pw_check )
    {
      $res['result'] = false;
      $res['code'] = '02';
      $res['message'] = $this->config['message_no_pw'];

      // Password 틀림 > 실패 콜백함수에 전달
      call_user_func( $failed_callback, $res );
      return false;
    }

    // 패스워드 성공 > 성공 콜백 함수에 전달
    $res['result'] = true;
    $res['code'] = '00';
    $res['message'] = $this->config['message_success'];

    call_user_func( $success_callback, $res );
    return true;
  }

  /*
   * 로그인 후 일정 시간 활동이 없을 경우 자동으로 로그아웃
   * 기본값은 600초
   */

  public function timeout( $timeout_callback )
  {
    if( !isset( $this->config['session_field'] ) )
    {
      show_error('\'session_field\' in configuration value is required to use timeout() function', 500);
    }
    $field = & $this->config['session_field'];
    $expire = & $this->config['expire'];
    $last_action = & $this->CI->session->userdata($field);

    if( mktime() - $last_action > $expire )
    {
      call_user_func( $timeout_callback );
    }
    else
    {
      $this->CI->session->set_userdata( $field, mktime() );
    }
  }
}
