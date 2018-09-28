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
             		if (filesize($filepath)>$max_file_size*1024*1024) unlink($filepath);
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


function backup_majordomo($type)
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
}

function sync_favorit()
{
	$url = 'https://github.com/Fav0rit/MajorDomo/raw/master/favorit.tar.gz';

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
}

function calcSunsetSunrise() {
$lat=gg('ThisComputer.latitude');   // широта
$long=gg('ThisComputer.longitude'); // долгота
if ($lat=='') $lat=51.72; //Kursk
if ($long=='') $long=36.16; //Kursk

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