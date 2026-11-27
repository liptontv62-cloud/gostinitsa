<?php
require __DIR__.'/../src/Db.php';
if(PHP_SAPI!=='cli') exit(1);
$db=new Db(); foreach([['admin','HotelAdmin!26','admin'],['viewer','HotelViewer!26','viewer']] as [$login,$password,$role]){
 if(!$db->query('SELECT id FROM users WHERE login=?',[$login])) $db->query('INSERT INTO users(login,password_hash,role) VALUES(?,?,?)',[$login,password_hash($password,PASSWORD_DEFAULT),$role]);
} echo "Demo accounts initialized. Change passwords before production.\n";
