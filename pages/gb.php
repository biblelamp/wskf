<?
// Гостевая книга для сайта wskf.info © 2009, версия 1.03 от 16 мая

$gb_file = 'gb.log'; // файл с записями
$valid_email_pattern = "/^([a-z0-9\._-])+@([a-z0-9_-])+(\.[a-z0-9_-]+)+$/i";
$admin_email = 'biblelamp@yandex.ru'; // e-mail администратора
$email_copy = 'wskfrostov@mail.ru'; // копия

// обнуляем переменные
$error_msg = $action = $name = $email = $city = $body = '';

// проверяем режим работы, считываем данные формы
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	$name = $_POST['name'];
	$email = $_POST['email'];
	$city = $_POST['city'];
	$body = $_POST['body'];
	$mode = $_POST['mode'];
}

// читаем файл сообщений
if (file_exists($gb_file)) $gb_text = str_replace("\r", "", join("", file($gb_file))); else $gb_text = '';

// если выбран режим добавления записи
if ($action == 'send') {

	// проверяем данные
	if (trim($name) == "") $error_msg .= 'Укажите своё имя. ';
	if (preg_match('/[0-9a-z]+/i', $name)) $error_msg .= 'Латинские буквы и цифры в имени запрещены. ';
	if ($email == "") $error_msg .= 'Укажите свой контактный e-mail. ';
		elseif (!preg_match($valid_email_pattern, $email))
			$error_msg .= 'Укажите правильный e-mail. ';
	if ($mode == 'unsubscribe') { // если не подписка - проверяем заполнение полей города и сообщения
		if (trim($city) == "") $error_msg .= 'Укажите свой город и страну. ';
		if (preg_match('/[0-9a-z]+/i', $city)) $error_msg .= 'Латинские буквы и цифры в названии города запрещены. ';
		if (trim($body) == "") $error_msg .= 'Заполните текст сообщения. ';
	}

	// ошибок нет - отправляем сообщение
	if (empty($error_msg)) {

		// подправляем поле $body
		$body = strip_tags($body); // убираем теги
		$body = str_replace("\r\n", " ", $body); // заменяем переводы строк пробелами
		while (strpos($body, "  ")) $body = str_replace("  ", " ", $body); // удаляем двойные пробелы

		// добавляем запись
		$gb_text = '*'.date("d.m.y H:i")."|".$name."|".$email."|".$city."|".$body."||\n".$gb_text;

		// открываем с блокировкой и добавляем
		$fd = fopen($gb_file, "a+") or die("Не могу открыть файл на запись…");
		flock($fd, LOCK_EX);
		ftruncate($fd, 0);
		fputs($fd, $gb_text);
		fflush($fd);
		flock($fd, LOCK_UN);
		fclose($fd);

		// отправляем по почте копию реплики
		mail($admin_email,
			"Гостевая [wskf.info]",
			$name."\n".$email."\n".$city."\n".$body.' ('.$mode.')',
			"From: ".$name." <".$email.">\nCC: ".$email_copy."\nContent-Type: text/plain; charset=windows-1251");

		// очищаем поля ввода
		$name=$email=$city=$body = '';
	}
}

// выводим предупреждение
echo '<p><em>Уважаемые посетители!</em><blockquote>Все записи проходят премодерацию и размещаются <strong>только после проверки</strong>. Сообщения, не относящиеся к тематике этого сайта, не публикуются.</blockquote></p>';

$name=$email=$city=$body = '';

if (!empty($error_msg)) echo '<hr><p><em>Статус</em>: <strong>Сообщение не отправлено</strong> по причине следующих ошибок:<br><span style="color:red">'.$error_msg.'<span></p>'; elseif (!empty($action)) echo '<p><em>Статус</em>: <strong>Сообщение успешно отправлено</strong>.</p>';
?><p><hr><blockquote><strong>Напишите нам</strong>:</p>
<p><form action="/gb" method="post">
	<input type="hidden" name="action" value="send">
	<strong>Ваше имя</strong> (фамилия не помешает):
	<br><input type="text" name="name" size="80%" value="<? echo $name ?>" required>
	<br>Ваш <strong>e-mail</strong>:
	<br><input type="text" name="email" size="80%" value="<? echo $email ?>" required>
	<br>Ваш <strong>город</strong>:
	<br><input type="text" name="city" size="80%" value="<? echo $city ?>">
	<br>Ваше <strong>сообщение</strong> (вопрос, пожелание):
	<br><textarea cols="60%" rows="5" name="body" required><? echo $body ?></textarea>
	<br><input type="radio" name="mode" checked value="subscribe"> подпишусь на новости сайта
	<input type="radio" name="mode" value="unsubscribe"> не хочу получать новости
	<br><br><input type="submit" value="отправить">
</form>
</blockquote>
</p><?

// разбиваем файл сообщений по строкам
$gb_array = split("\n", $gb_text);

// выводим сообщения гостевой
for ($i=0; $i<count($gb_array); $i++) {
	list($date, $name, $email, $city, $body, $reply) = split("\|", $gb_array[$i]);
	if (substr($date, 0, 1) != '*') {
		$date = substr($date, 0, 2)." ".$month[substr($date, 3, 2)-1]." 20".substr($date, 6, 8);
		if (!empty($email)) $name = "<script language='JavaScript'>document.write('<a href=\"mailto:'+'".substr($email, 0, strpos($email, "@"))."'+'&#64;'+'".substr($email, strpos($email, "@")+1)."'+'\">')</script>".$name."</a>";
		if (!empty($city)) $city = ", ".$city;
		echo '<p><span class="date">'.$date.'</span> | '.$name.$city.'<br>'.$body;
		if ($reply != '') echo '<blockquote>'.$reply.'</blockquote>';
		echo "</p>";
	}
}