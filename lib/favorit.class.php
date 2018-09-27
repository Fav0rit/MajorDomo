<?php

function dirsize( $d ) { 
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