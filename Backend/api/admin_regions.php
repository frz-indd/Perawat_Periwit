<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../middleware/auth.php';
require_once __DIR__ . '/../database/koneksi.php';
adminOnly();
try { $result=$conn->query("SELECT DISTINCT domisili AS name FROM wisata WHERE domisili<>'' AND aktif=1 ORDER BY domisili"); $data=[]; while($r=$result->fetch_assoc()) $data[]=['id'=>$r['name'],'name'=>$r['name']]; echo json_encode(['status'=>'success','data'=>$data],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); }
catch(Throwable $e){ error_log('admin_regions: '.$e->getMessage()); http_response_code(500); echo json_encode(['status'=>'error','message'=>'Wilayah gagal dimuat.']); }
