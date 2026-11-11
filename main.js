// =====================================================================
// Сладкий день — немного JavaScript. Всё остальное делает HTML и CSS.
// =====================================================================

// 1. Маска телефона: человек вводит цифры, а мы показываем +7 (900) 000-00-00
const phone = document.querySelector('#phone');

function formatPhone(value) {
  let d = value.replace(/\D/g, '');            // оставляем только цифры
  if (d.startsWith('8')) d = '7' + d.slice(1);  // 8 900... → 7 900...
  if (!d.startsWith('7')) d = '7' + d;
  d = d.slice(0, 11);
  const p = [d.slice(1, 4), d.slice(4, 7), d.slice(7, 9), d.slice(9, 11)];
  let out = '+7';
  if (p[0]) out += ' (' + p[0];
  if (p[0].length === 3) out += ')';
  if (p[1]) out += ' ' + p[1];
  if (p[2]) out += '-' + p[2];
  if (p[3]) out += '-' + p[3];
  return out;
}

phone.addEventListener('input', () => { phone.value = formatPhone(phone.value); });
phone.addEventListener('focus', () => { if (!phone.value) phone.value = '+7 '; });
phone.addEventListener('blur', () => { if (phone.value.trim() === '+7') phone.value = ''; });

// 2. Мобильное меню: после нажатия на пункт — закрываем панель
const menu = document.querySelector('#menu');
menu.querySelectorAll('a').forEach(link =>
  link.addEventListener('click', () => { if (menu.matches(':popover-open')) menu.hidePopover(); })
);

// 3. Отправка заявки на сервер (send.php), а он пересылает её в Telegram.
//    fetch отправляет данные «в фоне» — страница не перезагружается.
const form = document.querySelector('#order-form');
const thanks = document.querySelector('#thanks');
const errorBox = document.querySelector('#form-error');
// Демо-режим: на GitHub Pages PHP не работает, поэтому там заявка никуда не отправляется
const DEMO = location.hostname.endsWith('github.io');

form.addEventListener('submit', async (event) => {
  event.preventDefault();                       // не перезагружаем страницу
  const button = form.querySelector('button[type="submit"]');
  button.disabled = true;                       // защита от двойного нажатия
  button.textContent = 'Отправляем…';
  errorBox.hidden = true;

  try {
    if (DEMO) {                                 // демо-версия для портфолио: сервера с PHP нет
      await new Promise(r => setTimeout(r, 500));
      thanks.querySelector('p:not(.dialog__emoji)').textContent =
        'Это демо-версия сайта для портфолио. На сайте заказчика заявка сразу приходит владельцу в Telegram.';
      form.reset();
      thanks.showModal();
      return;
    }
    const response = await fetch('send.php', { method: 'POST', body: new FormData(form) });
    const result = await response.json();
    if (!result.ok) throw new Error(result.error);

    form.reset();
    thanks.showModal();                         // встроенное модальное окно <dialog>
  } catch (error) {
    // Нет интернета, сервер недоступен или он вернул ошибку — показываем понятный текст
    errorBox.textContent = error.message && !error.message.includes('fetch')
      ? error.message
      : 'Не удалось отправить заявку. Проверьте интернет или позвоните нам.';
    errorBox.hidden = false;
  } finally {
    button.disabled = false;
    button.textContent = 'Отправить заявку';
  }
});

// 4. Текущий год в подвале — чтобы не править вручную каждый январь
document.querySelector('#year').textContent = new Date().getFullYear();
