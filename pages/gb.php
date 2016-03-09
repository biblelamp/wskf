<?
// �������� ����� ��� ����� wskf.info � 2009, ������ 1.03 �� 16 ���

$gb_file = 'gb.log'; // ���� � ��������
$valid_email_pattern = "/^([a-z0-9\._-])+@([a-z0-9_-])+(\.[a-z0-9_-]+)+$/i";
$admin_email = 'biblelamp@yandex.ru'; // e-mail ��������������
$email_copy = 'wskfrostov@mail.ru'; // �����

// �������� ����������
$error_msg = $action = $name = $email = $city = $body = '';

// ��������� ����� ������, ��������� ������ �����
if (isset($_POST['action'])) {
	$action = $_POST['action'];
	$name = $_POST['name'];
	$email = $_POST['email'];
	$city = $_POST['city'];
	$body = $_POST['body'];
	$mode = $_POST['mode'];
}

// ������ ���� ���������
if (file_exists($gb_file)) $gb_text = str_replace("\r", "", join("", file($gb_file))); else $gb_text = '';

// ���� ������ ����� ���������� ������
if ($action == 'send') {

	// ��������� ������
	if (trim($name) == "") $error_msg .= '������� ��� ���. ';
	if (preg_match('/[0-9a-z]+/i', $name)) $error_msg .= '��������� ����� � ����� � ����� ���������. ';
	if ($email == "") $error_msg .= '������� ���� ���������� e-mail. ';
		elseif (!preg_match($valid_email_pattern, $email))
			$error_msg .= '������� ���������� e-mail. ';
	if ($mode == 'unsubscribe') { // ���� �� �������� - ��������� ���������� ����� ������ � ���������
		if (trim($city) == "") $error_msg .= '������� ���� ����� � ������. ';
		if (preg_match('/[0-9a-z]+/i', $city)) $error_msg .= '��������� ����� � ����� � �������� ������ ���������. ';
		if (trim($body) == "") $error_msg .= '��������� ����� ���������. ';
	}

	// ������ ��� - ���������� ���������
	if (empty($error_msg)) {

		// ����������� ���� $body
		$body = strip_tags($body); // ������� ����
		$body = str_replace("\r\n", " ", $body); // �������� �������� ����� ���������
		while (strpos($body, "  ")) $body = str_replace("  ", " ", $body); // ������� ������� �������

		// ��������� ������
		$gb_text = '*'.date("d.m.y H:i")."|".$name."|".$email."|".$city."|".$body."||\n".$gb_text;

		// ��������� � ����������� � ���������
		$fd = fopen($gb_file, "a+") or die("�� ���� ������� ���� �� �������");
		flock($fd, LOCK_EX);
		ftruncate($fd, 0);
		fputs($fd, $gb_text);
		fflush($fd);
		flock($fd, LOCK_UN);
		fclose($fd);

		// ���������� �� ����� ����� �������
		mail($admin_email,
			"�������� [wskf.info]",
			$name."\n".$email."\n".$city."\n".$body.' ('.$mode.')',
			"From: ".$name." <".$email.">\nCC: ".$email_copy."\nContent-Type: text/plain; charset=windows-1251");

		// ������� ���� �����
		$name=$email=$city=$body = '';
	}
}

// ������� ��������������
echo '<p><em>��������� ����������!</em><blockquote>��� ������ �������� ������������ � ����������� <strong>������ ����� ��������</strong>. ���������, �� ����������� � �������� ����� �����, �� �����������.</blockquote></p>';

$name=$email=$city=$body = '';

if (!empty($error_msg)) echo '<hr><p><em>������</em>: <strong>��������� �� ����������</strong> �� ������� ��������� ������:<br><span style="color:red">'.$error_msg.'<span></p>'; elseif (!empty($action)) echo '<p><em>������</em>: <strong>��������� ������� ����������</strong>.</p>';
?><p><hr><blockquote><strong>�������� ���</strong>:</p>
<p><form action="/gb" method="post">
	<input type="hidden" name="action" value="send">
	<strong>���� ���</strong> (������� �� ��������):
	<br><input type="text" name="name" size="80%" value="<? echo $name ?>" required>
	<br>��� <strong>e-mail</strong>:
	<br><input type="text" name="email" size="80%" value="<? echo $email ?>" required>
	<br>��� <strong>�����</strong>:
	<br><input type="text" name="city" size="80%" value="<? echo $city ?>">
	<br>���� <strong>���������</strong> (������, ���������):
	<br><textarea cols="60%" rows="5" name="body" required><? echo $body ?></textarea>
	<br><input type="radio" name="mode" checked value="subscribe"> ��������� �� ������� �����
	<input type="radio" name="mode" value="unsubscribe"> �� ���� �������� �������
	<br><br><input type="submit" value="���������">
</form>
</blockquote>
</p><?

// ��������� ���� ��������� �� �������
$gb_array = split("\n", $gb_text);

// ������� ��������� ��������
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