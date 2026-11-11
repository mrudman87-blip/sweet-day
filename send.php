<?php
/* =====================================================================
   send.php — принимает заявку с формы и пересылает её в Telegram.
   Работает на любом хостинге с PHP 8+ (Beget, Timeweb, REG.RU …).
   Токен бота лежит в config.php — посетители сайта его не видят.
   ===================================================================== */

header('Content-Type: application/json; charset=utf-8');

// Ответ браузеру в формате JSON и выход
function reply(int $code, array $data): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Принимаем только POST (отправку формы), а не простое открытие адреса в браузере
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    reply(405, ['ok' => false, 'error' => 'Только POST']);
}

$config = require __DIR__ . '/config.php';

// 2. Ловушка для спам-ботов (honeypot): поле «website» скрыто от людей.
//    Человек его не видит и не заполнит, а бот заполняет всё подряд.
if (!empty($_POST['website'])) {
    reply(200, ['ok' => true]);              // делаем вид, что всё хорошо, и молча выбрасываем
}

// 3. Ограничение частоты: не больше 3 заявок с одного адреса за 10 минут
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/sweetday_' . md5($ip);
$times = is_file($rateFile) ? array_filter(
    explode(',', (string)file_get_contents($rateFile)),
    fn($t) => (int)$t > time() - 600
) : [];
if (count($times) >= 3) {
    reply(429, ['ok' => false, 'error' => 'Слишком много заявок. Попробуйте через 10 минут или позвоните.']);
}

// 4. Забираем поля и чистим их (обрезаем пробелы и слишком длинный текст)
function field(string $name, int $max): string {
    $value = trim((string)($_POST[$name] ?? ''));
    return mb_substr($value, 0, $max);
}
$name  = field('name', 60);
$phone = field('phone', 25);
$date  = field('date', 10);
$wish  = field('wish', 1000);

// 5. Проверка на сервере. Проверке в браузере доверять нельзя: её легко обойти.
$digits = preg_replace('/\D/', '', $phone);
if ($name === '' || strlen($digits) !== 11) {
    reply(422, ['ok' => false, 'error' => 'Проверьте имя и телефон.']);
}
if (empty($_POST['agree'])) {
    reply(422, ['ok' => false, 'error' => 'Нужно согласие на обработку данных.']);
}

// 6. Собираем сообщение. htmlspecialchars — защита: текст из формы не сможет сломать разметку.
$e = fn(string $s) => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
$dateText = $date ? date('d.m.Y', strtotime($date)) : 'не указана';
$text = "🎂 <b>Новая заявка с сайта</b>\n\n"
      . "👤 {$e($name)}\n"
      . "📞 <a href=\"tel:+{$digits}\">{$e($phone)}</a>\n"
      . "📅 Дата: {$e($dateText)}\n"
      . ($wish !== '' ? "💬 {$e($wish)}\n" : '')
      . "\n🕒 " . date('d.m.Y H:i');

// 7. Отправляем в Telegram через curl
$ch = curl_init("https://api.telegram.org/bot{$config['token']}/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'chat_id'    => $config['chat_id'],
        'text'       => $text,
        'parse_mode' => 'HTML',
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
if (PHP_OS_FAMILY === 'Windows') {
    // На компьютере с Windows берём сертификаты из системы (как браузер), иначе антивирус/прокси,
    // проверяющие HTTPS, дают ошибку «self-signed certificate». На хостинге (Linux) не нужно.
    curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
}
if (!empty($config['proxy'])) {             // если хостинг не достаёт до Telegram напрямую
    curl_setopt($ch, CURLOPT_PROXY, $config['proxy']);
}
$response = curl_exec($ch);
$result = json_decode((string)$response, true);

if (!($result['ok'] ?? false)) {
    // Ошибку пишем в журнал сервера (там её увидит разработчик), а посетителю — понятный текст
    error_log('Telegram error: ' . ($response ?: curl_error($ch)));
    reply(502, ['ok' => false, 'error' => 'Не удалось отправить заявку. Позвоните нам, пожалуйста.']);
}

$times[] = time();
file_put_contents($rateFile, implode(',', $times));
reply(200, ['ok' => true]);
