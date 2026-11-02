<?php
require_once __DIR__ . '/bootstrap.php';

function process_action(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
    
    require_csrf();
    $action = (string)($_POST['action'] ?? '');
    $actor  = current_user();

    if (!$actor && !in_array($action, ['register_supplier', 'register_buyer', 'login'], true)) {
        throw new RuntimeException('Silakan masuk terlebih dahulu.');
    }

    switch ($action) {
        case 'logout':
            audit((int)$actor['id'], 'logout', 'user', (int)$actor['id']);
            unset($_SESSION['uid']);
            session_regenerate_id(true);
            flash('Anda telah keluar dari sistem.');
            redirect('home');

        case 'login':
            $email = strtolower(required_str('email', 190));
            $pw    = (string)($_POST['password'] ?? '');
            $u     = one('SELECT * FROM users WHERE email=?', [$email]);
            if (!$u || !(int)$u['is_active'] || !password_verify($pw, $u['password_hash'])) {
                throw new RuntimeException('Email/password salah atau akun dinonaktifkan.');
            }
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$u['id'];
            audit((int)$u['id'], 'login', 'user', (int)$u['id']);
            flash('Selamat datang di CoCoLink.');
            redirect();

        case 'register_supplier':
            $name  = required_str('name', 120);
            $email = strtolower(required_str('email', 190));
            $pw    = (string)($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
                throw new RuntimeException('Email tidak valid atau password kurang dari 8 karakter.');
            }
            $legal    = required_str('legal_name', 160);
            $nib      = required_str('nib', 30);
            $city     = required_str('city', 90);
            $province = required_str('province', 90);
            $address  = required_str('address', 1000);
            $lat      = coord('latitude', -90, 90);
            $lng      = coord('longitude', -180, 180);

            $names       = $_POST['coop_name'] ?? [];
            $regions     = $_POST['coop_city'] ?? [];
            $addresses   = $_POST['coop_address'] ?? [];
            $specialties = $_POST['coop_type'] ?? [];

            if (!is_array($names) || !is_array($regions) || !is_array($specialties) || count($names) < 5 || count($names) > 20) {
                throw new RuntimeException('Minimal 5 dan maksimal 20 Koperasi Anak harus diajukan.');
            }

            $prepared = [];
            foreach ($names as $i => $n) {
                $n    = trim((string)$n);
                $reg  = trim((string)($regions[$i] ?? ''));
                $addr = trim((string)($addresses[$i] ?? ''));
                $type = (int)($specialties[$i] ?? 0);
                if ($n === '' || uni_len($n) > 160 || $reg === '' || uni_len($reg) > 90 || $addr === '' || uni_len($addr) > 1000 || !val('SELECT id FROM raw_types WHERE id=?', [$type])) {
                    throw new RuntimeException('Data Koperasi Anak ke-' . ($i + 1) . ' belum lengkap.');
                }
                $prepared[] = ['name' => $n, 'city' => $reg, 'address' => $addr, 'type' => $type];
            }

            if (count(array_unique(array_map(fn($c) => uni_lower($c['name']), $prepared))) < 5) {
                throw new RuntimeException('Minimal 5 Koperasi Anak dengan nama berbeda.');
            }

            db()->beginTransaction();
            try {
                sql('INSERT INTO suppliers(legal_name,nib,province,city,address,latitude,longitude) VALUES(?,?,?,?,?,?,?)', [$legal, $nib, $province, $city, $address, $lat, $lng]);
                $sid = (int)db()->lastInsertId();
                sql("INSERT INTO users(name,email,password_hash,role,supplier_id) VALUES(?,?,?,'supplier',?)", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $sid]);
                $uid = (int)db()->lastInsertId();
                foreach ($prepared as $c) {
                    sql('INSERT INTO cooperatives(supplier_id,name,province,city,address,specialty_raw_type_id) VALUES(?,?,?,?,?,?)', [$sid, $c['name'], $province, $c['city'], $c['address'], $c['type']]);
                }
                audit($uid, 'register_supplier', 'supplier', $sid, 'Mencalonkan ' . count($prepared) . ' Koperasi Anak');
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Pendaftaran PT diterima. Koperasi Induk akan memverifikasi minimal 5 mitra Koperasi Anak.');
            redirect('login');

        case 'register_buyer':
            $name  = required_str('name', 120);
            $email = strtolower(required_str('email', 190));
            $pw    = (string)($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
                throw new RuntimeException('Email tidak valid atau password kurang dari 8 karakter.');
            }
            db()->beginTransaction();
            try {
                $buyerType = required_str('buyer_type', 20);
                if (!in_array($buyerType, ['industry', 'collector'], true)) {
                    throw new RuntimeException('Jenis pembeli tidak valid.');
                }
                sql('INSERT INTO buyer_profiles(company_name,buyer_type,nib,address,city) VALUES(?,?,?,?,?)', [
                    required_str('company_name', 160), $buyerType, trim((string)($_POST['nib'] ?? '')) ?: null, required_str('address', 1000), required_str('city', 90)
                ]);
                $bid = (int)db()->lastInsertId();
                sql("INSERT INTO users(name,email,password_hash,role,buyer_id) VALUES(?,?,?,'buyer',?)", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $bid]);
                audit((int)db()->lastInsertId(), 'register_buyer', 'buyer', $bid);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Pendaftaran pembeli berhasil. Tunggu verifikasi Koperasi Induk.');
            redirect('login');

        case 'verify_coop':
            must_role(['super_admin', 'induk']);
            $id     = post_id('id');
            $status = required_str('status', 15);
            if (!in_array($status, ['verified', 'rejected'], true)) throw new RuntimeException('Status tidak valid.');
            db()->beginTransaction();
            try {
                $coop = one('SELECT * FROM cooperatives WHERE id=? FOR UPDATE', [$id]);
                if (!$coop) throw new RuntimeException('Koperasi tidak ditemukan.');
                $parent = one('SELECT * FROM suppliers WHERE id=? FOR UPDATE', [$coop['supplier_id']]);
                if ($parent['status'] === 'verified' && $coop['status'] === 'verified' && $status === 'rejected') {
                    $n = (int)val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='verified'", [$coop['supplier_id']]);
                    if ($n <= 5) throw new RuntimeException('Koperasi tidak bisa ditolak karena PT akan memiliki kurang dari 5 koperasi valid.');
                }
                sql('UPDATE cooperatives SET status=?,verified_by=? WHERE id=?', [$status, $actor['id'], $id]);
                audit((int)$actor['id'], 'verify_coop', 'cooperative', $id, $status);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Status Koperasi Anak diperbarui.');
            redirect('verification');

        case 'verify_supplier':
            must_role(['super_admin', 'induk']);
            $id     = post_id('id');
            $status = required_str('status', 15);
            if (!in_array($status, ['verified', 'rejected'], true)) throw new RuntimeException('Status tidak valid.');
            db()->beginTransaction();
            try {
                $supplier = one('SELECT * FROM suppliers WHERE id=? FOR UPDATE', [$id]);
                if (!$supplier) throw new RuntimeException('PT tidak ditemukan.');
                $count = (int)val("SELECT COUNT(*) FROM cooperatives WHERE supplier_id=? AND status='verified'", [$id]);
                if ($status === 'verified' && $count < 5) throw new RuntimeException('PT baru bisa disetujui jika minimal 5 Koperasi Anak sudah terverifikasi (saat ini ' . $count . ').');
                sql('UPDATE suppliers SET status=?,verified_by=?,verified_at=IF(?="verified",NOW(),NULL) WHERE id=?', [$status, $actor['id'], $status, $id]);
                audit((int)$actor['id'], 'verify_supplier', 'supplier', $id, $status . '; koperasi valid=' . $count);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Status PT Pemasok berhasil diperbarui.');
            redirect('verification');

        case 'verify_buyer':
            must_role(['super_admin', 'induk']);
            $id     = post_id('id');
            $status = required_str('status', 15);
            if (!in_array($status, ['verified', 'rejected'], true)) throw new RuntimeException('Status tidak valid.');
            sql('UPDATE buyer_profiles SET status=? WHERE id=?', [$status, $id]);
            audit((int)$actor['id'], 'verify_buyer', 'buyer', $id, $status);
            flash('Verifikasi pembeli diperbarui.');
            redirect('verification');

        case 'create_user':
            must_role(['super_admin']);
            $role = required_str('role', 20);
            if (!in_array($role, ['super_admin', 'induk', 'supplier', 'coop', 'buyer', 'driver'], true)) throw new RuntimeException('Role tidak valid.');
            $email = strtolower(required_str('email', 190));
            $pw    = (string)($_POST['password'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) throw new RuntimeException('Email tidak valid atau password minimal 8 karakter.');
            $sid = $role === 'supplier' ? post_id('supplier_id') : null;
            $cid = $role === 'coop' ? post_id('coop_id') : null;
            $bid = $role === 'buyer' ? post_id('buyer_id') : null;
            if ($sid && !val('SELECT id FROM suppliers WHERE id=?', [$sid])) throw new RuntimeException('PT tidak ditemukan.');
            if ($cid && !val('SELECT id FROM cooperatives WHERE id=?', [$cid])) throw new RuntimeException('Koperasi tidak ditemukan.');
            if ($bid && !val('SELECT id FROM buyer_profiles WHERE id=?', [$bid])) throw new RuntimeException('Pembeli tidak ditemukan.');
            sql('INSERT INTO users(name,email,password_hash,role,supplier_id,coop_id,buyer_id) VALUES(?,?,?,?,?,?,?)', [
                required_str('name', 120), $email, password_hash($pw, PASSWORD_DEFAULT), $role, $sid, $cid, $bid
            ]);
            audit((int)$actor['id'], 'create_user', 'user', (int)db()->lastInsertId(), $role);
            flash('Akun berhasil dibuat.');
            redirect('accounts');

        case 'toggle_user':
            must_role(['super_admin']);
            $id = post_id('id');
            if ($id === (int)$actor['id']) throw new RuntimeException('Akun yang sedang digunakan tidak bisa dinonaktifkan.');
            sql('UPDATE users SET is_active=1-is_active WHERE id=?', [$id]);
            audit((int)$actor['id'], 'toggle_user', 'user', $id);
            flash('Status akun diubah.');
            redirect('accounts');

        case 'new_batch':
            check_supplier_active($actor);
            $raw = post_id('raw_type_id');
            if (!val('SELECT id FROM raw_types WHERE id=?', [$raw])) throw new RuntimeException('Jenis limbah tidak ditemukan.');
            sql('INSERT INTO raw_batches(supplier_id,raw_type_id,batch_code,quantity,unit,notes) VALUES(?,?,?,?,?,?)', [
                $actor['supplier_id'], $raw, code('RAW'), positive('quantity'), 'kg', trim((string)($_POST['notes'] ?? ''))
            ]);
            audit((int)$actor['id'], 'new_batch', 'raw_batch', (int)db()->lastInsertId());
            flash('Batch limbah tercatat dan siap dialokasikan pusat.');
            redirect('batches');

        case 'allocate':
            must_role(['super_admin', 'induk']);
            $batchId = post_id('batch_id');
            $coopId  = post_id('coop_id');
            $qty     = positive('quantity');
            db()->beginTransaction();
            try {
                $batch = one('SELECT * FROM raw_batches WHERE id=? FOR UPDATE', [$batchId]);
                $coop  = one('SELECT * FROM cooperatives WHERE id=?', [$coopId]);
                if (!$batch || !$coop || $coop['status'] !== 'verified' || (int)$coop['supplier_id'] !== (int)$batch['supplier_id'] || (int)$coop['specialty_raw_type_id'] !== (int)$batch['raw_type_id']) {
                    throw new RuntimeException('Koperasi tujuan tidak cocok dengan PT / jenis limbah atau belum diverifikasi.');
                }
                $used = (float)val("SELECT COALESCE(SUM(quantity),0) FROM allocations WHERE batch_id=? AND status IN ('proposed','accepted','completed')", [$batchId]);
                if ($qty > (float)$batch['quantity'] - $used + 0.00001) {
                    throw new RuntimeException('Stok batch tidak cukup. Sisa: ' . number((float)$batch['quantity'] - $used) . ' kg.');
                }
                sql('INSERT INTO allocations(batch_id,coop_id,quantity,created_by) VALUES(?,?,?,?)', [$batchId, $coopId, $qty, $actor['id']]);
                audit((int)$actor['id'], 'allocate', 'allocation', (int)db()->lastInsertId());
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Alokasi dikirim ke Koperasi Anak.');
            redirect('allocations');

        case 'respond_allocation':
            check_coop_active($actor);
            $id     = post_id('id');
            $status = required_str('status', 10);
            if (!in_array($status, ['accepted', 'rejected'], true)) throw new RuntimeException('Status tidak valid.');
            $st = sql("UPDATE allocations SET status=? WHERE id=? AND coop_id=? AND status='proposed'", [$status, $id, $actor['coop_id']]);
            if (!$st->rowCount()) throw new RuntimeException('Alokasi sudah ditangani atau bukan milik koperasi ini.');
            audit((int)$actor['id'], 'respond_allocation', 'allocation', $id, $status);
            flash('Respons alokasi tersimpan.');
            redirect('allocations');

        case 'new_product':
            check_coop_active($actor);
            $raw  = post_id('raw_type_id');
            $coop = one('SELECT * FROM cooperatives WHERE id=?', [$actor['coop_id']]);
            if ((int)$coop['specialty_raw_type_id'] !== $raw) throw new RuntimeException('Jenis limbah produk harus sesuai spesialisasi koperasi.');
            $sku   = required_str('sku', 40);
            $price = positive('price');
            sql('INSERT INTO products(coop_id,raw_type_id,sku,name,description,unit,price) VALUES(?,?,?,?,?,?,?)', [
                $actor['coop_id'], $raw, $sku, required_str('name', 160), trim((string)($_POST['description'] ?? '')), 'kg', $price
            ]);
            audit((int)$actor['id'], 'new_product', 'product', (int)db()->lastInsertId());
            flash('Produk berhasil dibuat; stok awal 0.');
            redirect('products');

        case 'production':
            check_coop_active($actor);
            $aid    = post_id('allocation_id');
            $pid    = post_id('product_id');
            $input  = positive('input_qty');
            $output = positive('output_qty');
            db()->beginTransaction();
            try {
                $all     = one('SELECT a.*,b.raw_type_id FROM allocations a JOIN raw_batches b ON b.id=a.batch_id WHERE a.id=? FOR UPDATE', [$aid]);
                $product = one('SELECT * FROM products WHERE id=? FOR UPDATE', [$pid]);
                if (!$all || !$product || !in_array($all['status'], ['accepted', 'completed'], true) || (int)$all['coop_id'] !== (int)$actor['coop_id'] || (int)$product['coop_id'] !== (int)$actor['coop_id'] || (int)$product['raw_type_id'] !== (int)$all['raw_type_id']) {
                    throw new RuntimeException('Alokasi/produk tidak cocok atau belum disetujui.');
                }
                $used = (float)val('SELECT COALESCE(SUM(input_qty),0) FROM production_logs WHERE allocation_id=?', [$aid]);
                if ($input > (float)$all['quantity'] - $used + 0.00001) {
                    throw new RuntimeException('Input melebihi limbah tersedia: ' . number((float)$all['quantity'] - $used) . ' kg.');
                }
                sql('INSERT INTO production_logs(allocation_id,product_id,input_qty,output_qty,notes,created_by) VALUES(?,?,?,?,?,?)', [
                    $aid, $pid, $input, $output, trim((string)($_POST['notes'] ?? '')), $actor['id']
                ]);
                sql('UPDATE products SET stock=stock+? WHERE id=?', [$output, $pid]);
                if ($used + $input + 0.00001 >= (float)$all['quantity']) {
                    sql("UPDATE allocations SET status='completed' WHERE id=?", [$aid]);
                }
                audit((int)$actor['id'], 'production', 'product', $pid, $input . ' kg input -> ' . $output . ' kg produk');
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Produksi dicatat dan stok produk bertambah.');
            redirect('products');

        case 'new_order':
            check_buyer_active($actor);
            $pid = post_id('product_id');
            $qty = positive('quantity');
            $p   = one('SELECT p.*,c.status coop_status,s.status supplier_status FROM products p JOIN cooperatives c ON c.id=p.coop_id JOIN suppliers s ON s.id=c.supplier_id WHERE p.id=?', [$pid]);
            if (!$p || !$p['is_active'] || $p['coop_status'] !== 'verified' || $p['supplier_status'] !== 'verified' || (float)$p['stock'] < $qty) {
                throw new RuntimeException('Produk tidak tersedia atau stok kurang.');
            }
            db()->beginTransaction();
            try {
                $total = round($qty * (float)$p['price'], 2);
                sql('INSERT INTO orders(order_code,buyer_id,shipping_address,city,notes,total) VALUES(?,?,?,?,?,?)', [
                    code('ORD'), $actor['buyer_id'], required_str('shipping_address', 1000), required_str('city', 90), trim((string)($_POST['notes'] ?? '')), $total
                ]);
                $oid = (int)db()->lastInsertId();
                sql('INSERT INTO order_items(order_id,product_id,quantity,unit_price) VALUES(?,?,?,?)', [$oid, $pid, $qty, $p['price']]);
                audit((int)$actor['id'], 'new_order', 'order', $oid);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Pesanan diajukan ke Koperasi Induk. Stok dikurangi setelah pesanan disetujui.');
            redirect('orders');

        case 'approve_order':
            must_role(['super_admin', 'induk']);
            $oid      = post_id('id');
            $decision = required_str('decision', 12);
            if (!in_array($decision, ['approved', 'rejected'], true)) throw new RuntimeException('Keputusan tidak valid.');
            db()->beginTransaction();
            try {
                $order = one('SELECT * FROM orders WHERE id=? FOR UPDATE', [$oid]);
                if (!$order || $order['status'] !== 'requested') throw new RuntimeException('Pesanan tidak dalam status diajukan.');
                if ($decision === 'approved') {
                    foreach (rows('SELECT * FROM order_items WHERE order_id=?', [$oid]) as $item) {
                        $p = one('SELECT p.*,c.status coop_status,s.status supplier_status FROM products p JOIN cooperatives c ON c.id=p.coop_id JOIN suppliers s ON s.id=c.supplier_id WHERE p.id=? FOR UPDATE', [$item['product_id']]);
                        if (!$p || $p['coop_status'] !== 'verified' || $p['supplier_status'] !== 'verified' || (float)$p['stock'] < (float)$item['quantity']) {
                            throw new RuntimeException('Stok produk tidak cukup atau mitra belum valid.');
                        }
                        sql('UPDATE products SET stock=stock-? WHERE id=?', [$item['quantity'], $item['product_id']]);
                    }
                }
                sql('UPDATE orders SET status=?,approved_by=? WHERE id=?', [$decision, $actor['id'], $oid]);
                audit((int)$actor['id'], 'approve_order', 'order', $oid, $decision);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Keputusan pesanan berhasil disimpan.');
            redirect('orders');

        case 'assign_driver':
            must_role(['super_admin', 'induk']);
            $oid = post_id('id');
            $did = post_id('driver_id');
            db()->beginTransaction();
            try {
                $order  = one('SELECT * FROM orders WHERE id=? FOR UPDATE', [$oid]);
                $driver = one("SELECT id FROM users WHERE id=? AND role='driver' AND is_active=1", [$did]);
                if (!$order || $order['status'] !== 'approved' || !$driver) throw new RuntimeException('Pesanan/driver tidak siap untuk ditugaskan.');
                sql('INSERT INTO shipments(order_id,driver_id) VALUES(?,?)', [$oid, $did]);
                $sid = (int)db()->lastInsertId();
                sql("INSERT INTO shipment_events(shipment_id,event_type,note,actor_id) VALUES(?,'assigned','Driver ditugaskan Koperasi Induk',?)", [$sid, $actor['id']]);
                sql("UPDATE orders SET status='assigned' WHERE id=?", [$oid]);
                audit((int)$actor['id'], 'assign_driver', 'shipment', $sid);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Driver logistik berhasil ditugaskan.');
            redirect('shipments');

        case 'approve_pickup':
            $actor    = must_role(['super_admin', 'induk', 'coop']);
            $sid      = post_id('id');
            $decision = required_str('decision', 12);
            $note     = required_str('note', 800);
            if (!in_array($decision, ['approved', 'rejected'], true)) throw new RuntimeException('Keputusan pickup tidak valid.');
            db()->beginTransaction();
            try {
                $ship = one('SELECT s.*,o.id order_id FROM shipments s JOIN orders o ON o.id=s.order_id WHERE s.id=? FOR UPDATE', [$sid]);
                if (!$ship || $ship['status'] !== 'pickup_pending') throw new RuntimeException('Pickup tidak sedang menunggu persetujuan.');
                if ($actor['role'] === 'coop' && !val('SELECT 1 FROM order_items i JOIN products p ON p.id=i.product_id WHERE i.order_id=? AND p.coop_id=? LIMIT 1', [$ship['order_id'], $actor['coop_id']])) {
                    throw new RuntimeException('Pickup ini bukan dari lokasi Koperasi Anda.');
                }
                if ($decision === 'approved') {
                    sql("UPDATE shipments SET status='picked_up',picked_up_at=NOW() WHERE id=?", [$sid]);
                    sql("UPDATE orders SET status='in_transit' WHERE id=?", [$ship['order_id']]);
                    $event = 'picked_up';
                } else {
                    sql("UPDATE shipments SET status='accepted' WHERE id=?", [$sid]);
                    $event = 'pickup_rejected';
                }
                sql('INSERT INTO shipment_events(shipment_id,event_type,note,actor_id) VALUES(?,?,?,?)', [$sid, $event, $note, $actor['id']]);
                audit((int)$actor['id'], 'approve_pickup', 'shipment', $sid, $decision);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Persetujuan pickup diperbarui.');
            redirect('shipments');

        case 'shipment_event':
            must_role(['driver']);
            $sid   = post_id('id');
            $event = required_str('event_type', 20);
            $note  = required_str('note', 1000);
            if (!in_array($event, ['accepted', 'pickup_requested', 'location', 'delivered'], true)) throw new RuntimeException('Tahapan pengiriman tidak valid.');
            $lat = coord('latitude', -90, 90, true);
            $lng = coord('longitude', -180, 180, true);
            if (($lat === null) !== ($lng === null)) throw new RuntimeException('Isi pasangan Lintang dan Bujur secara bersamaan.');
            $proofPath = null;
            if (!empty($_FILES['proof']['name'])) {
                if ($_FILES['proof']['error'] !== UPLOAD_ERR_OK || $_FILES['proof']['size'] > 5 * 1024 * 1024) throw new RuntimeException('Ukuran bukti foto/file maksimal 5 MB.');
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES['proof']['tmp_name']);
                $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'application/pdf' => 'pdf'][$mime] ?? null;
                if (!$ext) throw new RuntimeException('Bukti harus berformat JPG, PNG, WEBP, atau PDF.');
                $proofPath = 'proof_' . bin2hex(random_bytes(16)) . '.' . $ext;
                if (!move_uploaded_file($_FILES['proof']['tmp_name'], __DIR__ . '/../storage/uploads/' . $proofPath)) throw new RuntimeException('Bukti gagal diunggah.');
            }
            if ($event === 'delivered' && !$proofPath) throw new RuntimeException('Pengiriman selesai wajib mengunggah foto bukti serah terima.');
            db()->beginTransaction();
            try {
                $ship = one('SELECT s.*,o.status order_status FROM shipments s JOIN orders o ON o.id=s.order_id WHERE s.id=? FOR UPDATE', [$sid]);
                if (!$ship || (int)$ship['driver_id'] !== (int)$actor['id']) throw new RuntimeException('Pengiriman bukan tugas Anda.');
                $expected = ['accepted' => 'assigned', 'pickup_requested' => 'accepted', 'delivered' => 'picked_up'];
                if ($event === 'location' && !in_array($ship['status'], ['accepted', 'pickup_pending', 'picked_up'], true)) throw new RuntimeException('Perbarui lokasi setelah tugas diterima dan sebelum selesai.');
                if ($event !== 'location' && $ship['status'] !== ($expected[$event] ?? '')) throw new RuntimeException('Urutan tahapan pengiriman tidak sesuai.');
                sql('INSERT INTO shipment_events(shipment_id,event_type,note,latitude,longitude,proof_path,actor_id) VALUES(?,?,?,?,?,?,?)', [$sid, $event, $note, $lat, $lng, $proofPath, $actor['id']]);
                if ($event === 'pickup_requested') {
                    sql("UPDATE shipments SET status='pickup_pending' WHERE id=?", [$sid]);
                } elseif ($event === 'delivered') {
                    sql("UPDATE shipments SET status='delivered',delivered_at=NOW() WHERE id=?", [$sid]);
                    sql("UPDATE orders SET status='delivered' WHERE id=?", [$ship['order_id']]);
                } elseif ($event === 'accepted') {
                    sql("UPDATE shipments SET status='accepted' WHERE id=?", [$sid]);
                }
                audit((int)$actor['id'], 'shipment_event', 'shipment', $sid, $event);
                db()->commit();
            } catch (Throwable $ex) {
                db()->rollBack();
                throw $ex;
            }
            flash('Status pengiriman berhasil diperbarui.');
            redirect('shipments');

        case 'confirm_order':
            check_buyer_active($actor);
            $oid = post_id('id');
            $st  = sql("UPDATE orders SET status='completed' WHERE id=? AND buyer_id=? AND status='delivered'", [$oid, $actor['buyer_id']]);
            if (!$st->rowCount()) throw new RuntimeException('Pesanan belum dapat dikonfirmasi.');
            audit((int)$actor['id'], 'confirm_order', 'order', $oid);
            flash('Penerimaan barang dikonfirmasi oleh pembeli.');
            redirect('orders');

        default:
            throw new RuntimeException('Tindakan tidak dikenali.');
    }
}