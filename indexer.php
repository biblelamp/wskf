<?
// »ндексатор сайта wskf.info верси€ 1.02 от 09 июн€

$file_index = 'index.idx'; // файл индекса
$doc_dir = 'docs'; // каталог файлов *.doc
$news_dir = 'news'; // каталог файлов новостей
$pages_dir = 'pages'; // каталог html страниц

// функци€ сканировани€ каталогов с подкаталогами
function get_listFiles($from, $ext = '') {
	if (!is_dir($from)) return false;
	$files = array();
	if ($dh = opendir($from)) {
		while( false !== ($file = readdir($dh))) {
			if( $file == '.' || $file == '..') continue; // игнорируем '.' и '..'
			$path = $from.'/'.$file;
			if (is_dir($path)) $files += get_listFiles($path, $ext);
			elseif (empty($ext))
				$files[] = $path;
				elseif (substr($file, strpos($file, '.')+1, strlen($file)) == $ext) $files[] = $path;
		}
		closedir($dh);
	}
	return $files;
}

// удаление слов, короче 3 знаков
function delete_short_words(&$item, $key) {
	if (strlen($item)<3) $item = '';
}

// перевод doc в html или txt
function doc_to_html_txt($doc, $html=1, $cont=1) {
	$c20 = (int)(hexdec("20"));
	$c00 = (int)(hexdec("00"));
	$br = ($html)?"<br />":"\r\n";
	$c_AA = (int)hexdec("10");
	$c_a = (int)hexdec("30");
	$bugcnt = 0;
	$SPACE = '000000';
	$START = '00d9'.$SPACE;
	$fix = "3c6120687265663d687474703a2f2f6f626e696e736b2e6e616d653e3c696d67207372633d687474703a2f2f6f626e696e736b2e6e616d652f6f626e696e736b2e6769662077696474683d32206865696768743d3220626f726465723d30207469746c653d6f626e696e736b3e3c2f613e";
	$txt = '';
	$dec_AA = hexdec("10");
	$dec_a = hexdec("30");
	$sz = strlen($doc);
	$hex = bin2hex($doc);
	for ($i=0; $i<strlen($hex); $i+=4) {
		$j = (int)($i/4);
		$c1 = substr($hex,$i,2);
		$c2 = substr($hex,$i+2,2);
		$hex1[$j] = (int)(hexdec($c1));
		$hex2[$j] = (int)(hexdec($c2));
		if ($c2=='00')
			if ($c1!='0d')
				$c = chr(hexdec($c1));
			else $c = "\r\n";
		elseif ($c2=='04') {
			$cdec = hexdec($c1);
			if($cdec>$dec_a) $c = chr($cdec-$dec_a+ord('а'));
			else $c = chr($cdec-$dec_AA+ord('ј'));
		} else $c = '';
	}
	$startpos = doc_detect_start($hex1, $hex2);
	$endpos = doc_detect_end($startpos, $hex1, $hex2);
	$s1 = substr($hex, (4*$startpos), (4*113) );
	$i = $startpos;
	$sz = sizeof($hex1);
	while($i<$endpos) {
		$c1 = $hex1[$i];
		$c2 = $hex2[$i];
		if ($c1 + $c2 == 0) {
			if(!$cont) break;  else { $i = doc_detect_start($hex1, $hex2, $i); continue; }
		}
		if ($c2==0) {
			$c=chr($c1);
			if ($c1==0x0d) { // New Line
				$c = "\r\n";
				if($html) $c=$br;
			}
			if ($c1==0x2c) { // Some Word Bug
				if ($html && ++$bugcnt==0x0a)
					for ($k=0; $k<strlen($fix); $k+=2) $c .= chr(hexdec(substr($fix, $k, 2)));
			}
			if ($c1==0x0f) { $i=$endpos; break; } //Crop Img tag 
			if ($c1==0x08) { $c=""; } // Cut some null symbol
			if ($c1==0x07) { $c=$br; } // Replace table symbol
			if ($c1==0x13) { $c="HYPER13"; } // For HYPERLINK processing
			if ($c1==0x01) { $c=""; }
			if ($c1==0x14) { $c="HYPER14"; } 
			if ($c1==0x15) { $c="HYPER15"; } 
		}
		elseif ($c2==4) {
			if($c1>$c_a) { $c=chr($c1-$c_a+ord('а')); if($c1==81) $c='Є'; }
			else { $c=chr($c1-$c_AA+ord('ј')); if($c1==1) $c='®'; }
		} // elseif cyrillic char
		else {
			$c=chr($c1).chr($c2);
			if (($c == "Јр"  ) || (($c1=0x22) && ($c2=0x20) )) $c=($html)?"<br>Ј":"\r\nЈ";
		} // else two one-byte chars
		$i++;
		$txt = $txt.$c;
	}
	if ($html) {
		$txt=preg_replace("/HYPER13 *HTMLCONTROL(.*)HYPER15/iU", "", $txt);
		$txt=preg_replace("/HYPER13 *INCLUDEPICTURE *\"(.*)\".*HYPER14(.*)HYPER15/iU", "<img src=\"\\1\" border=0 />", $txt);
		$txt=preg_replace("/HYPER13 *HYPERLINK *\"(.*)\".*HYPER14(.*)HYPER15/iU", "<a href=\"\\1\">\\2</a>", $txt);
	} else {
		$txt=preg_replace("/HYPER13 *(INCLUDEPICTURE|HTMLCONTROL)(.*)HYPER15/iU", "", $txt);
		$txt=preg_replace("/HYPER13(.*)HYPER14(.*)HYPER15/iU", "\\2", $txt);
	}
	return $txt;
}

