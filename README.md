# 목차
- [개요](#overview)
- [설치](#install)
- [실행](#run)

<a name="overview"></a>
# 개요

iperf 서버 스트레스 테스트를 위한 콘솔 코맨드 툴이다. 스트레스 상황에서의 `iperf` 결과 값의 영향, 용량 예측, 동접 제한 예측 등의 목적으로 개발되었다. Linux PC/Mac에서 여러 개의 콘솔 창을 띄워 놓고, 본 `iperf-stress` 코맨드를 이용하여 스트레스 상황을 연출할 수 있다.
 
이 툴은 스트레스 테스트를 편리하게 하기 위한 기본 옵션들을 내장한 시스템 `iperf` 또는 `novak/iperf`코맨드의 Wrapper이다. 

<a name="install"></a>
# 설치

<a name="dependency"></a>
## 시스템 요구사항

- Linux 또는 Mac OS [^1]
- iperf (콘솔에서 `iperf -v` 로 확인 가능)
- php5.4 이상 (콘솔에서 `php -v` 로 확인 가능) [^2]

[^1]: 윈도우에서는 `iperf-stress` 실행파일의 첫 줄을 `c:\path-to-php-runtime\php.exe` 와 같이 수정하고, `Appkr/LoopCommand.php:275` 의 시스템 `iperf`의 경로를 수정해야 한다. 윈도우에서 동작은 검증되지 않았다. 
[^2]: 본 툴은 결과 기록을 위해 sqlite를 사용한다. php 실행기에 sqlite 모듈이 없다면, 리눅스 `sudo apt-get install sqlite3 php5-sqlite`, Mac OS `brew install sqlite3` 로 의존성 패키지들을 설치한다.

<a name="how-to-install"></a>
## 설치 방법

Linux PC/Mac 시스템에 `iperf` 툴이 없다면

~~~
# Linux
sudo apt-get iperf

# Mac OS
brew install iperf
~~~

Git 프로젝트를 클론하고 `iperf-stress` 파일에 실행권한을 부여한다.

~~~
git clone git@src.airplug.co.kr:git/iperf-stress.git && chmod 755 iperf-stress/iperf-stress
~~~

이 프로젝트에는 Novak이 수정한 `novak/iperf` 소스를 포함하고 있다. 이 `novak/iperf` 바이너리는 맥 OS X에서 컴파일되었는데, 설치하려는 시스템에 따라 다시 빌드해야 할 수도 있다.
  
~~~
cd path-to/iperf-stress/vendor/novak/iperf-2.0.5
./configure
make clean
make
cd ../../bin && ln -s ../novak/iperf-2.0.5/src/iperf iperf && chmod 755 iperf
~~~

<a name="run"></a>
# 실행

~~~
./iperf-stress command [options] [arguments]
~~~

<a name="command"></a>
## command
`loop`, `history` 2개의 명령을 내장하고 있다.

[`loop [options] [count]`](#command-loop)
:`count`로 지정한 횟수만큼 `iperf` 테스트를 수행한다.

[`history [options] [limit]`](#command-history)
:`limit`로 지정한 갯수만큼 과거 테스트 이력을 화면에 보여주거나, CSV로 추출한다.  

<a name="command-loop"></a>
### ./iperf-stress loop

`loop` 명령은 테스트 루프사이의 공백을 위한 `-S`, `novak/iperf`의 Upload/Download Swapping Patch가 적용되지 않은 서버를 테스트하기 위한 `-R` 옵션 외에는, 속도 측정을 위해 클라이언트에서 주로 사용하는 `iperf`의 기본 옵션을 거의 대부분 제공한다. *단, `-i`, `-t`, `-n` 옵션은 지원하지 않는다.*

~~~
# loop 명령에 대한 도움말 보기
./iperf-stress help loop
~~~

~~~
# loop 명령의 전체 Signature
./iperf-stress loop [-S|--sleep[="..."]] [-R|--no-reverse] [-c|--client="..."] [-l|--len[="..."]] [-p|--port[="..."]] [-u|--udp] [-w|--window="..."] [-B|--bind="..."] [-M|--mss="..."] [-N|--nodelay] [count]
~~~

아래는 옵션이다.
`--sleep (-S)`            
:테스트 loop 사이의 sleep 을 지정한다. *(default: 1)*

`--no-reverse (-R)`       
:Up/Down 스왑을 끈다. 즉, 이 옵션을 사용하면 Novak이 수정한 `iperf`를 사용하지 않고 시스템 `iperf`를 사용한다.
 
`--client (-c)`           
:run in client mode, connecting to <host> *(default: "speedgiga1.airplug.com")*

`--len (-l)`              
:length of buffer to read or write *(default: 10000)*

`--port (-p)`             
:server port to connect to *(default: 5100)*

`--udp (-u)`              
:use UDP rather than TCP

`--window (-w)`           
:TCP window size (socket buffer size)

`--bind (-B)`             
:bind to <host>, an interface or multicast address

`--mss (-M)`              
:set TCP maximum segment size

`--nodelay (-N)`          
:set TCP no delay, disabling Nagle's Algorithm

#### 실행 예

~~~
./iperf-stress loop 2
------------------------------------------------------------
Client connecting to speedgiga1.airplug.com, TCP port 5100
TCP window size:  129 KByte (default)
------------------------------------------------------------
[  9] local 192.168.11.5 port 49322 connected with 182.161.125.3 port 5100
[ ID] Interval       Transfer     Bandwidth
[  9]  0.0-10.0 sec  60.1 MBytes  50.4 Mbits/sec
Test index 1 done.
------------------------------------------------------------
Client connecting to speedgiga1.airplug.com, TCP port 5100
TCP window size:  129 KByte (default)
------------------------------------------------------------
[  9] local 192.168.11.5 port 49323 connected with 182.161.125.3 port 5100
[ ID] Interval       Transfer     Bandwidth
[  9]  0.0-10.0 sec  56.4 MBytes  47.3 Mbits/sec
Test index 2 done.
Test finished.
~~~

> 위 명령으로 수행되는 실제 시스템 코맨드는 `iperf -c speedgiga1.airplug.com -p 5100 -l 10000` 이다. 코맨드를 2번 수행하고 코맨드 사이의 sleep은 1초 이다.
> `./iperf-stress loop` 처럼 아무런 인자, 옵션 없이 실행할 경우, 코맨드를 1번 수행한다. 

<a name="command-history"></a>
### ./iperf-stress history 

`history` 명령은 테스트 이력을 살펴보기 위한 목적으로 제공된다. 테스트 이력을 CSV로 추출하기 위한 `-e` 옵션이 없으면 화면에 테이블 형태로 결과값들을 출력한다.

~~~
# history 명령에 대한 도움말 보기
./iperf-stress help history
~~~

~~~
# history 명령의 전체 Signature
./iperf-stress history [-e|--extract] [-t|--truncate] [limit]
~~~

아래는 옵션이다.

`--extract (-e)`
: `path-to/iperf-stress/exports` 에 CSV 파일로 저장한다.

`--truncate (-t)`
: 테스트 이력을 모두 삭제한다.

#### 실행 예

~~~
./iperf-stress history 2
+----+------+--------------------------------------------------+--------------------------------------------------+-------+-----------+---------------------+
| id | pid  | command                                          | result                                           | speed | unit      | tested_at           |
+----+------+--------------------------------------------------+--------------------------------------------------+-------+-----------+---------------------+
| 44 | 2983 | iperf -c speedgiga1.airplug.com -l 10000 -p 5100 | [  9]  0.0-10.0 sec   110 MBytes  92.1 Mbits/sec | 92.1  | Mbits/sec | 2015-03-22 21:38:47 |
| 43 | 2983 | iperf -c speedgiga1.airplug.com -l 10000 -p 5100 | [  9]  0.0-10.0 sec   108 MBytes  90.3 Mbits/sec | 90.3  | Mbits/sec | 2015-03-22 21:38:36 |
+----+------+--------------------------------------------------+--------------------------------------------------+-------+-----------+---------------------+
~~~
