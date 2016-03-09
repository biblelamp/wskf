<?
// Сайт Федерации WSKF России © 2009, версия 1.18 от 14 Марта 2011г

$sections = array('federation'=>'ФЕДЕРАЦИЯ', 'clubs'=>'КЛУБЫ', 'team'=>'СБОРНАЯ', 'competitions'=>'СОРЕВНОВАНИЯ, СЕМИНАРЫ, СБОРЫ', 'articles'=>'СТАТЬИ, ИНТЕРВЬЮ, БИОГРАФИИ', 'news'=>'НОВОСТИ ФЕДЕРАЦИИ', 'photos'=>'ФОТОАЛЬБОМ', 'shop'=>'МАГАЗИН', 'gb'=>'ГОСТЕВАЯ КНИГА', 'links'=>'ССЫЛКИ');

$month = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
$Umonth = array('Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь');

$headtitle = ''; // название раздела в <title></title>
$maxhead = 8; // максимальное число заголовков на 1й странице
$maxlength = 360; // максимальное число символов в анонсе новости
$maxadvt = 6; // максимальное число анонсов
$titleleftcolumn = ''; // заголовок левой колонки
$photo_linetopage = 4; // количество записей фотоальбома на страницу

/***********************************************************************************
Функция img_resize(): генерация thumbnails
Параметры:
	$src			- имя исходного файла
	$dest		- имя генерируемого файла
	$width, $height - ширина и высота генерируемого изображения, в пикселях
Необязательные параметры:
	$rgb		- цвет фона, по умолчанию - белый
	$quality	- качество генерируемого JPEG, по умолчанию - максимальное (100)
***********************************************************************************/
function img_resize($src, $dest, $width, $height, $rgb=0xFFFFFF, $quality=100) {
	if (!file_exists($src)) return false;

	$size = getimagesize($src);

	// В случае, если формат файла не распознан, getimagesize возвращает false
	if ($size === false) return false;

	// Определяем исходный формат по MIME-информации, предоставленной
	// функцией getimagesize, и выбираем соответствующую формату
	// imagecreatefrom-функцию
	$format = strtolower(substr($size['mime'], strpos($size['mime'], '/')+1));
	$icfunc = "imagecreatefrom" . $format;
	if (!function_exists($icfunc)) return false;

	$x_ratio = $width / $size[0];
	$y_ratio = $height / $size[1];

	$ratio = min($x_ratio, $y_ratio);
	$use_x_ratio = ($x_ratio == $ratio);

	$new_width = $use_x_ratio  ? $width  : floor($size[0] * $ratio);
	$new_height = !$use_x_ratio ? $height : floor($size[1] * $ratio);

	$isrc = $icfunc($src); // загружаем исходный файл

	$idest = imagecreatetruecolor($new_width, $new_height); // создаем новый файл
	imagefill($idest, 0, 0, $rgb); // заполняем фон
	imagecopyresampled($idest, $isrc, 0, 0, 0, 0, $new_width, $new_height, $size[0], $size[1]); // копируем, сжимая
	imagejpeg($idest, $dest, $quality); // определяем качество

	imagedestroy($isrc);
	imagedestroy($idest);

	return true;
}

// разбираем строку запроса
$REQUEST = split("/", $_SERVER['REQUEST_URI']);

// чистим текст левой колонки
$text ='';
$advt = '';

