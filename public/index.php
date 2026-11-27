<?php
declare(strict_types=1);
require __DIR__.'/../src/Db.php'; require __DIR__.'/../src/Booking.php'; require __DIR__.'/../src/release.php';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off']); session_start();
function h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function go(string $v):never{header('Location: ?'.$v, true,303);exit;}
function message(string $text,string $type='ok'):void{$_SESSION['flash']=[$text,$type];}
function money($v):string{return number_format((int)$v,0,',',' ').' ₽';}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$csrf=$_SESSION['csrf'];$user=$_SESSION['user']??null;$admin=($user['role']??'')==='admin';
try{$db=new Db();$booking=new Booking($db);}catch(Throwable $e){error_log($e->getMessage());http_response_code(503);exit('База данных недоступна. Проверьте настройки подключения.');}
$view=(string)($_GET['view']??'overview'); $id=max(0,(int)($_GET['id']??0));$formError=''; $old=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(!is_string($_POST['csrf']??null)||!hash_equals($csrf,$_POST['csrf'])){http_response_code(403);exit('Недействительный токен запроса.');}
 $action=$_POST['action']??'';
 if($action==='login'){
  $u=$db->query('SELECT * FROM users WHERE login=?',[(string)($_POST['login']??'')])[0]??null;
  if($u&&password_verify((string)($_POST['password']??''),$u['password_hash'])){session_regenerate_id(true);$_SESSION['csrf']=bin2hex(random_bytes(32));$_SESSION['user']=['login'=>$u['login'],'role'=>$u['role']];go('view=overview');}
  message('Неверный логин или пароль.','error');go('view=login');
 }
 if(!$user){http_response_code(401);exit('Требуется авторизация.');}
 if($action==='logout'){$_SESSION=[];session_destroy();go('view=login');}
 if(!$admin){http_response_code(403);exit('Недостаточно прав.');}
 try{
  if($action==='save'){$id=(int)($_POST['id']??0);$booking->save($_POST,$id);message(TRAINING_DEFECTS?'Запись добавлена.':($id?'Изменения сохранены.':'Бронь создана.'));go('view=bookings');}
  if($action==='cancel'){$booking->cancel((int)($_POST['id']??0));message('Бронь отменена.');go('view=bookings');}
  http_response_code(400);exit('Неизвестное действие.');
 }catch(DomainException $e){$formError=$e->getMessage();$old=$_POST;$view=$action==='save'?'form':'bookings';}
 catch(Throwable $e){error_log($e->getMessage());$formError='Не удалось сохранить данные. Повторите попытку.';$old=$_POST;$view=$action==='save'?'form':'bookings';}
}
if(!$user)$view='login';elseif($view==='login')go('view=overview');
if(!in_array($view,['login','overview','bookings','rooms','form'],true)){$view='overview';}
if($view==='form'&&!$admin){http_response_code(403);exit('Недостаточно прав.');}
$q=trim((string)($_GET['q']??''));$rooms=$user?$booking->rooms():[];$all=$user?$booking->list():[];$rows=$view==='bookings'?$booking->list($q):[];$flash=$_SESSION['flash']??null;unset($_SESSION['flash']);
$record=$old?:($id&&$view==='form'?$booking->find($id):[]);if($view==='form'&&$id&&(!$record||(($record['status']??'confirmed')!=='confirmed'))){message('Активная бронь не найдена.','error');go('view=bookings');}
$active=array_filter($all,fn($r)=>$r['status']==='confirmed');$today=date('Y-m-d');$arrivals=array_filter($active,fn($r)=>$r['arrival']===$today);
function token(string $csrf):void{echo '<input type="hidden" name="csrf" value="'.h($csrf).'">';}
?><!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>HOTEL — ИС Гостиница</title><link rel="stylesheet" href="assets/style.css"><script defer src="assets/app.js"></script></head><body>
<header class="top"><a class="brand" href="?view=overview">HOTEL <small>Система управления гостиницей</small></a><?php if($user):?><nav aria-label="Основное меню"><a href="?view=overview" class="<?= $view==='overview'?'selected':'' ?>">Обзор</a><a href="?view=bookings" class="<?= $view==='bookings'?'selected':'' ?>">Бронирования</a><a href="?view=rooms" class="<?= $view==='rooms'?'selected':'' ?>">Номера</a></nav><form method="post" class="logout"><?php token($csrf);?><input type="hidden" name="action" value="logout"><span><?=h($user['login'])?> · <?= $admin?'Администратор':'Наблюдатель' ?></span><button class="secondary">Выйти</button></form><?php endif;?></header>
<main class="<?= $view==='login'?'login-layout':'' ?>">
<?php if($flash):?><div class="notice <?=h($flash[1])?>" role="status"><?=h($flash[0])?></div><?php endif;?><?php if($formError):?><div class="notice error" role="alert"><?=h($formError)?></div><?php endif;?>
<?php if($view==='login'):?>
<section class="panel login"><p class="eyebrow">РАБОЧЕЕ МЕСТО</p><h1>Вход в систему</h1><p class="muted">Введите учётные данные для доступа к гостинице.</p><form method="post"><?php token($csrf);?><input type="hidden" name="action" value="login"><label>Логин<input name="login" required autocomplete="username"></label><label>Пароль<input type="password" name="password" required autocomplete="current-password"></label><button>Войти</button></form></section>
<?php elseif($view==='overview'):?>
<p class="eyebrow">РАБОЧИЙ СТОЛ</p><h1>Обзор гостиницы</h1><p class="muted">Управление номерным фондом и проживанием гостей.</p><div class="cards"><section class="panel"><span>Номерной фонд</span><strong><?=count($rooms)?></strong><p>Номеров в справочнике</p></section><section class="panel"><span>Активные брони</span><strong><?=count($active)?></strong><p>Подтверждённые записи</p></section><section class="panel"><span>Заезды сегодня</span><strong><?=count($arrivals)?></strong><p><?=h($today)?></p></section></div><section class="panel"><h2>Работа с бронированиями</h2><p>Создавайте и изменяйте записи, проверяйте доступность номера по датам.</p><div class="actions"><?php if($admin):?><a class="button" href="?view=form">Новая бронь</a><?php endif;?><a class="button secondary" href="?view=bookings">Журнал бронирований</a></div></section>
<?php elseif($view==='rooms'):?>
<p class="eyebrow">СПРАВОЧНИК</p><h1>Номерной фонд</h1><div class="cards"><?php foreach($rooms as $r):?><section class="panel"><span><?=h($r['category'])?></span><strong>№ <?=h($r['number'])?></strong><p>Вместимость: <?=h($r['capacity'])?> чел.</p><p><?=money($r['price'])?> / ночь</p><?php if($admin):?><a class="button" href="?view=form&amp;room_id=<?=h($r['id'])?>">Оформить бронь</a><?php endif;?></section><?php endforeach;?></div>
<?php elseif($view==='bookings'):?>
<p class="eyebrow">ЖУРНАЛ</p><div class="heading"><h1>Бронирования</h1><?php if($admin):?><a class="button" href="?view=form">Новая бронь</a><?php endif;?></div><section class="panel"><form class="search" method="get"><input type="hidden" name="view" value="bookings"><label class="sr">Поиск<input name="q" value="<?=h($q)?>" placeholder="Фамилия гостя или номер"></label><button>Найти</button><a class="button secondary" href="?view=bookings">Сбросить</a></form><p class="muted" id="count">Найдено: <?=TRAINING_DEFECTS&&$q!==''?count($all):count($rows)?></p><div class="table-wrap"><table><thead><tr><th>Гость / телефон</th><th>Номер</th><th>Заезд – выезд</th><th>Гостей</th><th>Стоимость</th><th>Статус</th><?php if($admin):?><th>Действия</th><?php endif;?></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><b><?=h($r['guest'])?></b><small><?=h($r['phone'])?></small></td><td><?=h($r['number'])?></td><td><?=h($r['arrival'])?><small><?=h($r['departure'])?></small></td><td><?=h($r['guests'])?></td><td><?=money($r['price'])?></td><td><span class="status <?=h($r['status'])?>"><?=$r['status']==='confirmed'?'Подтверждена':(TRAINING_DEFECTS?'Отменено':'Отменена')?></span></td><?php if($admin):?><td><?php if($r['status']==='confirmed'):?><div class="actions"><a class="button secondary" href="?view=form&amp;id=<?=h($r['id'])?>">Изменить</a><form method="post" class="cancel"><?php token($csrf);?><input type="hidden" name="action" value="cancel"><input type="hidden" name="id" value="<?=h($r['id'])?>"><button class="danger">Отменить</button></form></div><?php else:?>—<?php endif;?></td><?php endif;?></tr><?php endforeach;?><?php if(!$rows):?><tr><td colspan="<?=$admin?7:6?>">Записей не найдено.</td></tr><?php endif;?></tbody></table></div></section>
<?php elseif($view==='form'):?>
<p class="eyebrow">БРОНИРОВАНИЕ</p><h1><?=$id?'Редактирование брони № '.h($id):'Новая бронь'?></h1><section class="panel form-panel"><form method="post" id="booking-form"><?php token($csrf);?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=h($id)?>"><div class="form-grid"><label>ФИО гостя<input name="guest" required maxlength="120" value="<?=h($record['guest']??'')?>"></label><label>Телефон<input name="phone" type="tel" required maxlength="25" value="<?=h($record['phone']??'')?>"></label><label>Номер<select name="room_id" required><option value="">Выберите номер</option><?php foreach($rooms as $r):?><option value="<?=h($r['id'])?>" data-price="<?=h($r['price'])?>" data-capacity="<?=h($r['capacity'])?>" <?= (string)($record['room_id']??$_GET['room_id']??'')===(string)$r['id']?'selected':'' ?>><?=h($r['number'].' · '.$r['category'].' · '.$r['capacity'].' чел.')?></option><?php endforeach;?></select></label><label>Количество гостей<input name="guests" type="number" min="1" max="4" required value="<?=h($record['guests']??1)?>"></label><label>Заезд<input type="date" name="arrival" required value="<?=h($record['arrival']??'')?>"></label><label>Выезд<input type="date" name="departure" required value="<?=h($record['departure']??'')?>"></label></div><div class="estimate" id="estimate" aria-live="polite">Стоимость: —</div><p class="muted">Номер резервируется только после серверной проверки дат и вместимости.</p><div class="actions"><button>Сохранить<?=$id?' изменения':''?></button><a class="button secondary" href="?view=bookings">Назад</a></div></form></section>
<?php endif;?></main><footer>HOTEL · Учебная информационная система · <?=TRAINING_DEFECTS?'МДК 05.02 / учебная версия':'МДК 05.03 / исправленная версия'?></footer></body></html>
