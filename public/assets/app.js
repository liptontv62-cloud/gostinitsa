'use strict';
for(const form of document.querySelectorAll('.cancel'))form.addEventListener('submit',e=>{if(!confirm('Отменить бронирование? Запись останется в журнале.'))e.preventDefault();});
const form=document.querySelector('#booking-form');
if(form){const fields=form.elements;function update(){const r=fields.room_id.selectedOptions[0];const price=Number(r?.dataset.price||0);const cap=Number(r?.dataset.capacity||4);fields.guests.max=cap;const a=Date.parse(fields.arrival.value),d=Date.parse(fields.departure.value),days=(d-a)/86400000;document.querySelector('#estimate').textContent=price&&days>0?`Стоимость: ${(days*price).toLocaleString('ru-RU')} ₽ · ${days} ноч.`:'Стоимость: —';}form.addEventListener('input',update);update();}
