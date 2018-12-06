<?php

function dirsize( $d )
{ 
  $size = 0; 
  $dh = opendir( $d ); 
  while( ( $files = readdir( $dh ) ) !== false ) 
   { 
    if ( $files != "." && $files != ".." ) 
     { 
      $path = $d . "/" . $files; 
      if( is_dir( $path ) ) 
       {  $size += dirsize( $path , $count1 );  } 
      elseif( is_file( $path ) ) 
       {  $size += filesize($d . '/' . $files);  } 
     } 
   } 
  closedir($dh); 
  $size=$size/1024;
  $size=round($size,2);
  return $size; 
}


function clear_files($dir,$expire_time,$max_file_size)
{
// переводим дни в секунды
$expire_time=$expire_time*24*60*60;
// проверяем, что $dir - каталог
if (is_dir($dir))
{
	// открываем каталог
	if ($dh = opendir($dir))
    {
		// читаем и выводим все элементы
		// от первого до последнего
		while (($file = readdir($dh)) !== false)
    	{
			// текущее время
			$time_sec=time();
			// время изменения файла
			$time_file=filemtime($dir . $file);
			// тепрь узнаем сколько прошло времени (в секундах)
			$time=$time_sec-$time_file;
			$filepath = $dir.$file;
         	
			if (is_file($filepath))
        	{
				if ($time>$expire_time)	unlink($filepath); // удаление старых файлов
             	if ($max_file_size!='') // если задан $max_file_size
                {
             		if (filesize($filepath)>$max_file_size*1024) unlink($filepath);
                }
			}
		}
		// закрываем каталог
		closedir($dh);
	}
}
 
}


function clear_history()
{
$properties = SQLSelect("SELECT * FROM properties WHERE KEEP_HISTORY>0");
   $cnt=count($properties);
   for($i=0; $i<$cnt; $i++) {
   		$propid=$properties[$i]['ID'];
    	$keep=$properties[$i]['KEEP_HISTORY'];
    	$pvals=SQLSelect("SELECT * FROM pvalues WHERE PROPERTY_ID=$propid");
    	$cnt2=count($pvals);
    		for ($j=0; $j<$cnt2; $j++) {
            $id=$pvals[$j]['ID'];
            SQLExec("DELETE FROM phistory WHERE VALUE_ID=$id AND ADDED < DATE_SUB(NOW(), INTERVAL $keep DAY)");
            }
   }

// Оптимизация таблицы phistory БД
safe_exec("mysqlcheck -o db_terminal phistory -u ".DB_USER." -p".DB_PASSWORD);
}


function backup_majordomo($type,$ftp_backup)
{
	chdir(ROOT);
	include_once("./config.php");
	include_once("./lib/loader.php");
	include_once("./lib/threads.php");

	// connecting to database
	$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);

	include_once("./load_settings.php");
	include_once(DIR_MODULES . "saverestore/saverestore.class.php");

	$sv=new saverestore();

	global $design;
	global $code;
	global $data;
	global $save_files;
	$data=1;
	$design=1;
	$code=1;
	$save_files=1;	
	if ($type=="db") {
	$design=0;
	$code=0;
	$save_files=0;
	}
	$res=$sv->dump($out, 1);
	$sv->removeTree(ROOT.'cms/saverestore/temp');
	
	// Кидаем бэкап на ftp-сервер
	if ($ftp_backup) {
		$filepath = ROOT.'cms/saverestore/';
		foreach(glob($filepath . '*.tgz') as $file) {
		// Получаем последний добавленный/измененный файл
		$LastModified[] = filemtime($file); // массив файлов со временем изменения файла
		$FileName[] = $file; // массив всех файлов
		}
		 
		// Сортируем массив с файлами по дате изменения
		$files = array_multisort($LastModified, SORT_NUMERIC, SORT_ASC, $FileName);
		$lastIndex = count($LastModified) - 1;
		// И вот он наш последний добавленный или измененный файл
		$LastModifiedFile =  $FileName[$lastIndex];
		
		echo "Файл для загрузки на ftp: $LastModifiedFile \n";
		// Загружаем файл на FTP
		
		$ftp_server=gg("backup_ftp_server");
		$ftp_user_name=gg("backup_ftp_login");
		$ftp_user_pass=gg("backup_ftp_password");
		$ftp_error=false;
		if ($ftp_server=='') $ftp_error=true;
		if ($ftp_user_name=='') $ftp_error=true;
		if ($ftp_user_pass=='') $ftp_error=true;
		if (!$ftp_error) ftp_send($LastModifiedFile,$ftp_server,$ftp_user_name,$ftp_user_pass);
		
	}
	
}

