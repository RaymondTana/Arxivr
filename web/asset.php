<?php require 'config.php';
$sid=(int)($_GET['sid']??0);
$path=ltrim($_GET['path']??'', '/');
$st=$pdo->prepare('SELECT assets_zip FROM snapshots WHERE id=?');
$st->execute([$sid]);
if(!$zip=$st->fetchColumn()) die('no asset');
$z=new ZipArchive();
$z->open('php://memory', ZipArchive::RDONLY);
$z->addFromString('tmp',$zip); // workaround; real impl should stream properly
$stream=$z->getStream($path); if(!$stream) die('404');
$finfo=finfo_open(FILEINFO_MIME_TYPE); header('Content-Type:'.finfo_buffer($finfo, stream_get_contents($stream,0,0))); rewind($stream);
fpassthru($stream);