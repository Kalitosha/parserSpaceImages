<?php
header('Content-type: text/html; charset=utf-8');
require_once 'phpQuery.php';

$db_name = 'wallpaperscraft'; // имя БД

$arrIm = array();
$arrSize = array();
$arrTags = array();

$url = 'http://wallpaperscraft.ru/catalog/space';
$content = getContent($url);

$arrIm = parseIm($content, $arrIm);
$arr = parseSizeAndTags($content, $arrSize, $arrTags);

for ($i=0; $i < count($arr); $i++) { 
 	if ($i < count($arr)/2) {
 		$arrSize[] = $arr[$i];
 	}
 	else $arrTags[] = $arr[$i];
 } 

function getContent($url){

	//$file = file_get_contents($url); // тащим контент // лучше использовать cURL

	$ch = curl_init(); // возвращает дескриптор подключения
	curl_setopt($ch, CURLOPT_URL, $url); // задаем url и параметры
	//curl_setopt($ch, CURLOPT_FILE, $fp); // запихнули содержимое в файл
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // для сохранения результата в виде строки в перем
	/***************************************************************/
	/*curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // для загрузки страниц 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //по https*/
	/***************************************************************/
	$content = curl_exec($ch); // выполняем соединение // в content находится весь контент
	curl_close($ch); // закрываем соединение
	return ($content);
}

function parseIm($content, $arrIm){ // парсим картиночки
	
	$doc = phpQuery::newDocument($content); // для работы с библ создаем объект библ

	$findCont = $doc->find('.wallpapers__canvas');

	foreach ($findCont as $el){
		$pq = pq($el);
		$pq = $pq->find('.wallpapers__image')->attr('src');

		$arrIm[] = $pq;
	}
	return $arrIm;
}

function parseSizeAndTags($content, $arrSize, $arrTags){ // парсим теги
	
	$doc = phpQuery::newDocument($content); // для работы с библ создаем объект библ

	$findCont = $doc->find('.wallpapers__info'); // указываем какой эл-т парсим
	$tumbler = true; 
	foreach ($findCont as $el) {
		$pq = pq($el); // $el - DOM Element
		$pq->find('.wallpapers__info-rating')->remove();
		$pq->find('.wallpapers__info-downloads')->remove();
		$pq = strip_tags($pq); // strip_tags - php команда убирает html теги
		
		if ($tumbler === true) {
			$arrSize [] = str_replace(array("\n"),"",$pq); // убираем переносы строк			
			$tumbler = false;
		}
		else{
			$arrTags [] = $pq;
			$tumbler = true;
		}
	}	
	return array_merge($arrSize, $arrTags);
}


/***********************with*PDO**********************************/
try {
  $db = new PDO('mysql:host=localhost;dbname='.$db_name, 'root', '');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch(PDOException $e) {
    echo $e->getMessage();
}

//getImages($db, 1);
selectALL($db);

 for ($i=0; $i < count($arrSize) ; $i++) { 
 
//	addToDB($db, $arrIm[$i], $arrSize[$i], $arrTags[$i]);
 } 

function once_addToDB($db, $urlIm, $size, $tags){
	echo '("'.$urlIm.'", "'.$size.'", "'.$tags.'")';

	$sql = $db->prepare("INSERT INTO space_im
			(bin_im, size, tags) 
	VALUES ('".$urlIm."', '".$size."', '".$tags."')"); 

    if (!$sql) {
        echo "Ошибка при добавлении записи в БД";
    }

    $db->exec($sql);
}

function addToDB($db, $urlIm, $size, $tags){
	echo '("'.$urlIm.'", "'.$size.'", "'.$tags.'")';

	$sql = $db->prepare("INSERT INTO space_im
			(bin_im, size, tags) 
	VALUES (?, ?, ?)");

    if (!$sql) {
        echo "Ошибка при добавлении записи в БД";
    }

	$sql->execute([$urlIm, $size, $tags]);	
}

/*function getImages($db, $numId){
	$sql = 'SELECT bin_im FROM space_im';// WHERE `id` ='.$numId; 
	$result = $db-> query($sql);

	if(!$result) exit("Ошибка выполнения SQL запроса!");
	else{
		while ($element = $result->fetch(PDO::FETCH_ASSOC)){ 
			echo '<img src='."{$element['bin_im']}".'">' ;
		}
	}	
	return $result;
}*/

function selectALL($db){
	$sql = "SELECT * FROM space_im";
	$result = $db-> query($sql);

 	$data = $result->fetchAll(PDO::FETCH_ASSOC); // задаем вывод в форме ассоц. массива
	foreach ($data as $el){
		/*echo "{$el['id']}. bin_im: {$el['bin_im']}, size: {$el['size']}, tags: {$el['tags']}. <br/>";// выборка всех записей в массив и вывод на экран */
	
	echo'<div id=parseImage>
			<br>'.$el['size'].'</br>			
			<img src = "'.$el['bin_im'].'">
			<p>'.$el['tags'].'</p>
		 </div>';

	}

	/*
	echo "<h2>вывод записей результата по одной</h2>";
	// fetch - Извлечение следующей строки из результирующего набора
	// FETCH_ASSOC: возвращает массив, индексированный именами столбцов результирующего набора
	while ($ims = $result->fetch(PDO::FETCH_ASSOC)){ 

		echo "{$ims['id']}. bin_im: {$ims['bin_im']}, size: {$ims['size']}, tags: {$ims['tags']}. <br/>";
	}
	*/
}

$db = null; //Закрытие соединения
/*******************with*mysqli****************************************/
/*
$link = mysqli_connect('localhost', 'root', '', $db_name); // устанавливаем соединение с бд

if (mysqli_connect_errno()) {
	echo 'Ошибка подключения к БД ('.mysqli_connect_errno().'): '.mysqli_connect_error();
	exit(); // если произошла ошибка, останавливаем выполнение скрипта
}

function getImages($link, $numId){
	$sql = 'SELECT `size` FROM `space_im` WHERE `id` ='.$numId; // выбирает инфу из бд по sql запросу
	$result = mysqli_query($link, $sql);

	if(!$result) exit("Ошибка выполнения SQL запроса!");
	else{
		//$r = mysqli_fetch_array($result)
		//echo $r['size'].'<br>';
		//echo mysqli_fetch_array($result).'<br>';
	}
	
	return $result;
}

function addToDB($link, $urlIm, $size, $tags){

	$sql = mysqli_query($link, 'INSERT INTO `space_im`
			(`bin_im`, `size`, `tags`) 
	VALUES ("'.$urlIm.'", "'.$size.'", "'.$tags.'")');

    if (!$sql) {
        echo "Ошибка при добавлении записи в БД (INSERT)";
    }
}
*/
/*for ($i=0; $i < count($arrIm); $i++) { 
	echo '
		<div id=parseImage>
			<br>'.$arrSize[$i].'</br>			
			<img src = "'.$arrIm[$i].'">
			<p>'.$arrTags[$i].'</p>
		</div>';
}*/
?>
