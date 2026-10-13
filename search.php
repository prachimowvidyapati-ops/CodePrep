<?php
declare(strict_types=1);
require_once __DIR__.'/config.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');
function fail(string $message,int $status=400):void{http_response_code($status);echo json_encode(['error'=>$message]);exit;}
function getJson(string $url):array{
 $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>12,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: SmartPickDemo/1.0']]);
 $raw=curl_exec($ch);$status=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
 if($raw===false||$status<200||$status>=300)throw new RuntimeException('The external provider is currently unavailable.');
 return json_decode($raw,true,512,JSON_THROW_ON_ERROR);
}
function excerpt(string $s,int $limit=520):string{$s=trim(strip_tags($s));return mb_strlen($s)>$limit?mb_substr($s,0,$limit).'…':$s;}
$q=trim((string)($_GET['q']??''));$category=(string)($_GET['category']??'books');
if(mb_strlen($q)<2||mb_strlen($q)>120)fail('Enter between 2 and 120 characters.');
if(!in_array($category,['books','movies'],true))fail('Unsupported category.');
try{
 $items=[];
 if($category==='books'){
  // Use Google Books without keeping a large local catalogue.
  $search=preg_replace('/^(best|top|recommended)\s+/i','',$q);
  $search=preg_replace('/\s+(books?|recommendations?)(\s+for\s+beginners?)?$/i','',$search);
  $json=getJson('https://www.googleapis.com/books/v1/volumes?'.http_build_query(['q'=>$search,'maxResults'=>18,'printType'=>'books']));
  foreach(($json['items']??[]) as $b){$v=$b['volumeInfo']??[];
   $image=$v['imageLinks']['thumbnail']??'';if(str_starts_with($image,'http://'))$image='https://'.substr($image,7);
   $url=$v['infoLink']??'https://books.google.com/books?id='.rawurlencode($b['id']??'');
   $items[]=['category'=>'books','title'=>$v['title']??'Unknown title','subtitle'=>implode(', ',$v['authors']??[]),'description'=>excerpt($v['description']??''),'image'=>$image,'rating'=>isset($v['averageRating'])?(float)$v['averageRating']:null,'rating_scale'=>5,'votes'=>(int)($v['ratingsCount']??0),'url'=>$url,'review_url'=>$url,'reviews'=>[]];
  }
  $source='Google Books';
 }else{
  if(TMDB_API_KEY==='')fail('Movie search requires a TMDB API key. Add it to api/config.php.',503);
  $json=getJson('https://api.themoviedb.org/3/search/movie?'.http_build_query(['api_key'=>TMDB_API_KEY,'query'=>$q,'include_adult'=>'false','language'=>'en-US']));
  foreach(array_slice($json['results']??[],0,18) as $m){$id=(int)($m['id']??0);if(!$id)continue;$url='https://www.themoviedb.org/movie/'.$id;
   $items[]=['category'=>'movies','title'=>$m['title']??'Unknown title','subtitle'=>substr($m['release_date']??'',0,4),'description'=>excerpt($m['overview']??''),'image'=>!empty($m['poster_path'])?'https://image.tmdb.org/t/p/w342'.$m['poster_path']:'','rating'=>!empty($m['vote_count'])?(float)$m['vote_average']:null,'rating_scale'=>10,'votes'=>(int)($m['vote_count']??0),'url'=>$url,'review_url'=>$url.'/reviews','reviews'=>[]];
  }
  $source='TMDB';
 }
 // MySQL is deliberately optional: the site functions without a huge database.
 // On successful configuration, store only query analytics, not external catalogues.
 try{$pdo=new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DB.';charset=utf8mb4',MYSQL_USER,MYSQL_PASS,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_TIMEOUT=>2]);$stmt=$pdo->prepare('INSERT INTO searches(category,query_text,result_count) VALUES(?,?,?)');$stmt->execute([$category,$q,count($items)]);}catch(Throwable $ignored){}
 echo json_encode(['source'=>$source,'items'=>$items],JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
}catch(Throwable $e){fail('Search provider request failed. Check network access and API configuration.',502);}
