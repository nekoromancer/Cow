Cow
===

Cow 는 Codeigniter 라이브러리입니다.  어렵지는 않지만 대단히 귀찮은 작업중 하나인 로그인 로직 짜지, 높은 수준의 비밀번호 해시 만들기 등을 지원합니다.

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
로그인 데이터는 ID와 Password 뿐이며 POST로 전송된다고 가정한다.  view쪽 코드는 생략하고 Form Action으로 아래의 컨트롤러 함수를 호출한다고 가정한다.

```php
  // POST로 전송된 데이터는 XSS_Clean 과정을 거친 후 $req라는 배열로 전달된다.
  
  $this->mex->requset('POST', function( $req ){
    $id = & $req['user_id'];
    $pw = & $req['user_pw'];
    
    $this->cow->login( $id, $pw,
      functino($req){
        // 로그인 성공했을 때 동작
      },
      function($req){
        // 로그인 실패시 동작  
      }
    );
  }, true);
  
  // 아무런 요청이 없을 경우 동작
  $this->mex->request('/', function() {
    //  아무 요청이 없었을 경우에 동작
  });
```

# 타임아웃 처리
타임아웃 처리는 생성자 함수 내에서 체크할 것을 권장합니다.

```php
  $this->mex->timeout( function(){
    // setConfig에서 설정한 시간이 지났을 때 동작
  });
```

참 간단하죠?

# 해시값 생성
```php
  $hash = $this->cow->pwGen( 'plaintext' );
  echo $hash;
```
Cow 클래스의 pwGen 이라는 함수를 통해 자동으로 해시값이 생성된다.  사용중인 PHP버전이 높을 수록 강력한 해시 암호화 알고리즘을 사용합니다. 따라서, 보안을 위해 최소한 5.3.2 이상을 사용할 것을 권장합니다.