// работаем с файлом календарного плана
$today = date('Y/m/d');
$add = 0;
if (substr($today, 5, 2)>'08') $add = 1;
$calendar = file('calendar.log');
$text = ''; // страница плана соревнований текущего года
$pages = array(); // список ссылок на страницы
$count = 0; // счетчик выведенных анонсов
$j = 0; // начинаем с 1й строки
// определяем стартовую и конечную даты
if (!empty($REQUEST[2])) $startyear = $REQUEST[2]; else $startyear = substr($today, 0, 4)+$add;
$sdate = ($startyear-1).'/08/31';
$edate = ($startyear).'/09/01';
// собираем анонсы и формируем страницу соревнований
do { // собираем анонсы
	$dt = substr($calendar[$j], 0, 10);
	if (substr($dt, 7, 1) != '/') $dt = substr($dt, 0, 7).'/31'; // если указан только месяц
	// формируем анонсы
	if (($today <= $dt) && ($count<$maxadvt)) {
		$count++;
		if (substr($calendar[$j], 7, 1) != '/') $dts = $Umonth[substr($calendar[$j], 5, 2)-1].' '.substr($calendar[$j], 0, 4);
		else $dts = substr($calendar[$j], 8, 2).' '.$month[substr($calendar[$j], 5, 2)-1].' '.substr($calendar[$j], 0, 4);
		if (strlen($calendar[$j+4]) > 2) $url = '<br><a href="/docs/'.trim($calendar[$j+4]).'"><img src="/images/w.gif" width="13" style="margin: 0 4px 0 0; border: 0">положение</a>'; else $url = '';
		$advt .= '<p><strong>'.$dts.'</strong><br>'.trim($calendar[$j+1]).', '.trim($calendar[$j+2]).' '.trim($calendar[$j+3]).$url.'</p>';
	}
	// заполняем страницу плана соревнований
	if ($REQUEST[1] == 'competitions') { // раздел Соревнования, сборы, семинары
		// заполняем список периодов
		if (!in_array(substr($dt, 0, 4), $pages)) $pages[] = substr($dt, 0, 4);
		// событие попало в выбранный интервал
		if (($dt > $sdate) && ($dt < $edate)) { // событие попало в интервал
			// работаем с датой
			if (substr($calendar[$j], 7, 1) != '/') $dt = $Umonth[substr($calendar[$j], 5, 2)-1].' '.substr($calendar[$j], 0, 4);
			elseif (substr($calendar[$j], 10, 1) == '-') {
				if (substr($calendar[$j], 13, 1) == '/') $dt = 'С '.substr($calendar[$j], 8, 2).' '.$month[substr($calendar[$j], 5, 2)-1].' по '.substr($calendar[$j], 11, 2).' '.$month[substr($calendar[$j], 14, 2)-1].' '.substr($calendar[$j], 0, 4);
				else $dt = 'С '.substr($calendar[$j], 8, 2).' по '.substr($calendar[$j], 11, 2).' '.$month[substr($calendar[$j], 5, 2)-1].' '.substr($calendar[$j], 0, 4);
			}
			else $dt = substr($calendar[$j], 8, 2).' '.$month[substr($calendar[$j], 5, 2)-1].' '.substr($calendar[$j], 0, 4);
			// определяем соревнования, входящие в рейтинг
			$red_style_start = $red_style_end = '';
			if (substr(trim($calendar[$j]), -1) == '*') {
				$red_style_start = '<span style="color:red">';
				$red_style_end = '</span>';
			}
			// формируем ссылку для скачивания положения
			if (strlen($calendar[$j+4]) > 2) $url = '<br><a href="/docs/'.trim($calendar[$j+4]).'"><img src="/images/w.gif" width="13" style="margin: 0 4px 0 0; border: 0">положение</a>'; else $url = '';
			// формируем ссылку для скачивания протокола
			if (strlen($calendar[$j+5]) > 2) $url .= '<br><a href="/docs/'.trim($calendar[$j+5]).'"><img src="/images/w.gif" width="13" style="margin: 0 4px 0 0; border: 0">итоговый протокол</a>';
			// формируем ссылку для перехода к новости
			if (strlen($calendar[$j+6]) > 2) $url .= ' • <a href="/news/'.trim($calendar[$j+6]).'">отчет+фото »»»</a>';
			// добавляем событие в список
			$text .= '<p>'.$red_style_start.'<strong>'.$dt.'</strong><br>'.trim($calendar[$j+1]).'<br>'.trim($calendar[$j+2]).' '.trim($calendar[$j+3]).$red_style_end.$url.'</p>';
		}
	}
	$j = $j+7;
} while ($j<count($calendar)-1);
$advt .= '<p><a href="/competitions">все события »»»</a></p>';

