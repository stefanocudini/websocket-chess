<?

$conf = json_decode( file_get_contents('conf.json') ,true);
//legge configurazione da json

//$hostws = 'easyblog.it';
$hostws = $conf['hostws'];
$portws = $conf['portws'];

$hostphp = $conf['hostphp'];
$portphp = $conf['portphp'];

$pedfile = "tmp/%s.txt";
$turnfile = "tmp/turno.txt";

?>
