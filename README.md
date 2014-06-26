Cow
===

Cow 는 Codeigniter 라이브러리입니다.  어렵지는 않지만 대단히 귀찮은 작업중 하나인 로그인 로직 짜기, 높은 수준의 비밀번호 해시 만들기 등을 지원합니다.

# 종속성 및 권장사항
Cow 는 Codeigniter의 session 라이브러리가 선행적으로 로드되어 있어야 정상적으로 동작을 보장할 수 있습니다.  더불어 보기 좋은 코드를 위해 Mex Library를 사용하는 것을 권장합니다.

# 사전준비
다음 코드는 생성자 함수 내부에 위치할 것은 권장합니다.

```php
  $this->load->library( arrary('session', 'cow', 'mex') );
  // session과 cow 및 mex 라이브러리를 로드했습니다.
  
  $this->cow->setConfig( array(
                             'table' => 'user_account',
                             'id_column' => 'user_id',
                             'pw_column' => 'user_pw',
                             'session_field' => 'last_action',
                             'expire' => 600
                           ) );
                           
  /* table은 로그인에 사용할 DB의 테이블 명입니다.
   * id_column은 id 데이터가 저장되는 컬럼 명입니다.
   * pw_column은 password 데이터가 저장되는 컬럼 입니다.
   * session_field는 session에서 사용자의 마지막 활동 시간을 기록하는 필드명입니다.
   * expire는 사용자가 일정시간 이무 행동도 하지 않았을때 자동 로그 아웃 되는 시간으로 단위는 초이다.
   */
```

# 로그인 처리를 해보자
## login( $id, $password, $success_callback_function, $fail_callback_function )
로그인 데이터는 ID와 Password 뿐이며 POST로 전송된다고 가정합니다.  view쪽 코드는 생략하고 Form Action으로 아래의 컨트롤러 함수를 호출합니다.

```php
  // POST로 전송된 데이터는 XSS_Clean 과정을 거친 후 $req라는 배열로 전달된다.
  
  $this->mex->requset('POST', function( $req ){
    $id = & $req['user_id'];
    $pw = & $req['user_pw'];
    
    $this->cow->login( $id, $pw,
      function($req){
        // 로그인 성공했을 때 동작
      },
      function($req){
        // 로그인 실패시 동작  
      }
    );
  }, true);
  
  $this->mex->request('/', function(){
    // 아무런 요청이 없을 경우 동작
  }
```

# 타임아웃 처리
## timeout( $timeout_function )
타임아웃 처리는 생성자 함수 내에서 체크할 것을 권장합니다.

```php
  $this->mex->timeout( function(){
    // setConfig에서 설정한 시간이 지났을 때 동작
  });
```

# 해시값 생성
## pw_gen( $plain_text )
```php
  $hash = $this->cow->pwGen( 'plaintext' );
  echo $hash;
```
Cow 클래스의 pw_gen 이라는 함수를 통해 자동으로 해시값이 생성됩니다.  사용중인 PHP버전이 높을 수록 강력한 해시 암호화 알고리즘을 사용합니다. 따라서, 보안을 위해 PHP 5.3.2 이상(최하 5.3.0)을 사용할 것을 권장합니다.

## crypt_by_bcrypt( $plain_input, $detail )
BCRYPT 알고리즘으로 해시값을 생성합니다.  PHP 5.5.0 이상에서 사용 가능하며, PHP 5.5.0 이상의 환경에서 pw_gen을 통해 해시값을 생성할 경우 자동으로 이 함수를 이용해 해시값을 생성합니다.

```php
  $hash = $this->cow->crypt_by_bcrypt( 'plaintext' );
  echo $hash;
```

두번째 매개변수인 $detail을 true(기본값 false)로 지정할 경우 다음과 같은 형식의 배열이 반환됩니다.
```
  result => '변환된 해시값'
  php_version => '사용중인 PHP 버전'
  crypted_by => '해싱에 사용된 알고리즘'
```

## crypt_by_sha256( $plain_input, $detail, $salt_length, $iteration )
SHA256 알고리즘으로 해사값을 생성합니다.  PHP 5.3.2 이상에서 사용 가능하며, PHP 5.3.2 이상의 환경에서 pw_gen을 통해 해시값을 생성할 경우 자동으로 이 함수를 이용해 해시값을 생성합니다.

두 번째 매개변수인 $detail의 내용은 crypt_by_bcrypt()와 동일합니다.
세 번째 매개변수인 $salt_length 는 Salt string의 길이를 설정하며 기본값을 16입니다.
네 번째 매개변수인 $iteration은 해싱 반복횟수로 기본값은 9000입니다.

```php
  $hash = $this->cow->crypt_by_sha256( 'plaintext', false, 16, 1024 );
  echo $hash;
```

## crypt_by_md5( $plain_input, $detail, $salt_length )
MD5 알고리즘으로 해사값을 생성합니다.  PHP 5.3.0 이상에서 사용 가능하며, PHP 5.3.0 이상의 환경에서 pw_gen을 통해 해시값을 생성할 경우 자동으로 이 함수를 이용해 해시값을 생성합니다.

두 번째 매개변수인 $detail의 내용은 crypt_by_bcrypt()와 동일합니다.
세 번째 매개변수인 $salt_length 는 Salt string의 길이를 설정하며 기본값을 8입니다.

```php
  $hash = $this->cow->crypt_by_md5( 'plaintext', false, 8 );
  echo $hash;
```
