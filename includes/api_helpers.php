<?php
// includes/api_helpers.php
function json_start(){
  // กันข้อความ/Warning ปนกับ JSON
  if (function_exists('ob_get_level')) { while (ob_get_level()) @ob_end_clean(); }
  @ini_set('display_errors','0');
  @error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
  header('Content-Type: application/json; charset=utf-8');
}

function json_ok($data=null, $message=null, $code=200){
  json_start();
  if ($code !== 200) http_response_code($code);
  echo json_encode(['success'=>true, 'message'=>$message, 'data'=>$data], JSON_UNESCAPED_UNICODE);
  exit;
}

function json_err($message='เกิดข้อผิดพลาด', $code=400){
  json_start();
  http_response_code($code);
  echo json_encode(['success'=>false, 'error'=>$message], JSON_UNESCAPED_UNICODE);
  exit;
}
