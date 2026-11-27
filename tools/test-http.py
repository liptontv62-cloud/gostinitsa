import os,sys,json,sqlite3,pathlib,requests
from bs4 import BeautifulSoup
base=os.environ.get('HOTEL_URL','http://127.0.0.1:18765/'); db=os.environ.get('HOTEL_SQLITE','/tmp/hotel.sqlite'); out=pathlib.Path(os.environ.get('HOTEL_EVIDENCE','../evidence'));out.mkdir(exist_ok=True)
mode=sys.argv[1] if len(sys.argv)>1 else 'fixed'; results=[];s=requests.Session()
with sqlite3.connect(db) as c: c.execute('DELETE FROM bookings')
css=requests.get(base+'assets/style.css').text; js=requests.get(base+'assets/app.js').text
try:
 from playwright.sync_api import sync_playwright
 p=sync_playwright().start(); b=p.chromium.launch(executable_path='/usr/bin/chromium',args=['--no-sandbox']); page=b.new_page(viewport={'width':1366,'height':900})
except Exception: page=None

def screen(name,r,mobile=False):
 html=r.text.replace('<link rel="stylesheet" href="assets/style.css">','<style>'+css+'</style>').replace('<script defer src="assets/app.js"></script>',''); html=html.replace('</body>','<script>'+js+'</script></body>'); (out/f'{mode}_{name}.html').write_text(html)
 if page:
  try:
   page.set_viewport_size({'width':390 if mobile else 1366,'height':844 if mobile else 900});page.set_content(html,wait_until='load');page.screenshot(path=str(out/f'{mode}_{name}.png'),full_page=True)
  except Exception as e: print('render:',e)
def soup(r):return BeautifulSoup(r.text,'html.parser')
def txt(r,sel): el=soup(r).select_one(sel);return el.get_text(' ',strip=True) if el else ''
def get(v='overview',**params):return s.get(base,params={'view':v,**params})
def post(action,r,**params):return s.post(base,data={'csrf':soup(r).select_one('[name=csrf]')['value'],'action':action,**params})
def test(i,n,e,a,ok):results.append(dict(id=i,name=n,expected=e,actual=str(a),status='PASS' if ok else 'FAIL'))
def data(guest='Иванов Иван',room_id=1,arrival='2026-12-01',departure='2026-12-04',guests=2):return dict(guest=guest,room_id=room_id,arrival=arrival,departure=departure,guests=guests,phone='+79000000001')
r=get('login');screen('01_login',r);r=post('login',r,login='admin',password='wrong');a=txt(r,'.notice');test('TC01','Неверный пароль','Неверный логин или пароль.',a,a=='Неверный логин или пароль.');screen('02_wrong',r);r=post('login',r,login='admin',password='HotelAdmin!26');a=txt(r,'h1');test('TC02','Вход администратора','Обзор гостиницы',a,a=='Обзор гостиницы');screen('03_overview',r);r=get('rooms');a=len(soup(r).select('.cards .panel'));test('TC03','Номерной фонд','4 номера',a,a==4);screen('04_rooms',r)
r=get('form');screen('05_form',r);r=post('save',r,**data());a=txt(r,'tbody');test('TC04','Добавление','Иванов Иван и 7 500 ₽',a,'Иванов Иван' in a and '7 500 ₽' in a);screen('06_created',r);r=post('save',get('form'),**data('Петрова Анна',2,'2026-12-03','2026-12-05'));r=post('save',get('form'),**data('Сидоров Олег',1,'2026-12-02','2026-12-05'));a=txt(r,'.notice');test('TC05','Пересечение дат','Номер уже забронирован',a,'уже забронирован' in a);screen('07_overlap',r);r=post('save',get('form'),**data('Сидоров Олег',3,'2026-12-05','2026-12-04'));a=txt(r,'.notice');test('TC06','Обратные даты','Дата выезда позже заезда',a,'позже' in a);screen('08_dates',r);r=post('save',get('form'),**data('',3));a=txt(r,'.notice');test('TC07','Пустое имя','Укажите корректное имя гостя.',a,'корректное имя' in a);screen('08_empty',r)
r=get('bookings',q='Иванов');a=len(soup(r).select('tbody tr'));test('TC08','Поиск','1 строка',a,a==1);a=txt(r,'#count');test('TC09','Счётчик поиска','Найдено: 1',a,a=='Найдено: 1');screen('09_search',r);id=int(soup(r).select_one('input[name=id]')['value']);r=post('save',get('form',id=id),id=id,**data(departure='2026-12-05'));a=txt(r,'.notice');test('TC10','Сообщение редактирования','Изменения сохранены.',a,a=='Изменения сохранены.');screen('10_edit',r);a=txt(r,'tbody');test('TC11','Сохранение изменений','2026-12-05 и 10 000 ₽',a,'2026-12-05' in a and '10 000 ₽' in a);r=post('cancel',r,id=id);a=next(x.select_one('.status').get_text(strip=True) for x in soup(r).select('tbody tr') if 'Иванов Иван' in x.get_text());test('TC12','Статус отмены','Отменена',a,a=='Отменена');screen('11_cancel',r);r=post('save',get('form'),**data('Сидоров Олег',1,'2026-12-02','2026-12-04'));a=txt(r,'tbody');test('TC13','Повторная бронь отменённого периода','Сидоров Олег',a,'Сидоров Олег' in a);r=get('bookings');a=len(soup(r).select('tbody tr'));test('TC14','Сохранение после нового запроса','3 строки',a,a==3);screen('12_persist',r);r=get('form');screen('13_mobile',r,True);a=page.evaluate('document.documentElement.scrollWidth <= innerWidth') if page else None;test('TC15','Мобильная форма (рендер ответа)','Нет горизонтальной прокрутки',a,a is True);r=post('logout',r);r=post('login',r,login='viewer',password='HotelViewer!26');r=get('bookings');screen('14_viewer',r);a=len(soup(r).select('a[href="?view=form"]'));test('TC16','Роль наблюдатель','Кнопка создания отсутствует',a,a==0);r=get('form');test('TC17','Прямой доступ наблюдателя','HTTP 403',r.status_code,r.status_code==403);screen('15_forbidden',r);r=s.post(base,data={'action':'cancel','id':id});test('TC18','Запрос без CSRF','HTTP 403',r.status_code,r.status_code==403)
with sqlite3.connect(db) as c: rows=c.execute('SELECT b.id,r.number,b.guest,b.arrival,b.departure,b.status,b.price FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY b.id').fetchall()
(out/f'{mode}_results.json').write_text(json.dumps(results,ensure_ascii=False,indent=2));(out/f'{mode}_db.json').write_text(json.dumps(rows,ensure_ascii=False,indent=2));print('\n'.join(f"{r['id']} {r['status']} {r['name']}" for r in results));
if page:b.close();p.stop()
