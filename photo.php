<?
// ���������� ���������� ������� �� ������ // 14 ����� 2011�
// ����������� ������ � �������: 214, 216, 263, 265
// images/photo/'.$str[$j]
// �������� ��:
// images/photo/'.$str[0].'/'.$str[$j]

$photos = file('photos.log');
for ($k=0; $k<count($photos); $k++) {
	$str = explode(' ', trim($photos[$k]));
	mkdir('images/photo/'.$str[0]);
	for ($l=1; $l<count($str); $l++) {
		copy('images/photo/'.$str[$l].'.jpg', 'images/photo/'.$str[0].'/'.$str[$l].'.jpg');
		unlink('images/photo/'.$str[$l].'.jpg');
	}
	echo $str[0].'<br>';
}

?>