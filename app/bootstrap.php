<?php
declare(strict_types=1);
$config = require __DIR__.'/../config/config.php';
date_default_timezone_set($config['timezone']);
if (session_status() !== PHP_SESSION_ACTIVE) {
 session_set_cookie_params(['httponly' => true, 'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'samesite' => 'Lax']);
 session_start();
}
function db(): PDO {
 global $config;
 static $pdo = null;
 if ($pdo === null) {
  $pdo = new PDO('mysql:host='.$config['db_host'].';port='.$config['db_port'].';dbname='.$config['db_name'].';charset=utf8mb4',
   $config['db_user'], $config['db_password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]);
 }
 return $pdo;
}
function sql(string $query, array $params = []): PDOStatement { $st=db()->prepare($query); $st->execute($params); return $st; }
function rows(string $query, array $params=[]): array { return sql($query,$params)->fetchAll(); }
function one(string $query, array $params=[]): ?array { $r=sql($query,$params)->fetch(); return $r?:null; }
function val(string $query, array $params=[]): mixed { return sql($query,$params)->fetchColumn(); }
function uni_len(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s); }
function uni_lower(string $s): string { return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); }
function uni_slice(string $s,int $start,int $length): string { return function_exists('mb_substr') ? mb_substr($s,$start,$length) : substr($s,$start,$length); }
function e(mixed $v): string { return htmlspecialchars((string)($v??''), ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'); }
function app_base(): string { $dir=str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/index.php')); return ($dir==='/'||$dir==='.')?'':rtrim($dir,'/'); }
function url(string $page='dashboard', array $args=[]): string { return app_base().'/index.php?'.http_build_query(array_merge(['page'=>$page],$args)); }
function redirect(string $page='dashboard', array $args=[]): never { header('Location: '.url($page,$args)); exit; }
function money(mixed $number): string { return 'Rp '.number_format((float)$number,0,',','.'); }
function number(mixed $n, int $dec=2): string { return number_format((float)$n,$dec,',','.'); }
function csrf(): string { return $_SESSION['csrf']??=bin2hex(random_bytes(32)); }
function csrf_input(): string { return '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function require_csrf(): void { if(!hash_equals(csrf(),(string)($_POST['csrf']??''))) throw new RuntimeException('Token keamanan formulir kedaluwarsa. Muat ulang halaman.'); }
function current_user(): ?array {
 if (empty($_SESSION['uid'])) return null;
 $u=one('SELECT * FROM users WHERE id=?',[(int)$_SESSION['uid']]);
 if(!$u||!(int)$u['is_active']) { unset($_SESSION['uid']);return null; }
 return $u;
}
function user(): array { $u=current_user(); if(!$u){redirect('login');} return $u; }
function must_role(array $roles): array { $u=user(); if(!in_array($u['role'],$roles,true)) throw new RuntimeException('Anda tidak memiliki akses untuk tindakan ini.'); return $u; }
function is_manager(?array $u=null): bool { $u??=current_user(); return $u && in_array($u['role'],['super_admin','induk'],true); }
function flash(string $message, string $kind='success'): void { $_SESSION['flash']=['message'=>$message,'kind'=>$kind]; }
function take_flash(): ?array { $f=$_SESSION['flash']??null;unset($_SESSION['flash']);return $f; }
function required_str(string $key, int $max=255): string { $v=trim((string)($_POST[$key]??'')); if($v===''||uni_len($v)>$max)throw new RuntimeException('Isian '.str_replace('_',' ',$key).' wajib dan maksimal '.$max.' karakter.'); return $v; }
function post_id(string $key): int { $id=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT);if(!$id||$id<1)throw new RuntimeException('ID '.e($key).' tidak valid.');return $id; }
function positive(string $key): float { $str=str_replace(',','.',trim((string)($_POST[$key]??''))); if(!is_numeric($str)||(float)$str<=0)throw new RuntimeException('Nilai '.str_replace('_',' ',$key).' harus lebih dari nol.'); return round((float)$str,2); }
function coord(string $key, float $min, float $max, bool $nullable=false): ?float { $s=trim((string)($_POST[$key]??''));if($s===''&&$nullable)return null;if($s===''||!is_numeric($s)||(float)$s<$min||(float)$s>$max)throw new RuntimeException('Koordinat '.e($key).' tidak valid.');return (float)$s; }
function audit(?int $u, string $action, string $entity, ?int $id=null, string $details=''): void { sql('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,details,ip) VALUES (?,?,?,?,?,?)',[$u,$action,$entity,$id,$details,substr((string)($_SERVER['REMOTE_ADDR']??'CLI'),0,45)]); }
function code(string $prefix): string { return $prefix.'-'.date('ymd').'-'.strtoupper(bin2hex(random_bytes(4))); }
function status_text(string $status): string { return [
 'pending'=>'Menunggu verifikasi','verified'=>'Terverifikasi','rejected'=>'Ditolak','proposed'=>'Menunggu koperasi',
 'accepted'=>'Diterima','completed'=>'Selesai','requested'=>'Diajukan','approved'=>'Disetujui','assigned'=>'Driver ditugaskan',
 'in_transit'=>'Dalam perjalanan','pickup_pending'=>'Pickup perlu disetujui','pickup_requested'=>'Pengajuan pickup','pickup_rejected'=>'Pickup ditolak','picked_up'=>'Pickup disetujui','delivered'=>'Terkirim','location'=>'Lokasi diperbarui'
 ][$status]??ucwords(str_replace('_',' ',$status)); }
function badge(string $status): string {
 $color=match($status){'completed','verified','delivered'=>'success','rejected'=>'danger','accepted','approved'=>'info','in_transit','picked_up'=>'primary',default=>'warning'};
 return '<span class="badge rounded-pill text-bg-'.$color.' fw-medium">'.e(status_text($status)).'</span>';
}
function initials(string $name): string { $words=preg_split('/\s+/u',trim($name));return strtoupper(substr(($words[0]??'C'),0,1).substr(($words[1]??'L'),0,1)); }
function check_supplier_active(array $u): void {if($u['role']!=='supplier'||!$u['supplier_id']||val('SELECT status FROM suppliers WHERE id=?',[$u['supplier_id']])!=='verified')throw new RuntimeException('PT pemasok belum terverifikasi.');}
function check_coop_active(array $u): void {if($u['role']!=='coop'||!$u['coop_id']||val('SELECT status FROM cooperatives WHERE id=?',[$u['coop_id']])!=='verified')throw new RuntimeException('Koperasi anak belum terverifikasi.');}
function check_buyer_active(array $u): void {if($u['role']!=='buyer'||!$u['buyer_id']||val('SELECT status FROM buyer_profiles WHERE id=?',[$u['buyer_id']])!=='verified')throw new RuntimeException('Akun pembeli belum terverifikasi.');}
