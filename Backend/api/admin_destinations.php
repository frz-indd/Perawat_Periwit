<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../database/koneksi.php';
adminOnly();

function respond(array $data, int $code=200): void { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
function uploadPhoto(mysqli $conn, string $id): ?string {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f=$_FILES['image'];
    if ($f['error'] !== UPLOAD_ERR_OK) respond(['status'=>'error','message'=>'Foto gagal diunggah.'],400);
    if ($f['size'] > 10*1024*1024) respond(['status'=>'error','message'=>'Ukuran foto maksimal 10MB.'],400);
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png'][$mime] ?? null;
    if (!$ext) respond(['status'=>'error','message'=>'Format foto harus JPG atau PNG.'],400);
    $dir=__DIR__.'/../../FrontEnd/assets/images/wisata';
    if (!is_dir($dir) && !mkdir($dir,0755,true)) respond(['status'=>'error','message'=>'Folder penyimpanan foto tidak dapat dibuat.'],500);
    $name='wisata_'.preg_replace('/[^A-Za-z0-9_-]/','',$id).'_'.bin2hex(random_bytes(5)).'.'.$ext;
    if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) respond(['status'=>'error','message'=>'Foto gagal disimpan.'],500);
    return 'FrontEnd/assets/images/wisata/'.$name;
}
function destinationId(mysqli $conn, string $domisili): string {
    $prefix = stripos($domisili,'Majalengka') !== false ? 'M' : 'K';
    $stmt=$conn->prepare('SELECT COALESCE(MAX(CAST(SUBSTRING(id_wisata,2) AS UNSIGNED)),0)+1 AS next_id FROM wisata WHERE id_wisata LIKE CONCAT(?, "%")');
    $stmt->bind_param('s',$prefix); $stmt->execute(); $n=(int)$stmt->get_result()->fetch_assoc()['next_id'];
    return $prefix.str_pad((string)$n,5,'0',STR_PAD_LEFT);
}
$method=$_SERVER['REQUEST_METHOD']??'GET';
if ($method==='POST' && ($_POST['_method']??'')==='PUT') $method='PUT';
try {
 if ($method==='GET') {
    $search=trim((string)($_GET['search']??'')); $region=trim((string)($_GET['kabupaten']??'')); $category=trim((string)($_GET['category']??'')); $id=trim((string)($_GET['id']??''));
    $page=max(1,(int)($_GET['page']??1)); $limit=min(100,max(1,(int)($_GET['per_page']??5))); $offset=($page-1)*$limit;
    $where=['w.aktif=1']; $params=[]; $types='';
    if($search!==''){ $where[]='w.nama_wisata LIKE ?'; $params[]="%$search%"; $types.='s'; }
    if($region!==''){ $where[]='w.domisili=?'; $params[]=$region; $types.='s'; }
    if($category!=='' && ctype_digit($category)){ $where[]='w.id_kategori=?'; $params[]=(int)$category; $types.='i'; }
    if($id!==''){ $where[]='w.id_wisata=?'; $params[]=$id; $types.='s'; }
    $whereSql='WHERE '.implode(' AND ',$where);
    $count=$conn->prepare("SELECT COUNT(*) total FROM wisata w $whereSql"); if($types) $count->bind_param($types,...$params); $count->execute(); $total=(int)$count->get_result()->fetch_assoc()['total'];
    $sql="SELECT w.id_wisata,w.nama_wisata,w.deskripsi,w.domisili,w.harga_tiket,w.alamat,w.Maps_Wisata,w.jam_buka,w.jam_tutup,w.latitude,w.longitude,w.id_kategori,k.nama_kategori,(SELECT AVG(u.rating) FROM ulasan u WHERE u.Id_wisata=w.id_wisata) rating,(SELECT f.link_foto FROM foto_wisata f WHERE f.id_wisata=w.id_wisata ORDER BY f.Id_foto DESC LIMIT 1) image FROM wisata w LEFT JOIN kategori k ON k.Id_kategori=w.id_kategori $whereSql ORDER BY w.nama_wisata LIMIT ? OFFSET ?";
    $stmt=$conn->prepare($sql); $bt=$types.'ii'; $bv=array_merge($params,[$limit,$offset]); $stmt->bind_param($bt,...$bv); $stmt->execute(); $rs=$stmt->get_result(); $data=[];
    while($r=$rs->fetch_assoc()) $data[]=['id'=>$r['id_wisata'],'name'=>$r['nama_wisata'],'region'=>$r['domisili'],'kabupaten'=>$r['domisili'],'category'=>$r['nama_kategori']??'-','category_detail'=>['id'=>(int)$r['id_kategori'],'name'=>$r['nama_kategori']??''],'price'=>(float)$r['harga_tiket'],'rating'=>$r['rating']!==null?round((float)$r['rating'],1):null,'maps_url'=>$r['Maps_Wisata'],'image'=>$r['image'],'image_url'=>$r['image'],'description'=>$r['deskripsi'],'address'=>$r['alamat'],'open_time'=>$r['jam_buka'],'close_time'=>$r['jam_tutup'],'latitude'=>$r['latitude'],'longitude'=>$r['longitude']];
    respond(['status'=>'success','total'=>$total,'total_data'=>$total,'total_page'=>(int)ceil($total/$limit),'current_page'=>$page,'per_page'=>$limit,'data'=>$data]);
 }
 if($method==='POST') {
    $name=trim((string)($_POST['name']??'')); $region=trim((string)($_POST['domisili']??$_POST['kabupaten_id']??'')); $cat=(int)($_POST['category_id']??0); $price=(float)($_POST['price']??0); $maps=trim((string)($_POST['maps_url']??''));
    $latitudeRaw=trim((string)($_POST['latitude']??'')); $longitudeRaw=trim((string)($_POST['longitude']??''));
    if($name===''||$region===''||$cat<1) respond(['status'=>'error','message'=>'Nama wisata, wilayah, dan kategori wajib diisi.'],400);
    if(($latitudeRaw==='')!==($longitudeRaw==='')) respond(['status'=>'error','message'=>'Latitude dan longitude harus diisi bersama.'],400);
    if($latitudeRaw!=='' && (!is_numeric($latitudeRaw)||!is_numeric($longitudeRaw)||(float)$latitudeRaw < -90||(float)$latitudeRaw > 90||(float)$longitudeRaw < -180||(float)$longitudeRaw > 180)) respond(['status'=>'error','message'=>'Koordinat latitude atau longitude tidak valid.'],400);
    $latitude=$latitudeRaw===''?null:(float)$latitudeRaw; $longitude=$longitudeRaw===''?null:(float)$longitudeRaw;
    $check=$conn->prepare('SELECT Id_kategori FROM kategori WHERE Id_kategori=? AND aktif=1'); $check->bind_param('i',$cat); $check->execute(); if(!$check->get_result()->fetch_assoc()) respond(['status'=>'error','message'=>'Kategori tidak ditemukan.'],400);
    $id=destinationId($conn,$region); $photo=uploadPhoto($conn,$id);
    $stmt=$conn->prepare('INSERT INTO wisata (id_wisata,id_kategori,nama_wisata,harga_tiket,domisili,Maps_Wisata,latitude,longitude,aktif) VALUES (?,?,?,?,?,?,?,?,1)');
    $stmt->bind_param('sisdssdd',$id,$cat,$name,$price,$region,$maps,$latitude,$longitude);
    $stmt->execute();
    if($photo){ $p=$conn->prepare('INSERT INTO foto_wisata (id_wisata,Nama_Foto,link_foto) VALUES (?,?,?)'); $base=basename($photo); $p->bind_param('sss',$id,$base,$photo); $p->execute(); }
    respond(['status'=>'success','message'=>'Data wisata berhasil ditambahkan.','data'=>['id'=>$id]],201);
 }
 if($method==='PUT') {
    $id=trim((string)($_GET['id']??'')); if($id==='') respond(['status'=>'error','message'=>'ID wisata tidak valid.'],400);
    $name=trim((string)($_POST['name']??'')); $region=trim((string)($_POST['domisili']??$_POST['kabupaten_id']??'')); $cat=(int)($_POST['category_id']??0); $price=(float)($_POST['price']??0); $maps=trim((string)($_POST['maps_url']??''));
    $latitudeRaw=trim((string)($_POST['latitude']??'')); $longitudeRaw=trim((string)($_POST['longitude']??''));
    if($name===''||$region===''||$cat<1) respond(['status'=>'error','message'=>'Nama wisata, wilayah, dan kategori wajib diisi.'],400);
    if(($latitudeRaw==='')!==($longitudeRaw==='')) respond(['status'=>'error','message'=>'Latitude dan longitude harus diisi bersama.'],400);
    if($latitudeRaw!=='' && (!is_numeric($latitudeRaw)||!is_numeric($longitudeRaw)||(float)$latitudeRaw < -90||(float)$latitudeRaw > 90||(float)$longitudeRaw < -180||(float)$longitudeRaw > 180)) respond(['status'=>'error','message'=>'Koordinat latitude atau longitude tidak valid.'],400);
    $latitude=$latitudeRaw===''?null:(float)$latitudeRaw; $longitude=$longitudeRaw===''?null:(float)$longitudeRaw;
    $stmt=$conn->prepare('UPDATE wisata SET nama_wisata=?,domisili=?,id_kategori=?,harga_tiket=?,Maps_Wisata=?,latitude=?,longitude=? WHERE id_wisata=?'); $stmt->bind_param('ssidssdds',$name,$region,$cat,$price,$maps,$latitude,$longitude,$id); $stmt->execute();
    $photo=uploadPhoto($conn,$id); if($photo){ $p=$conn->prepare('INSERT INTO foto_wisata (id_wisata,Nama_Foto,link_foto) VALUES (?,?,?)'); $base=basename($photo); $p->bind_param('sss',$id,$base,$photo); $p->execute(); }
    respond(['status'=>'success','message'=>'Data wisata berhasil diperbarui.']);
 }
 if($method==='DELETE') { $id=trim((string)($_GET['id']??'')); if($id==='') respond(['status'=>'error','message'=>'ID wisata tidak valid.'],400); $stmt=$conn->prepare('DELETE FROM wisata WHERE id_wisata=?'); $stmt->bind_param('s',$id); $stmt->execute(); if(!$stmt->affected_rows) respond(['status'=>'error','message'=>'Data wisata tidak ditemukan.'],404); respond(['status'=>'success','message'=>'Data wisata berhasil dihapus.']); }
 respond(['status'=>'error','message'=>'Metode tidak diizinkan.'],405);
} catch(Throwable $e) { error_log('admin_destinations: '.$e->getMessage()); respond(['status'=>'error','message'=>'Terjadi kesalahan saat memproses data wisata.'],500); }