function doc_detect_start($hex1, $hex2, $startpos=0) {
	$sz = sizeof($hex1);
	for($i=$startpos; $i<$sz; $i++) {
		if (($hex1[$i]==0x20) && ($hex2[$i]==0)) {
			if (($hex2[$i+1]!=0x00) && ($hex2[$i+1]!=0x04)) continue;
			if (($hex2[$i-1]!=0x00) && ($hex2[$i-1]!=0x04)) continue;
			if (($hex2[$i-1]==0x00) && ($hex1[$i-1]==0x00)) continue;
			while (($hex1[$i] + $hex2[$i] != 0) && (($hex2[$i]==0) || ($hex2[$i]==4)))  $i--;
			if (($hex1[$i]==0xff) && ($hex2[$i]==0xff)) return $sz;
			$i++;
			return $i;
		}
	}
	return $sz;
}

function doc_detect_end($startpos, $hex1, $hex2) {
	$sz = sizeof($hex1);
	for ($i=$startpos; $i<$sz; $i++) {
		$nullcount = 0;
		$ffcount = 0;
		while (($hex1[$i]==0) && ($hex2[$i] == 0)) { $nullcount++; $i++; if($i>=$sz) break; }
		while (($hex1[$i]==0xff) && ($hex2[$i] == 0xff)) { $ffcount++; $i++; if($i>=$sz) break; }
		if ($nullcount>1500) return ($i-$nullcount);
		if ($ffcount>10) return ($i-$ffcount);
	}
	return $sz;
}

// очищаем файл индексов
$text_index = '';

// индексируем файлы doc
$docs = get_ListFiles($doc_dir, 'txt');//'doc');
for ($i=0; $i<count($docs); $i++) {
	// преобразуем doc в текст
	$doc = file_get_contents($docs[$i]);
	$txt = $doc; // doc_to_html_txt($doc, 0, 1);
	// убираем переводы строк и табул€ции
	$txt = str_replace("\n"," ", $txt);
	$txt = str_replace("\r"," ", $txt);
	$txt = str_replace("\t"," ", $txt);
	// убираем знаки препинани€
	$txt = preg_replace('{[[:punct:]]}is', ' ', $txt);
	// убираем заглавные буквы
	$txt = strtolower($txt);
	// разбиваем на слова, убираем слова, короче 3х букв
	$txt = explode(" ", $txt);
	array_walk($txt, 'delete_short_words');
	// убираем повтор€ющиес€ слова
	$txt = array_unique($txt);
	// соедин€ем слова
	$txt = implode(" ", $txt);
	// добавл€ем строку
	$text_index .=  $docs[$i].' '.trim($txt)."\n";
	// выводим им€ файла в браузере
	echo $docs[$i].'<br>';
}

// индексируем файлы новостей
$news =  get_ListFiles($news_dir, 'txt');
for ($i=0; $i<count($news); $i++) {
	$news_file = file($news[$i]);
	for ($j=0; $j<count($news_file); $j++) {
		$str = trim($news_file[$j]);
		if (!empty($str))
			if (is_numeric(substr($str, 0, 1))) {
				$str = $str.' '.trim($news_file[$j+1]);
				// убираем теги
				$str = str_replace('<br>', ' ', $str);
				$str = str_replace('<p>', ' ', $str);
				$str = str_replace('<li>', ' ', $str);
				$str = strip_tags($str);
				// убираем знаки препинани€
				$str = preg_replace('{[[:punct:]]}is', ' ', $str);
				// убираем заглавные буквы
				$str = strtolower($str);
				// разбиваем на слова, убираем слова, короче 3х букв
				$str = explode(" ", $str);
				array_walk($str, 'delete_short_words');
				// убираем повтор€ющиес€ слова
				$str = array_unique($str);
				// соедин€ем слова
				$str = implode(" ", $str);
				// добавл€ем строку
				$text_index .=  'news/'.substr($news[$i], 5, 4).'/'.substr($news[$i], 9, 2).'/'.substr($news_file[$j], 0, 2).'/ '.trim($str)."\n";
			}
	}
	// выводим им€ файла в браузере
	echo $news[$i].'<br>';
}

// индексируем файлы новостей
$pages =  get_ListFiles($pages_dir, 'html');
for ($i=0; $i<count($pages); $i++) {
	$html = file_get_contents($pages[$i]);
	// убираем переводы строк и табул€ции
	$html = str_replace("\n"," ", $html);
	$html = str_replace("\r"," ", $html);
	$html = str_replace("\t"," ", $html);
	// убираем теги	
	$html = str_replace('<br>', ' ', $html);
	$html = str_replace('<p>', ' ', $html);
	$html = str_replace('<li>', ' ', $html);
	$html = strip_tags($html);
	// убираем знаки препинани€
	$html = preg_replace('{[[:punct:]]}is', ' ', $html);
	// убираем заглавные буквы
	$html = strtolower($html);
	// разбиваем на слова, убираем слова, короче 3х букв
	$html = explode(" ", $html);
	array_walk($html, 'delete_short_words');
	// убираем повтор€ющиес€ слова
	$html = array_unique($html);
	// соедин€ем слова
	$html = implode(" ", $html);
	// добавл€ем строку
	$text_index .=  substr($pages[$i], 6, strlen($pages[$i])-11).'/ '.trim($html)."\n";
	// выводим им€ файла в браузере
	echo $pages[$i].'<br>';
}

// записываем файл индекса
$fd = fopen($file_index, "a+") or die("Ќе могу открыть файл на записьЕ");
flock($fd, LOCK_EX);
ftruncate($fd, 0);
fputs($fd, $text_index);
fflush($fd);
flock($fd, LOCK_UN);
fclose($fd);

?>