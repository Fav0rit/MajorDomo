https://docs.google.com/document/d/1W_8YkBUr_X_oxMaF0uBniM06Xp1on52qZkAT70pf6lg/edit#heading=h.12in1asq2bv1

// Для авторазвертывания выполняем такой скрипт



$url = 'https://github.com/Fav0rit/MajorDomo/raw/master/favorit.tar.gz';

        if (!is_dir(ROOT . 'cms/saverestore')) {
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
//@mkdir(ROOT . 'cms/saverestore/temp', 0777);
//@chdir(ROOT . 'cms/saverestore/temp');
@chdir(ROOT);
exec('tar xzvf ./cms/saverestore/favorit.tar.gz --overwrite-dir', $output, $res);

