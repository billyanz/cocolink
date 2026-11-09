<?php
declare(strict_types=1);

function parse_gmaps_url(string $url): array {
    $url = trim($url);
    $lat = -6.2000000;
    $lng = 106.8166666;

    if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
        return [(float)$matches[1], (float)$matches[2]];
    }
    if (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $url, $matches)) {
        return [(float)$matches[1], (float)$matches[2]];
    }
    if (preg_match('/(-?\d+\.\d+)\s*,\s*(-?\d+\.\d+)/', $url, $matches)) {
        return [(float)$matches[1], (float)$matches[2]];
    }

    return [$lat, $lng];
}

function process_action(): void {
    require_csrf();
    $u   = current_user();
    $act = (string)($_POST['action'] ?? '');

    switch ($act) {
        case 'login':
            $email = strtolower(required_str('email', 190));
            $pass  = (string)($_POST['password'] ?? '');
            $user  = one('SELECT * FROM users WHERE email=?', [$email]);

            if (!$user) {
                throw new RuntimeException('Alamat email tersebut tidak terdaftar dalam sistem.');
            }

            $hash = $user['password'] ?? $user['password_hash'] ?? '';
            $isPasswordValid = password_verify($pass, $hash);

            if (!$isPasswordValid) {
                throw new RuntimeException('Kombinasi email atau kata sandi tidak cocok.');
            }
            if (!(int)$user['is_active']) {
                throw new RuntimeException('Akun Anda saat ini dinonaktifkan oleh administrator.');
            }

            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$user['id'];
            audit((int)$user['id'], 'login', 'users', (int)$user['id'], 'Login berhasil ke platform Jabodetabek');
            flash('Selamat datang kembali, ' . $user['name'] . '!');
            redirect('dashboard');

        case 'logout':
            if (isset($u['id'])) {
                audit((int)$u['id'], 'logout', 'users', (int)$u['id']);
            }
            unset($_SESSION['uid']);
            session_regenerate_id(true);
            flash('Anda telah keluar dari sistem.');
            redirect('home');

        case 'register_supplier':
            $name    = required_str('name');
            $email   = required_str('email');
            $pass    = required_str('password');
            $legal   = required_str('legal_name');
            $nib     = required_str('nib');
            $prov    = required_str('province');
            $city    = required_str('city');
            $addr    = required_str('address');
            $mapUrl  = trim((string)($_POST['gmaps_url'] ?? ''));

            if (val('SELECT COUNT(*) FROM users WHERE email=?', [$email]) > 0) {
                throw new RuntimeException('Alamat email tersebut sudah terdaftar.');
            }
            if (val('SELECT COUNT(*) FROM suppliers WHERE nib=?', [$nib]) > 0) {
                throw new RuntimeException('Nomor NIB PT Perusahaan sudah terdaftar.');
            }

            [$lat, $lng] = parse_gmaps_url($mapUrl, $city);

            $coopNames = $_POST['coop_name'] ?? [];
            $coopCities = $_POST['coop_city'] ?? [];
            $coopAddrs = $_POST['coop_address'] ?? [];
            $coopTypes = $_POST['coop_raw_type_id'] ?? [];
            $coopMaps  = $_POST['coop_gmaps_url'] ?? [];

            $validCoops = 0;
            foreach ($coopNames as $idx => $cName) {
                if (!empty(trim((string)$cName))) {
                    $validCoops++;
                }
            }

            if ($validCoops < 5) {
                throw new RuntimeException('Syarat Pendaftaran PT Induk Regional: Wajib mendaftarkan minimal 5 Koperasi Anak Sektor Wilayah di Jabodetabek!');
            }

            db()->beginTransaction();
            try {
                sql("INSERT INTO suppliers(legal_name, nib, province, city, address, latitude, longitude, status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')", [
                    $legal, $nib, $prov, $city, $addr, $lat, $lng
                ]);
                $sid = (int)db()->lastInsertId();

                $hash = password_hash($pass, PASSWORD_DEFAULT);
                sql("INSERT INTO users(name, email, password, role, supplier_id) VALUES (?, ?, ?, 'supplier', ?)", [
                    $name, $email, $hash, $sid
                ]);
                $uid = (int)db()->lastInsertId();

                foreach ($coopNames as $idx => $cName) {
                    $cn = trim((string)$cName);
                    if ($cn === '') continue;

                    $cc = trim((string)($coopCities[$idx] ?? $city));
                    $ca = trim((string)($coopAddrs[$idx] ?? $addr));
                    $ct = (int)($coopTypes[$idx] ?? 1);
                    $cm = trim((string)($coopMaps[$idx] ?? ''));

                    [$cLat, $cLng] = parse_gmaps_url($cm, $cc);

                    sql("INSERT INTO cooperatives(supplier_id, name, city, address, latitude, longitude, specialty_raw_type_id, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')", [
                        $sid, $cn, $cc, $ca, $cLat, $cLng, $ct
                    ]);
                }

                db()->commit();
                audit($uid, 'register_supplier', 'suppliers', $sid, 'Pendaftaran PT Pemasok Regional Jabodetabek dan 5 Koperasi Sektor');
                flash('Pendaftaran PT Pemasok berhasil diajukan! Menunggu verifikasi pengesahan dari Koperasi Induk Metropolitan.', 'success');
                redirect('login');

            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'add_replacement_coop':
            must_role(['super_admin', 'induk', 'supplier']);
            
            $sid = (int)(($u['supplier_id'] ?? null) ?: post_id('supplier_id'));
            if ($sid <= 0) {
                throw new RuntimeException('ID PT Pemasok Utama (supplier_id) tidak valid.');
            }

            $name  = required_str('name');
            $city  = required_str('city');
            $addr  = required_str('address');
            $type  = post_id('specialty_raw_type_id');
            $mUrl  = trim((string)($_POST['gmaps_url'] ?? ''));

            [$cLat, $cLng] = parse_gmaps_url($mUrl, $city);

            sql("INSERT INTO cooperatives(supplier_id, name, city, address, latitude, longitude, specialty_raw_type_id, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'verified')", [
                $sid, $name, $city, $addr, $cLat, $cLng, $type
            ]);
            $cid = (int)db()->lastInsertId();
            audit($u['id'], 'add_replacement_coop', 'cooperatives', $cid, 'Penambahan Koperasi Anak Sektor pengganti');
            flash('Koperasi Anak Sektor pengganti berhasil ditambahkan!');
            redirect('verification');

        case 'create_user':
            must_role(['super_admin', 'induk', 'coop']);
            
            $name       = required_str('name');
            $email      = required_str('email');
            $pass       = required_str('password');
            $role       = required_str('role');
            $supplierId = filter_var($_POST['supplier_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $coopId     = filter_var($_POST['coop_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $driverType = $_POST['driver_type'] ?? null;

            if (val('SELECT COUNT(*) FROM users WHERE email=?', [$email]) > 0) {
                throw new RuntimeException('Alamat email tersebut sudah terdaftar.');
            }

            if ($u['role'] === 'induk') {
                if (!in_array($role, ['coop', 'buyer', 'driver'], true)) {
                    throw new RuntimeException('Koperasi Induk hanya berhak menerbitkan akun Koperasi Anak, Buyer B2B, dan Driver!');
                }
                $supplierId = (int)$u['supplier_id'];
            } elseif ($u['role'] === 'coop') {
                if ($role !== 'buyer') {
                    throw new RuntimeException('Koperasi Anak hanya dapat menerbitkan akun untuk Pembeli B2B!');
                }
                $coopId = (int)$u['coop_id'];
                $supplierId = (int)$u['supplier_id'];
            }

            db()->beginTransaction();
            try {
                $buyerId = null;
                if ($role === 'buyer') {
                    $nib = 'BUY-' . date('Ymd') . '-' . rand(1000, 9999);
                    sql("INSERT INTO buyer_profiles(company_name, nib, address, city) VALUES (?, ?, 'Kawasan Industri Regional', 'Jakarta')", [
                        $name, $nib
                    ]);
                    $buyerId = (int)db()->lastInsertId();
                }

                $hash = password_hash($pass, PASSWORD_DEFAULT);
                sql("INSERT INTO users(name, email, password, role, driver_type, supplier_id, coop_id, buyer_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)", [
                    $name, $email, $hash, $role, $driverType, $supplierId, $coopId, $buyerId
                ]);
                $newUid = (int)db()->lastInsertId();

                db()->commit();
                audit($u['id'], 'create_user', 'users', $newUid, 'Pembuatan akun baru peran ' . $role);
                flash('Akun pengguna baru berhasil diterbitkan!', 'success');
                redirect('accounts');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'verify_supplier':
            must_role(['super_admin', 'induk']);
            $id     = post_id('id');
            $status = required_str('status');
            
            db()->beginTransaction();
            try {
                sql("UPDATE suppliers SET status=? WHERE id=?", [$status, $id]);
                if ($status === 'verified') {
                    sql("UPDATE cooperatives SET status='verified' WHERE supplier_id=?", [$id]);
                }
                db()->commit();
                audit($u['id'], 'verify_supplier', 'suppliers', $id, 'Verifikasi PT Pemasok & Koperasi Sektor ke ' . $status);
                flash('Status verifikasi PT Pemasok Regional & Koperasi Sektor berhasil disetujui!');
                redirect('verification');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'verify_coop':
            must_role(['super_admin', 'induk']);
            $id     = post_id('id');
            $status = required_str('status');

            sql("UPDATE cooperatives SET status=? WHERE id=?", [$status, $id]);
            audit($u['id'], 'verify_coop', 'cooperatives', $id, 'Pembaruan verifikasi Koperasi Sektor ke ' . $status);
            flash('Status verifikasi Koperasi Sektor Wilayah berhasil diperbarui.');
            redirect('verification');

        case 'delete_coop':
            must_role(['super_admin', 'induk']);
            $id = post_id('id');
            sql("DELETE FROM cooperatives WHERE id=?", [$id]);
            audit($u['id'], 'delete_coop', 'cooperatives', $id, 'Menghapus Koperasi Sektor yang tidak memenuhi syarat');
            flash('Koperasi Anak Sektor Wilayah berhasil dihapus.');
            redirect('verification');

        case 'create_batch':
            check_supplier_active($u);
            $typeId = post_id('raw_type_id');
            $qty    = positive('quantity');
            $notes  = trim((string)($_POST['notes'] ?? ''));

            $sid = ($u['role'] === 'super_admin') ? post_id('supplier_id') : (int)$u['supplier_id'];
            if (!$sid) {
                throw new RuntimeException('Akun PT Anda belum terikat dengan entitas PT Pemasok Utama.');
            }

            $bcode = code('RAW');

            sql("INSERT INTO raw_batches(supplier_id, raw_type_id, batch_code, quantity, notes) VALUES (?, ?, ?, ?, ?)", [
                $sid, $typeId, $bcode, $qty, $notes
            ]);
            $bid = (int)db()->lastInsertId();
            audit($u['id'], 'create_batch', 'raw_batches', $bid, 'Input batch pasokan limbah baru');
            flash('Batch pasokan limbah mentah berhasil ditambahkan!');
            redirect('batches');

        case 'allocate':
            must_role(['super_admin', 'induk', 'supplier']);
            $batchId = post_id('batch_id');
            $coopId  = post_id('coop_id');
            $qty     = positive('quantity');

            $rem = val("SELECT (quantity - COALESCE((SELECT SUM(quantity) FROM allocations WHERE batch_id=raw_batches.id AND status<>'rejected'),0)) 
                        FROM raw_batches WHERE id=?", [$batchId]);
            
            if ($qty > (float)$rem) {
                throw new RuntimeException('Jumlah alokasi melebihi sisa stok batch limbah mentah (' . number((float)$rem) . ' kg).');
            }

            sql("INSERT INTO allocations(batch_id, coop_id, quantity, status) VALUES (?, ?, ?, 'proposed')", [
                $batchId, $coopId, $qty
            ]);
            $aid = (int)db()->lastInsertId();

            audit($u['id'], 'allocate', 'allocations', $aid, 'Alokasi bahan mentah ke Koperasi Anak Sektor Wilayah');
            flash('Alokasi pasokan sebesar ' . number($qty) . ' kg berhasil dikirimkan ke Koperasi Anak!', 'success');
            redirect('allocations');

        case 'respond_allocation':
            check_coop_active($u);
            $id     = post_id('id');
            $status = required_str('status');

            sql("UPDATE allocations SET status=? WHERE id=? AND coop_id=?", [$status, $id, $u['coop_id']]);
            audit($u['id'], 'respond_allocation', 'allocations', $id, 'Koperasi Sektor merespon alokasi ke ' . $status);
            flash('Respon alokasi bahan berhasil disimpan.');
            redirect('allocations');

        case 'create_product':
            check_coop_active($u);
            $rawTypeId = post_id('raw_type_id');
            $name      = required_str('name');
            $price     = positive('price');
            $desc      = trim((string)($_POST['description'] ?? ''));
            $sku       = code('PROD');

            // PROSES UPLOAD GAMBAR PRODUK OLAHAN KOPERASI ANAK (STEP 8)
            $imagePath = null;
            if (!empty($_FILES['image']['name'])) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    throw new RuntimeException('Format gambar produk harus berupa file JPG, JPEG, PNG, atau WEBP.');
                }
                $imageFilename = 'prod_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                $targetDir     = __DIR__ . '/../storage/uploads/';
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetDir . $imageFilename)) {
                    throw new RuntimeException('Gagal mengunggah gambar sampel produk.');
                }
                $imagePath = $imageFilename;
            }

            sql("INSERT INTO products(coop_id, raw_type_id, sku, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?, 0, ?)", [
                $u['coop_id'], $rawTypeId, $sku, $name, $desc, $price, $imagePath
            ]);
            $pid = (int)db()->lastInsertId();
            audit($u['id'], 'create_product', 'products', $pid, 'Menambahkan katalog produk olahan baru dengan gambar');
            flash('Produk olahan baru beserta gambar berhasil ditambahkan ke katalog Sektor!', 'success');
            redirect('products');

        case 'log_production':
            check_coop_active($u);
            $allocId   = post_id('allocation_id');
            $productId = post_id('product_id');
            $inQty     = positive('input_qty');
            $outQty    = positive('output_qty');

            db()->beginTransaction();
            try {
                sql("INSERT INTO production_logs(allocation_id, product_id, input_qty, output_qty) VALUES (?, ?, ?, ?)", [
                    $allocId, $productId, $inQty, $outQty
                ]);
                $lid = (int)db()->lastInsertId();
                sql("UPDATE products SET stock = stock + ? WHERE id=?", [$outQty, $productId]);
                
                db()->commit();
                audit($u['id'], 'log_production', 'production_logs', $lid, 'Catatan konversi produksi olahan sektor');
                flash('Konversi produksi berhasil dicatat! Stok produk Sektor bertambah ' . number($outQty) . ' kg.', 'success');
                redirect('products');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'create_order':
            must_role(['buyer']);
            
            $buyerId = $u['buyer_id'];
            if (!$buyerId) {
                $nib = 'BUY-' . date('Ymd') . '-' . rand(1000, 9999);
                sql("INSERT INTO buyer_profiles(company_name, nib, address, city) VALUES (?, ?, 'Kawasan Industri Jabodetabek', 'Jakarta')", [
                    $u['name'], $nib
                ]);
                $buyerId = (int)db()->lastInsertId();
                sql("UPDATE users SET buyer_id=? WHERE id=?", [$buyerId, $u['id']]);
            }

            $productId = post_id('product_id');
            $qty       = positive('quantity');
            $addr      = required_str('shipping_address');
            $city      = trim((string)($_POST['city'] ?? 'Jakarta'));

            if (!empty($city)) {
                sql("UPDATE buyer_profiles SET city=? WHERE id=?", [$city, $buyerId]);
            }

            $prod = one("SELECT * FROM products WHERE id=? AND is_active=1", [$productId]);
            if (!$prod || (float)$prod['stock'] < $qty) {
                throw new RuntimeException('Stok produk olahan Sektor tidak mencukupi untuk jumlah pemesanan Anda.');
            }

            $total = round((float)$prod['price'] * $qty, 2);
            $code  = code('ORD');

            db()->beginTransaction();
            try {
                sql("INSERT INTO orders(order_code, buyer_id, shipping_address, total, status) VALUES (?, ?, ?, ?, 'requested')", [
                    $code, $buyerId, $addr, $total
                ]);
                $oid = (int)db()->lastInsertId();
                sql("INSERT INTO order_items(order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)", [
                    $oid, $productId, $qty, $prod['price']
                ]);

                db()->commit();
                audit($u['id'], 'create_order', 'orders', $oid, 'Pengajuan pesanan B2B industri sektor baru');
                flash('Pesanan industri berhasil dibuat! Menunggu persetujuan Koperasi Sektor.', 'success');
                redirect('orders');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'approve_order':
            check_coop_active($u);
            $id     = post_id('id');
            $status = required_str('status');

            db()->beginTransaction();
            try {
                sql("UPDATE orders SET status=?, approved_by=? WHERE id=?", [$status, $u['id'], $id]);
                if ($status === 'approved') {
                    $items = rows("SELECT * FROM order_items WHERE order_id=?", [$id]);
                    foreach ($items as $it) {
                        sql("UPDATE products SET stock = stock - ? WHERE id=?", [$it['quantity'], $it['product_id']]);
                    }
                }
                db()->commit();
                audit($u['id'], 'approve_order', 'orders', $id, 'Koperasi Sektor menyetujui order ke ' . $status);
                flash('Status pesanan berhasil diperbarui.');
                redirect('orders');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'assign_driver':
            must_role(['super_admin', 'induk', 'supplier', 'coop']);
            $orderId  = post_id('id');
            $driverId = post_id('driver_id');

            db()->beginTransaction();
            try {
                $existId = val("SELECT id FROM shipments WHERE order_id=?", [$orderId]);
                
                if ($existId) {
                    sql("UPDATE shipments SET driver_id=?, status='assigned' WHERE id=?", [$driverId, $existId]);
                    $sid = (int)$existId;
                } else {
                    sql("INSERT INTO shipments(order_id, driver_id, status) VALUES (?, ?, 'assigned')", [
                        $orderId, $driverId
                    ]);
                    $sid = (int)db()->lastInsertId();
                }

                sql("UPDATE orders SET status='assigned' WHERE id=?", [$orderId]);
                
                sql("INSERT INTO shipment_events(shipment_id, event_type, notes, actor_id) VALUES (?, 'assigned', 'Driver armada ditugaskan untuk pengiriman Sektor Jabodetabek', ?)", [
                    $sid, $u['id']
                ]);

                db()->commit();
                audit($u['id'], 'assign_driver', 'shipments', $sid, 'Penugasan driver armada ke pesanan sektor');
                flash('Driver armada berhasil ditugaskan!', 'success');
                redirect('shipments');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'shipment_event':
            must_role(['driver']);
            $shipId    = post_id('id');
            $type      = required_str('event_type');
            $note      = required_str('note');
            $lat       = coord('latitude', -90, 90, true);
            $lng       = coord('longitude', -180, 180, true);

            $proofPath = null;
            if (!empty($_FILES['proof']['name'])) {
                $ext = strtolower(pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true)) {
                    throw new RuntimeException('Format berkas bukti harus berupa gambar (JPG/PNG) atau PDF.');
                }
                $filename = 'proof_' . $shipId . '_' . time() . '.' . $ext;
                $target   = __DIR__ . '/../storage/uploads/' . $filename;
                if (!move_uploaded_file($_FILES['proof']['tmp_name'], $target)) {
                    throw new RuntimeException('Gagal menyimpan foto bukti pengiriman.');
                }
                $proofPath = $filename;
            }

            db()->beginTransaction();
            try {
                $newStatus = match($type) {
                    'accepted'         => 'accepted',
                    'pickup_requested' => 'pickup_pending',
                    'location'         => 'in_transit',
                    'delivered'        => 'delivered',
                    default            => 'in_transit'
                };

                sql("UPDATE shipments SET status=? WHERE id=?", [$newStatus, $shipId]);
                $orderId = val("SELECT order_id FROM shipments WHERE id=?", [$shipId]);
                sql("UPDATE orders SET status=? WHERE id=?", [$newStatus, $orderId]);

                sql("INSERT INTO shipment_events(shipment_id, event_type, notes, latitude, longitude, actor_id) VALUES (?, ?, ?, ?, ?, ?)", [
                    $shipId, $type, ($proofPath ? "[Bukti: $proofPath] " : "") . $note, $lat, $lng, $u['id']
                ]);

                db()->commit();
                audit($u['id'], 'shipment_event', 'shipments', $shipId, 'Update status driver ke ' . $type);
                flash('Pembaruan rute pengiriman Jabodetabek berhasil dikirim!');
                redirect('shipments');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'approve_pickup':
            must_role(['super_admin', 'induk', 'supplier', 'coop']);
            $shipId   = post_id('id');
            $decision = required_str('decision');
            $note     = required_str('note');

            $status = ($decision === 'approved') ? 'picked_up' : 'pickup_rejected';
            $type   = ($decision === 'approved') ? 'picked_up' : 'pickup_rejected';

            db()->beginTransaction();
            try {
                sql("UPDATE shipments SET status=?, picked_up_at=NOW() WHERE id=?", [$status, $shipId]);
                $orderId = val("SELECT order_id FROM shipments WHERE id=?", [$shipId]);
                sql("UPDATE orders SET status=? WHERE id=?", [$status, $orderId]);

                sql("INSERT INTO shipment_events(shipment_id, event_type, notes, actor_id) VALUES (?, ?, ?, ?)", [
                    $shipId, $type, 'ACC Handover Serah Terima Muatan: ' . $note, $u['id']
                ]);

                db()->commit();
                audit($u['id'], 'approve_pickup', 'shipments', $shipId, 'Respon persetujuan pickup ke ' . $status);
                flash('Konfirmasi penyerahan barang gudang berhasil disimpan & ACC disetujui!', 'success');
                redirect('shipments');
            } catch (Throwable $e) {
                db()->rollBack();
                throw $e;
            }

        case 'confirm_order_received':
            must_role(['buyer']);
            $id = post_id('id');

            sql("UPDATE orders SET status='completed' WHERE id=?", [$id]);
            audit($u['id'], 'confirm_order_received', 'orders', $id, 'Pembeli mengonfirmasi barang telah diterima');
            flash('Terima kasih! Pesanan industri Jabodetabek telah selesai secara sempurna.', 'success');
            redirect('orders');

        default:
            throw new RuntimeException('Aksi yang Anda minta tidak dikenali.');
    }
}