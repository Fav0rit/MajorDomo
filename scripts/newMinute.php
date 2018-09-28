<?php

$h=(int)date('G',time());
$m=date('i',time());

// Рассчитываем время восхода и захода Солнца
if (timeIs("01:00")) {
	calcSunsetSunrise();
}

// Делаем бэкап базы данных
if (timeIs("02:30")) {
	backup_majordomo("db");
}

// Запускаем синхронизацию файлов
if (timeIs("03:00")) {
	sync_favorit();
}

// Очищаем историю
if (timeIs("03:30")) {
	clear_history();
}

if (timeIs("04:00")) {
// Удаляем в папке saverestore файлы старше 30 дней
$folder = ROOT.'cms/saverestore/';
clear_files($folder,30,'');

// Чистим логи
$folder = ROOT.'cms/debmes/';
clear_files($folder,7,1);
}


// Каждые 10 минут контролируем размеры папок
if (($m%10)==0) {
$folder=ROOT.'cms/debmes';
$foldersize=dirsize($folder);
	if ($foldersize>10*1024)
	{
	say("Внимание! Папка ".$folder." занимает ".$foldersize."Kb"." запускаю очистку",2);
	clear_files($folder,7,1);
	}
}

// Каждые 15 минут проверяем свободное место на диске
if (($m%15)==0) {
$max_usage=90; //%
$output=array();
exec('df -h',$output);
//var_dump($output);
$fullOutput="";
$problems=0;
$problems_details='';
foreach($output as $line) {
 if (preg_match('/(\d+)% (\/.+)/',$line,$m))
   $proc=$m[1];
   $path=$m[2];
   $fullOutput.=$line."\n";
   if ($proc>$max_usage) {
    $problems++;
    $problems_details.="$path: $proc; ";
   }
   //echo "$path: $proc%<br/>";
}

// Оповещение
if (((gg("SpaceProblems"))==0)&&($problems!=0)) {
 $message="На диске заканчивается свободное место! \n";
 $message.=$fullOutput;
 say($message,3);
}

sg("ThisComputer.SpaceDetails",$fullOutput);
sg("ThisComputer.SpaceProblems",$problems);
sg("ThisComputer.SpaceProblems_Details",$problems_details);
}