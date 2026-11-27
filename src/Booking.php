<?php
declare(strict_types=1);
final class Booking {
    public function __construct(private Db $db){}
    public function rooms():array {return $this->db->query('SELECT * FROM rooms ORDER BY number');}
    public function list(string $q=''):array {
        $sql='SELECT b.*,r.number,r.category FROM bookings b JOIN rooms r ON r.id=b.room_id';
        return $this->db->query($sql.($q!==''?' WHERE b.guest LIKE ? OR r.number LIKE ?':'').' ORDER BY b.id DESC',$q!==''?['%'.$q.'%','%'.$q.'%']:[]);
    }
    public function find(int $id):?array {return $this->db->query('SELECT * FROM bookings WHERE id=?',[$id])[0]??null;}
    private function date(string $v):bool {$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return $d!==false && $d->format('Y-m-d')===$v;}
    public function save(array $p,int $id=0):void {
        $guest=trim((string)($p['guest']??''));$phone=trim((string)($p['phone']??''));$room=filter_var($p['room_id']??null,FILTER_VALIDATE_INT);$guests=filter_var($p['guests']??null,FILTER_VALIDATE_INT);$a=(string)($p['arrival']??'');$d=(string)($p['departure']??'');
        if($guest===''||strlen($guest)>240||!preg_match('/^[\p{L}\s.\-]+$/u',$guest)) throw new DomainException('Укажите корректное имя гостя.');
        if(!preg_match('/^\+?[0-9 ()\-]{7,25}$/D',$phone)||strlen(preg_replace('/\D/','',$phone))<7) throw new DomainException('Укажите корректный телефон.');
        if(!$room||!$guests||$guests<1) throw new DomainException('Проверьте номер и количество гостей.');
        if(!$this->date($a)||!$this->date($d)||$d<=$a) throw new DomainException('Дата выезда должна быть позже даты заезда.');
        $days=(int)(new DateTimeImmutable($a))->diff(new DateTimeImmutable($d))->days;
        if($days>365) throw new DomainException('Период проживания не более 365 ночей.');
        $this->db->begin();
        try {
            $r=$this->db->query('SELECT * FROM rooms WHERE id=?'.($this->db->sqlite?'':' FOR UPDATE'),[$room])[0]??null;
            if(!$r||$guests>(int)$r['capacity']) throw new DomainException('Превышена вместимость номера или номер не найден.');
            if($id && (($this->find($id)['status']??null)!=='confirmed')) throw new DomainException('Бронь не найдена или отменена.');
            if($this->db->query("SELECT id FROM bookings WHERE room_id=? AND status='confirmed' AND arrival < ? AND departure > ? AND id <> ?",[$room,$d,$a,$id])) throw new DomainException('Номер уже забронирован на выбранные даты.');
            $v=[$room,$guest,$phone,$guests,$a,$d,$days*(int)$r['price']];
            if($id) $this->db->query("UPDATE bookings SET room_id=?,guest=?,phone=?,guests=?,arrival=?,departure=?,price=? WHERE id=? AND status='confirmed'",[...$v,$id]);
            else $this->db->query('INSERT INTO bookings(room_id,guest,phone,guests,arrival,departure,price) VALUES(?,?,?,?,?,?,?)',$v);
            $this->db->commit();
        } catch(Throwable $e){$this->db->rollback();throw $e;}
    }
    public function cancel(int $id):void {if(($this->find($id)['status']??null)!=='confirmed') throw new DomainException('Активная бронь не найдена.');$this->db->query("UPDATE bookings SET status='cancelled' WHERE id=? AND status='confirmed'",[$id]);}
}