// если это 1я страница или раздел новостей - сканируем новости
if (empty($REQUEST[1]) || ($REQUEST[1]=='news')) {
	// собираем строки новостей
	unset($news);
	$d = opendir('news');
	while (($e = readdir($d)) != false) {
		if (@is_dir($e)) continue; // игнорируем каталоги
		if (substr($e, strpos($e, '.'), strlen($e)) != '.txt') continue; // игнорируем не txt
		$news[] = substr($e, 0, strpos($e, '.')); // отрезаем расширение
	}
	closedir($d);
	rsort($news); // сортируем
}

// 1я страница - заголовки новостей слева
if (empty($REQUEST[1])) {
	// назначаем заголовок левой колонки
	$titleleftcolumn ='НОВОСТИ ФЕДЕРАЦИИ';
	$count = 0; // счетчик выведенных заголовков
	$j = -1; // начинаем с самого свежего файла
	do { // выводим заголовки новостей
		$j++;
		$date = $news[$j];
		$hotnews = file('news/'.$date.'.txt');
		for ($i=0; $i<count($hotnews) && $count<$maxhead; $i++) {
			$str = trim($hotnews[$i]);
			if (!empty($str))
				if (is_numeric(substr($str, 0, 1))) {
					$count++;
					$day = substr($str, 0, 2);
					$URL = '/news/'.substr($date, 0, 4).'/'.substr($date, 4, 2).'/'.$day;
					$title = substr($str, 3);
					$msg = $hotnews[$i+1];
					$msg = str_replace('<p>', ' ', $msg);
					$msg = str_replace('<ul>', ' ', $msg);
					$msg = str_replace('<ol>', ' ', $msg);
					$msg = str_replace('<li>', ' ', $msg);
					if ((strlen($msg) > $maxlength) && (strpos($msg, ' ', $maxlength) == True))
						$msg = substr($msg, 0, strpos($msg, ' ', $maxlength)).'…&nbsp;<a href="'.$URL.'/">далее »»»</a></span>';
					else $msg .= '</span>';
					if (file_exists('news/'.$date.$day.'.jpg')) $img = '<img src="/news/'.$date.$day.'.jpg" alt="'.$title.'.">';
					else $img = '';
					$text .= "\t\t".'<p><a href="'.$URL.'/" class="title">'.$img.$title.'</a><br><span class="date">'.$day.' '.$month[substr($date, 4, 2)-1].' '.substr($date, 0, 4).' года</span><br>'.$msg.'</p><br>'."\n";
				}
		}
	} while ($count<$maxhead && $j<count($news)-1);
	// добавляем форму для поисковых запросов
	/*$advt .=
'		<h2 style="background-image: url(/images/bg_right_down.gif);">ПОИСК ПО САЙТУ</h2>
		<form method="POST" action="/search/">
		<p><input type="text" name="text" size="28" class="input"> <input type="submit" value="Найти" style="font-size: 11px">
		</form></p>'."\n";
	*/
	// добавляем секцию Документы (правая колонка)
	$advt .= join('', file('pages/documents.html'));
} else { // вызов раздела или страницы в разделе
	foreach ($sections as $key=>$value) {
		if ($key == $REQUEST[1]) {
			$headtitle = ' | '.$value;
			$titleleftcolumn = $value;
		}
	}
	if ($REQUEST[1] == 'news') { // раздел Новости федерации
		$newslinks = '';
		if (empty($REQUEST[2])) $REQUEST[2] = $REQUEST[3] = $REQUEST[4] = ''; // избавляемся от ошибок неопределенности
		for ($i=0; $i<count($news); $i++) {
			$hotnews = file('news/'.$news[$i].'.txt');
			for ($j=0; $j<count($hotnews); $j++)
				if (is_numeric(substr($hotnews[$j], 0, 1)))
					// дата новости совпадает с датой в адресной строке
					if (($news[$i] == $REQUEST[2].$REQUEST[3]) && (substr($hotnews[$j], 0, 2) == $REQUEST[4])) {
						// выводим текст новости
						$datenews = $news[$i].substr($hotnews[$j], 0, 2);
						$titlenews = trim(substr($hotnews[$j], 3));
						if (file_exists('news/'.$datenews.'.jpg')) $img = '<img src="/news/'.$datenews.'.jpg">';
						else $img = '';
						$text = '<p>'.$img.'<span class="title">'.$titlenews.'</span><br><span class="date">'.substr($datenews, 6, 2).' '.$month[substr($datenews, 4, 2)-1].' '.substr($datenews, 0, 4).' года</span><br>'.$hotnews[$j+1];
						// добавляем фотографии (если они есть)
						$photos = file('photos.log');
						for ($k=0; $k<count($photos); $k++)
							if (substr($photos[$k], 0, 8) == $REQUEST[2].$REQUEST[3].$REQUEST[4]) {
								$text .= '</p><p>';
								$str = explode(' ', trim($photos[$k]));
								for ($l=1; $l<count($str); $l++) {
									if (!file_exists('images/photo/thumbnails/'.$str[$l].'.jpg'))
										img_resize('images/photo/'.$str[0].'/'.$str[$l].'.jpg', 'images/photo/thumbnails/'.$str[$l].'.jpg', 124, 124);
									if ($l == count($str)-1) $float_none = ' style="float: none"'; else $float_none = '';
									$image = getimagesize('images/photo/'.$str[0].'/'.$str[$l].'.jpg');
									$thumbnails = getimagesize('images/photo/thumbnails/'.$str[$l].'.jpg');
									$text .= '<a href="javascript:openPhoto(\'/images/photo/'.$str[0].'/'.$str[$l].'.jpg\',\''.$str[0].'\','.$image[0].','.$image[1].')"><img src="/images/photo/thumbnails/'.$str[$l].'.jpg" width="'.$thumbnails[0].'" height="'.$thumbnails[1].'" vspace="10" border="0"'.$float_none.'></a>';
								}
							}
						$text .= '</p>';
					} else {
						// выводим ссылку на новость
						$datenews = $news[$i].substr($hotnews[$j], 0, 2);
						$titlenews = trim(substr($hotnews[$j], 2));
						if (file_exists('news/'.$datenews.'.jpg')) $img = '<a href="/news/'.substr($datenews, 0, 4).'/'.substr($datenews, 4, 2).'/'.substr($datenews, 6, 2).'/"><img src="/news/'.$datenews.'.jpg" width="45" alt="'.$titlenews.'." style="margin-right: 6px"></a>';
						else $img = '';
						$newslinks .= '<p>'.$img.'<strong>'.substr($datenews, 6, 2).' '.$month[substr($datenews, 4, 2)-1].' '.substr($datenews, 0, 4).'</strong><br><a href="/news/'.substr($datenews, 0, 4).'/'.substr($datenews, 4, 2).'/'.substr($datenews, 6, 2).'/">'.$titlenews.'</a><br>'.$hotnews[$j+2].'</p>';
					}
		}
		if (!empty($text)) $text .= '<hr>'.$newslinks;
		else $text = $newslinks;
	} elseif ($REQUEST[1] == 'photos') { // раздел Фотогалерея
		$photos = file($REQUEST[1].'.log');
		if (empty($REQUEST[2])) $page = 1; else $page = $REQUEST[2];
		$pages = ceil(count($photos)/$photo_linetopage); // количество страниц
		// формируем ссылки на страницы
		$urls = '';
		if ($pages > 1) {
			$urls = '<div style="text-align:center;font-size:16px;font-weight:bold">';
			for ($i=1; $i<=$pages; $i++) {
				if ($i == $page) $urls .= $i;
				else $urls .= '<a href="/'.$REQUEST[1].'/'.$i.'/">'.$i.'</a>';
				if ($i<$pages) $urls .= ' • ';
			}
			$urls .= "</div>";
		}
		$text .= $urls;
		$page_begin = ($page-1)*$photo_linetopage;
		$page_end = min(count($photos), $page*$photo_linetopage);
		for ($i=$page_begin; $i<$page_end; $i++) {
			$str = explode(' ', trim($photos[$i]));
			$text .= '<p><span style="font-size:14px;font-weight:bold">'.substr($str[0], 6, 2).' '.$month[substr($str[0], 4, 2)-1].' '.substr($str[0], 0, 4).' г</span><br>';
			$count = 0;
			for ($j=1; $j<count($str); $j++) {
				// считаем фото в строке
				$count++;
				// если нет файла thumbnails
				if (!file_exists('images/photo/thumbnails/'.$str[$j].'.jpg'))
					img_resize('images/photo/'.$str[0].'/'.$str[$j].'.jpg', 'images/photo/thumbnails/'.$str[$j].'.jpg', 124, 124);
				if (($j == count($str)-1) || ($count = 4)) {
					$count = 0;
					$float_none = ' style="float: none"';
				} else $float_none = ''; // принудительный переход на новую строку
				$image = getimagesize('images/photo/'.$str[0].'/'.$str[$j].'.jpg');
				$thumbnails = getimagesize('images/photo/thumbnails/'.$str[$j].'.jpg');
				$text .= '<a href="javascript:openPhoto(\'/images/photo/'.$str[0].'/'.$str[$j].'.jpg\',\''.$str[0].'\','.$image[0].','.$image[1].')"><img src="/images/photo/thumbnails/'.$str[$j].'.jpg" width="'.$thumbnails[0].'" height="'.$thumbnails[1].'" vspace="10" border="0"'.$float_none.'></a>';
			}
		}
		$text .= $urls.'<br>';
	} elseif ($REQUEST[1] == 'competitions') { // раздел Соревнования, сборы, семинары
		// формируем ссылки
		$urls = '<div style="text-align:center;font-size:16px">';
		for ($i=0; $i<count($pages); $i++) {
			if ($pages[$i] == substr($sdate, 0, 4)) $urls .= '<strong>'.($pages[$i]).'-'.($pages[$i]+1).'</strong>';
			else $urls .= '<a href="/competitions/'.($pages[$i]+1).'/">'.($pages[$i]).'-'.($pages[$i]+1).'</a>';
			if ($i<count($pages)-1) $urls .= ' • ';
		}
		$urls .= '</div>';
		$text = $urls.$text.$urls.'<p><strong>Примечание</strong>:<br><span style="color:red">Красным цветом</span> отмечены соревнования, входящие в рейтинг.</p>'."\n";
	} elseif ($REQUEST[1] == 'search') { // раздел Поиск по сайту
			$headtitle = ' | ПОИСК';
			$titleleftcolumn = 'ПОИСК';
			$text = '&nbsp;';
	} else { // Предопределенный (статический) раздел/подраздел
		if (file_exists('pages/'.$REQUEST[1].'.html')) // статическая страница
			if (isset($REQUEST[2])) { // задана страницы 2 уровня, 1й уровень задается $REQUEST[1]
				if (file_exists('pages/'.$REQUEST[1].'/'.$REQUEST[2].'.html'))
					$text = join('', file('pages/'.$REQUEST[1].'/'.$REQUEST[2].'.html'));
			} else $text = join('', file('pages/'.$REQUEST[1].'.html'));
		elseif (file_exists('pages/'.$REQUEST[1].'.php')) { // Раздел php-скрипт
			ob_start();
			require('pages/'.$REQUEST[1].'.php');
			$text = ob_get_contents();
			ob_end_clean();
		}
	}
}
// обработаем ситуацию с пустым разделом
if ((!empty($REQUEST[1])) && (empty($text))) {
	$titleleftcolumn = 'НЕТ ТАКОЙ СТРАНИЦЫ (ОШИБКА 404)';
	$text = '<p>Иногда, когда Вы запрашиваете на сайте страницу, вместо нее выводится сообщение об ошибке. Сообщение сопровождается цифровым кодом.<p>Ошибка 404 обозначает, что запрошенному Вами адресу не соответствует никакая страница сайта. Причин тут может быть две: или ссылка неверна, или страница, существовавшая ранее, была удалена.<p>Получив вместо страницы сообщение об ошибке, тщательно проверьте написание адреса - возможно, Вы просто ошиблись при наборе. Кроме того, рекомендуем Вам перейти на <a href="/">первую страницу</a> и оттуда продолжить обзор сайта.';
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251">
<meta name="Description" content="Федерация W.S.K.F. России. О федерации, клубы, сборная, соревнования, сборы, семинары, статьи, новости, фотогалерея, магазин, гостевая книга, ссылки." />
<meta name="Keywords" content="каратэ, каратэ-до, шотокан, сетокан, wskf, w.s.k.f., федерация, Россия, клуб, соревнование" />
<meta name='yandex-verification' content='71c6bcfe9f85bde2' />
<link rel="icon" href="/favicon.ico" type="image/x-icon"> 
<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
<title>Федерация W.S.K.F. России<?echo $headtitle?></title>
<style type="text/css"><!--
	body {
		margin: 0 auto;
		padding: 0;
		border: 0;				/* This removes the border around the viewport in old versions of IE */
		font-family: Arial,sans-serif;
		font-size: 12px;
		background-image: url(/images/bg.gif);
		background-repeat: repeat-x;
	}
	h2 {
		margin: 0em 0em 1em 1em;
		color: white;
		padding-left: 15px;
		font-size: 13px;
		padding-top: 6px;
		padding-bottom: 5px;
	}
	p {
		text-align: left; /* justify; */
		margin: 1em 1em;
	}
	a {
		color: #336598;
	}
	a:hover {
		color: red;
	}
	img {
		margin-right: 10px;
		float: left;
		border: 0;
	}
	hr {
		height: 1px;
		background: #aaa;
		width: 96%;
		border: 0;
	}
	.page {
		width: 810px;
		margin: 0 auto;
	}
	.top_image {
		clear: both;
		float: left;
		margin-top: 13px;
		background: url(/images/top.jpg) no-repeat 50% top;
		width: 100%;
		position: relative;
		height: 278px;
	}
	.top_menu {
		font-family: Arial,sans-serif;
		font-weight: bold;
		font-size: 12px;
		color: #336598;
		letter-spacing: 0.45px;
		position: relative;
		top: 234px;
		text-align: center;
	}
	.top_menu A {
		color: #336598;
		text-decoration: none;
	}
	.top_menu A:hover {
		color: red;
	}
	#container {
		position: relative;
		margin: 0 auto;
		clear: both;
		overflow: hidden;
		width: 805px;
		border-bottom: #aaa 1px solid;
		border-left: #aaa 1px solid;
		border-right: #aaa 1px solid;
		}
	#container .col1 {
		float: left;
		background: #fff;
		width: 560px;
		border-right: #aaa 1px solid;
	}
	#container .col2 {
		width: 244px;
		float: right;
	}
	.title {
		color: red;
		font-size: 14px;
		font-weight: bold;
		text-decoration: none;
	}
	.title A {
		color: red;
		text-decoration: none;
	}
	.title A:hover {
		color: red;
		text-decoration: none;
	}
	.date {
		font-size: 11px;
		color: #999;
	}
	input,textarea {
		font-size: 12px;
		border: solid 1px #aaa;
	}
	input:focus,textarea:focus {
		border-color:#66afe9;outline:0;-webkit-box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6);box-shadow:inset 0 1px 1px rgba(0,0,0,.075),0 0 8px rgba(102,175,233,.6)
	}
	input[type=submit] {
		font-size: 14px;
		padding: 5px;
		border-radius: 4px;
		cursor: pointer;
	}
	.bottom_menu {
		position: relative;
		padding-top: 10px;
		height: 20px;
		width: 805px;
		color: #336598;
		text-align: center;
		font-size: 13px;
	}
	.footer {
		position: relative;
		top: 5px;
		padding-top: 1px;
		padding-bottom: 3px;
		height: 50px;
		width: 807px;
		background: #336598;
		color: white;
		background-image: url(/images/bg_bottom.gif);
		background-repeat: no-repeat;
		font-size: 11px;
	}
	.footer p {
		text-align: right;
	}
	.footer img {
		margin: 0 1px 0 10px;
		float: right;
	}
	.footer A {
		color: white;
	}
	.footer A:hover {
		color: white;
	}
