<?php
declare(strict_types=1);
// PDO MySQL is the deployment driver. The FFI branch is a local Linux test adapter only.
final class Db {
    private ?PDO $pdo=null; private $ffi=null; private $handle=null; public bool $sqlite=false;
    public function __construct() {
        $path=getenv('HOTEL_TEST_SQLITE');
        if($path!==false && $path!=='') {
            $this->sqlite=true;
            if(in_array('sqlite',PDO::getAvailableDrivers(),true)) {$this->pdo=new PDO('sqlite:'.$path);}
            else {
                $this->ffi=FFI::cdef('typedef struct sqlite3 sqlite3; typedef struct sqlite3_stmt sqlite3_stmt; int sqlite3_open(const char*,sqlite3**); int sqlite3_close(sqlite3*); int sqlite3_prepare_v2(sqlite3*,const char*,int,sqlite3_stmt**,const char**); int sqlite3_bind_text(sqlite3_stmt*,int,const char*,int,void*); int sqlite3_step(sqlite3_stmt*); int sqlite3_finalize(sqlite3_stmt*); int sqlite3_column_count(sqlite3_stmt*); const char* sqlite3_column_name(sqlite3_stmt*,int); const char* sqlite3_column_text(sqlite3_stmt*,int); const char* sqlite3_errmsg(sqlite3*);','libsqlite3.so.0');
                $this->handle=$this->ffi->new('sqlite3*'); if($this->ffi->sqlite3_open($path,FFI::addr($this->handle))!==0) throw new RuntimeException('SQLite open failed');
            }
            $this->query('PRAGMA foreign_keys=ON'); $this->query('PRAGMA busy_timeout=3000');
        } else {
            $file=dirname(__DIR__).'/config.local.php'; if(!is_file($file)) throw new RuntimeException('Создайте config.local.php из config.example.php');
            $cfg=require $file; $this->pdo=new PDO($cfg['dsn'],$cfg['user'],$cfg['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
        }
    }
    public function query(string $sql,array $params=[]): array {
        if($this->pdo){$s=$this->pdo->prepare($sql);$s->execute($params);return $s->columnCount()?$s->fetchAll(PDO::FETCH_ASSOC):[];}
        $f=$this->ffi;$s=$f->new('sqlite3_stmt*'); if($f->sqlite3_prepare_v2($this->handle,$sql,-1,FFI::addr($s),null)!==0) throw new RuntimeException((string)$f->sqlite3_errmsg($this->handle));
        try {foreach(array_values($params) as $i=>$v) { $v=(string)$v; if($f->sqlite3_bind_text($s,$i+1,$v,strlen($v),FFI::cast('void*',-1))!==0) throw new RuntimeException('Bind failed'); }
        $rows=[];while(($rc=$f->sqlite3_step($s))===100){$row=[];for($i=0;$i<$f->sqlite3_column_count($s);$i++){$k=$f->sqlite3_column_name($s,$i);$v=$f->sqlite3_column_text($s,$i);$row[$k]=$v;} $rows[]=$row;} if($rc!==101) throw new RuntimeException((string)$f->sqlite3_errmsg($this->handle)); return $rows;
        } finally {$f->sqlite3_finalize($s);}
    }
    public function begin():void {$this->query($this->sqlite?'BEGIN IMMEDIATE':'START TRANSACTION');}
    public function commit():void {$this->query('COMMIT');}
    public function rollback():void {$this->query('ROLLBACK');}
    public function __destruct(){if($this->ffi && $this->handle) $this->ffi->sqlite3_close($this->handle);}
}