// Отправка файла на FTP сервер в свою папку
function ftp_send($filename,$ftp_server,$ftp_user_name,$ftp_user_pass)
{
	$remote_folder=gg("homeName");
	if ($remote_folder=='') $remote_folder=gg("ThisComputer.Serial");
	
	//$remote_file = "/".$remote_folder.'/'.basename($filename);
	$remote_file = basename($filename);
	$log="";
	
	if ($conn_id = ftp_connect($ftp_server))
	{
		echo "Соединение установлено\n";
		$log.="Соединение установлено\n";
	}
	else
	{
		echo "Подключение к серверу не удалось\n";
		$log.="Подключение к серверу не удалось\n";
		say("Ошибка при выгрузке на FTP: \n".$log,2);
		return;
	}
	
	if ($login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass)) 
	{
		echo "Логин успешен \n";
		$log.="Логин успешен \n";
	}
	else 
	{
		echo "Неправильный логин или пароль \n";
		$log.="Неправильный логин или пароль \n";
		say("Ошибка при выгрузке на FTP: \n".$log,2);
		return;
	}
	// Включаем соединение в пассивном режиме
	ftp_pasv($conn_id, true);
	
	// Меняем директорию для бэкапа
	if (ftp_chdir($conn_id, $remote_folder)) {
    echo "Перехожу в директорию: " . ftp_pwd($conn_id) . "\n";
	$log.="Перехожу в директорию: " . ftp_pwd($conn_id) . "\n";
	} else { 
		echo "Не удалось сменить директорию, пробую её создать\n";
		// попытка создания директории $dir
		if (ftp_mkdir($conn_id, $remote_folder)) {
			echo "Создана директория $remote_folder\n";
			$log.="Создана директория $remote_folder\n";
		} else {
			echo "Не удалось создать директорию $remote_folder\n";
			$log.="Не удалось создать директорию $remote_folder\n";
			say("Ошибка при выгрузке на FTP: \n".$log,2);
		}
	}
	
	if (ftp_put($conn_id, $remote_file, $filename, FTP_ASCII)) {
		echo "$remote_file успешно загружен на сервер\n";
		$log.="Файл $remote_file успешно загружен на сервер\n";
	} else {
		echo "Не удалось загрузить $file на сервер\n";
		$log.="Не удалось загрузить $file на сервер\n";
		say("Ошибка при выгрузке на FTP: \n".$log,2);
	}
	
	ftp_close($conn_id);
}


function sync_favorit($branch="master")
{
	$url = "https://github.com/Fav0rit/MajorDomo/raw/$branch/favorit.tar.gz";

	if (!is_dir(ROOT . 'cms/saverestore'))
	{
    @umask(0);
    @mkdir(ROOT . 'cms/saverestore', 0777);
	}

        $filename = ROOT . 'cms/saverestore/favorit.tar.gz';
        @unlink(ROOT . 'cms/saverestore/favorit.tar.gz');

		$f = fopen($filename, 'wb');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FILE, $f);
        $incoming = curl_exec($ch);

        curl_close($ch);
        fclose($f);

		// unpack archive
		@chdir(ROOT);
		exec('tar xzvf ./cms/saverestore/favorit.tar.gz --overwrite-dir', $output, $res);
		
	say("Синхронизация скриптов завершена",0);
	favoritInit();
}

function calcSunsetSunrise() {
$lat=gg('ThisComputer.latitude');   // широта
$long=gg('ThisComputer.longitude'); // долгота
if ($lat=='') {
	$lat=51.72; //Kursk
	sg('ThisComputer.latitude',$lat);
}
if ($long=='') {
	$long=36.16; //Kursk
	sg('ThisComputer.longitude',$long);
}

$sun_info = date_sun_info(time(), $lat, $long);

	foreach ($sun_info as $key => $val)
	{
		if ($key == 'sunrise')
		{
		$sunrise = $val;
		//echo 'Восход: '.date("H:i", $sunrise).'<br>';
		setGlobal('ThisComputer.SunRiseTime',date("H:i", $sunrise));
		}

		if ($key == 'sunset')
		{
		$sunset = $val;
		$day_length = $sunset - $sunrise;
		
		setGlobal('ThisComputer.SunSetTime',date("H:i", $sunset));
		setGlobal('ThisComputer.LongTag',gmdate("H:i", $day_length));
		}

		if ($key == 'transit')
		{
		//echo 'В зените: '.date("H:i", $val).'<br>';
		//setGlobal('ThisComputer.Transit',date("H:i", $val));
		}

		if ($key == 'civil_twilight_begin')
		{
		//echo 'Начало утренних сумерек: '.date("H:i", $val).'<br>';
		//setGlobal('ThisComputer.civil_begin',date("H:i:s", $val));
		}

		if ($key == 'civil_twilight_end')
		{
		//echo 'Конец вечерних сумерек: '.date("H:i", $val).'<br>';
		//setGlobal('ThisComputer.civil_end',date("H:i", $val));
		}
	}
}

function favoritInit() {
	if (gg("calcSunsetSunrise")=='') sg("calcSunsetSunrise",1);
	if (gg("everydayBackupDB")=='') sg("everydayBackupDB",1);
	if (gg("everydayClearHistory")=='') sg("everydayClearHistory",0);
	if (gg("everydaySyncFavorit")=='') sg("everydaySyncFavorit",1);
	if (gg("autoClearDebmesBackup")=='') sg("autoClearDebmesBackup",1);
	if (gg("autoCheckFreePace")=='') sg("autoCheckFreePace",1);
	if (gg("autoCheckDebmesSize")=='') sg("autoCheckDebmesSize",1);
	
	// Добавляем ежеминутное выполнение скрипта newMinute.php если его нет
	$Record = SQLSelectOne("SELECT * FROM objects WHERE TITLE='ClockChime'");
	$objID = $Record['ID'];

	$Record = SQLSelectOne("SELECT * FROM methods WHERE TITLE='onNewMinute' AND OBJECT_ID=$objID");
	$code=$Record['CODE'];

	if ((strpos($code,"require(ROOT.'scripts/newMinute.php');"))==false)
	{
		 $Record['CODE']=$code."\n\n"."require(ROOT.'scripts/newMinute.php');";
		 SQLUpdate('methods', $Record);
	}
}