//-->
</style>
<script language="Javascript"><!--
function openPhoto(url, description, width, height) {
	w = window.open("", "w", "width="+width+",height="+height+',left=20,top=20');
	d = w.document;
	d.open();
	d.write('<html><title>'+description+'</title><body leftmargin="0" topmargin="0" marginheight="0" marginwidth="0" onBlur="self.close()" onClick="self.close()"><table width="100%" height="100%" cellspacing="0" cellpadding="0" border="0"><tr><td><img src="'+url+'" width="'+width+'" height="'+height+'" alt="'+description+'"></td></tr></table></body></html>');
	d.close();
	w.focus();
}
function collapsElement(obj) { //,pic)
	var el = document.getElementById(obj);
	if ( el.style.display != "none" ) {
		el.style.display = 'none';
		//document.getElementById(pic).src = 'images/plus.gif';
	} else {
		el.style.display = '';
		//document.getElementById(pic).src = 'images/minus.gif';
	}
}
//-->
</script>
</head>
<body>
<div class="page">
<div class="top_image" onclick="location.href='/'" style="cursor: pointer">
<div class="top_menu">
	<a href="/federation">ФЕДЕРАЦИЯ</a> |
	<a href="/clubs">КЛУБЫ</a> |
	<a href="/team">СБОРНАЯ</a> |
	<a href="/competitions">СОРЕВНОВАНИЯ</a> |
	<a href="/articles">СТАТЬИ</a> |
	<a href="/news">НОВОСТИ</a> |
	<a href="/photos">ФОТО</a> |
	<a href="/shop">МАГАЗИН</a> |
	<a href="/gb">КОНТАКТЫ</a> |
	<a href="/links">ССЫЛКИ</a>
</div>
</div>
<div id="container">
	<div class="col1">
		<h2 style="background-image: url(/images/bg_left.gif);"><?echo $titleleftcolumn?></h2>
		<? echo $text; ?>
	</div>
	<div class="col2">
		<h2 style="background-image: url(/images/bg_right.gif);">АНОНС СОБЫТИЙ</h2>
		<? echo $advt; ?>
	</div>
</div>
<div class="bottom_menu">
	<a href="/">Главная</a> |
	<a href="/federation">Федерация</a> |
	<a href="/clubs">Клубы</a> |
	<a href="/team">Сборная</a> |
	<a href="/competitions">Соревнования</a> |
	<a href="/articles">Статьи</a> |
	<a href="/news">Новости</a> |
	<a href="/photos">Фотогалерея</a> |
	<a href="/shop">Магазин</a> |
	<a href="/gb">Гостевая книга</a> |
	<a href="/links">Ссылки</a>
</div>
<div class="footer">
	<p><a href="http://top.mail.ru/jump?from=1635724" target="_blank"><img src="http://d5.cf.b8.a1.top.mail.ru/counter?id=1635724;t=52" border="0" height="31" width="88" alt="Рейтинг@Mail.ru" /><span id="spylog2006033"></span><script type="text/javascript"> var spylog = { counter: 2006033, image: 25, next: spylog }; document.write(unescape('%3Cscript src%3D"http' + (('https:' == document.location.protocol) ? 's' : '') + '://counter.spylog.com/cnt.js" defer="defer"%3E%3C/script%3E')); </script></a>Федерация W.S.K.F. России &copy; 2009 тел. +7 (863) 290 22 45
	<br>Верстка и программирование <a href="https://linkedin.com/in/biblelamp/" target="_blank">Сергей Ирюпин</a>, дизайн <a href="https://www.behance.net/solomnikov/" target="_blank">Андрей Соломников</a>
</p>
</div>
</div>
</body>
</html>
