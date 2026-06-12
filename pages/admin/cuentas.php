<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$_a = Auth::getInstance();
if (!$_a->isLoggedIn() || $_a->getUser()['role'] !== 'admin') {
    header('Location: ' . u('/login')); exit;
}

$db  = Database::getInstance();
$msg = '';
$msgType = 'success';
$editing = null;

$IMG_COLS = ['image_url'];
for ($n = 2; $n <= 10; $n++) $IMG_COLS[] = "image_url_{$n}";
$UPLOAD_DIR = 'frontend/assets/images/cuentas/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $dir = ROOT_PATH . '/' . $UPLOAD_DIR;
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $newImgs = [];
        foreach ($_FILES as $field => $file) {
            if (!str_starts_with($field, 'image_slot_')) continue;
            if ($file['error'] !== UPLOAD_ERR_OK || empty($file['name'])) continue;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','webp'], true) || !@getimagesize($file['tmp_name'])) continue;
            $slot = (int)substr($field, strlen('image_slot_'));
            if ($slot < 1 || $slot > 10) continue;
            $fname = 'cuenta_' . uniqid('', true) . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $dir . $fname);
            $newImgs[$slot] = $UPLOAD_DIR . $fname;
        }

        $deleteSlots = array_filter(explode(',', $_POST['delete_slots'] ?? ''), 'is_numeric');

        $waNum = preg_replace('/[^0-9]/', '', $_POST['whatsapp_number'] ?? '527862286246');
        $price = max(0, (float)str_replace(',', '', $_POST['price'] ?? '0'));

        if ($action === 'create') {
            $imgValues = [];
            for ($n = 1; $n <= 10; $n++) $imgValues[] = $newImgs[$n] ?? null;

            $db->insert("
                INSERT INTO cuentas_venta
                    (title, description, price, currency, server, power, kingdom, vip_level, heroes,
                     image_url, image_url_2, image_url_3, image_url_4, image_url_5,
                     image_url_6, image_url_7, image_url_8, image_url_9, image_url_10,
                     whatsapp_number, whatsapp_msg, status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ", array_merge([
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                $price,
                $_POST['currency'] ?? 'USD',
                trim($_POST['server'] ?? ''),
                trim($_POST['power'] ?? ''),
                trim($_POST['kingdom'] ?? ''),
                $_POST['vip_level'] !== '' ? (int)$_POST['vip_level'] : null,
                trim($_POST['heroes'] ?? ''),
            ], $imgValues, [
                $waNum,
                trim($_POST['whatsapp_msg'] ?? ''),
                $_POST['status'] ?? 'active',
            ]));
            $msg = 'Cuenta creada correctamente.';

        } else {
            $id  = (int)$_POST['cuenta_id'];
            $old = $db->fetch("SELECT " . implode(',', $IMG_COLS) . " FROM cuentas_venta WHERE id = ?", [$id]);

            $setParts = [
                "title=?","description=?","price=?","currency=?",
                "server=?","power=?","kingdom=?","vip_level=?","heroes=?",
                "whatsapp_number=?","whatsapp_msg=?","status=?"
            ];
            $setParams = [
                trim($_POST['title'] ?? ''),
                trim($_POST['description'] ?? ''),
                $price,
                $_POST['currency'] ?? 'USD',
                trim($_POST['server'] ?? ''),
                trim($_POST['power'] ?? ''),
                trim($_POST['kingdom'] ?? ''),
                $_POST['vip_level'] !== '' ? (int)$_POST['vip_level'] : null,
                trim($_POST['heroes'] ?? ''),
                $waNum,
                trim($_POST['whatsapp_msg'] ?? ''),
                $_POST['status'] ?? 'active',
            ];

            for ($n = 1; $n <= 10; $n++) {
                $col = $IMG_COLS[$n - 1];
                if (in_array((string)$n, $deleteSlots, true)) {
                    if (!empty($old[$col])) @unlink(ROOT_PATH . '/' . $old[$col]);
                    $setParts[]  = "{$col}=NULL";
                } elseif (isset($newImgs[$n])) {
                    if (!empty($old[$col])) @unlink(ROOT_PATH . '/' . $old[$col]);
                    $setParts[]  = "{$col}=?";
                    $setParams[] = $newImgs[$n];
                }
            }

            $setParams[] = $id;
            $db->update("UPDATE cuentas_venta SET " . implode(',', $setParts) . " WHERE id=?", $setParams);
            $msg = 'Cuenta actualizada.';
        }
    }

    if ($action === 'delete') {
        $id  = (int)$_POST['cuenta_id'];
        $old = $db->fetch("SELECT " . implode(',', $IMG_COLS) . " FROM cuentas_venta WHERE id = ?", [$id]);
        if ($old) foreach ($IMG_COLS as $col) {
            if (!empty($old[$col])) @unlink(ROOT_PATH . '/' . $old[$col]);
        }
        $db->update("DELETE FROM cuentas_venta WHERE id = ?", [$id]);
        $msg = 'Cuenta eliminada.';
    }
}

if (isset($_GET['edit'])) {
    $editing = $db->fetch("SELECT * FROM cuentas_venta WHERE id = ?", [(int)$_GET['edit']]);
}

$cuentas    = $db->fetchAll("SELECT * FROM cuentas_venta ORDER BY created_at DESC LIMIT 60");
$page_title = 'Admin · Cuentas en Venta';
require_once INCLUDES_PATH . '/header.php';
?>

<div class="admin-layout">
<?php require_once INCLUDES_PATH . '/admin-sidebar.php'; ?>
<main class="admin-main">

    <div class="admin-header">
        <h1>🎮 Cuentas en Venta</h1>
        <a href="<?= u('/cuentas') ?>" target="_blank" class="tbt-btn tbt-btn--outline" style="font-size:.85rem;padding:.4rem 1rem;">
            Ver pública →
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="admin-alert <?= $msgType ?>" style="margin-bottom:1rem;"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <section class="tbt-card" style="padding:var(--tbt-s3);margin-bottom:var(--tbt-s3);">
        <h2 class="ca-section-title"><?= $editing ? '✏ Editar cuenta' : '+ Agregar cuenta' ?></h2>

        <form method="POST" enctype="multipart/form-data" id="ca-form">
            <input type="hidden" name="action"    value="<?= $editing ? 'update' : 'create' ?>">
            <input type="hidden" name="delete_slots" id="ca-delete-slots" value="">
            <?php if ($editing): ?>
            <input type="hidden" name="cuenta_id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <!-- Fila 1: título + estado -->
            <div class="ca-row">
                <div class="ca-group" style="flex:3">
                    <label>Título *</label>
                    <input type="text" name="title" required
                           value="<?= htmlspecialchars($editing['title'] ?? '') ?>"
                           placeholder="Ej: Cuenta T5 servidor 801">
                </div>
                <div class="ca-group">
                    <label>Estado</label>
                    <select name="status">
                        <option value="active"  <?= ($editing['status'] ?? 'active') === 'active'  ? 'selected' : '' ?>>Disponible</option>
                        <option value="sold"    <?= ($editing['status'] ?? '') === 'sold'    ? 'selected' : '' ?>>Vendida</option>
                        <option value="hidden"  <?= ($editing['status'] ?? '') === 'hidden'  ? 'selected' : '' ?>>Oculta</option>
                    </select>
                </div>
            </div>

            <div class="ca-row">
                <div class="ca-group">
                    <label>Precio</label>
                    <input type="number" name="price" min="0" step="0.01"
                           value="<?= $editing['price'] ?? '0' ?>">
                </div>
                <div class="ca-group">
                    <label>Moneda</label>
                    <select name="currency">
                        <option value="USD" <?= ($editing['currency'] ?? 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                        <option value="MXN" <?= ($editing['currency'] ?? '') === 'MXN' ? 'selected' : '' ?>>MXN</option>
                        <option value="EUR" <?= ($editing['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                    </select>
                </div>
                <div class="ca-group">
                    <label>Poder</label>
                    <input type="text" name="power"
                           value="<?= htmlspecialchars($editing['power'] ?? '') ?>"
                           placeholder="Ej: 850M">
                </div>
                <div class="ca-group">
                    <label>Servidor</label>
                    <input type="text" name="server"
                           value="<?= htmlspecialchars($editing['server'] ?? '') ?>"
                           placeholder="Ej: 801">
                </div>
                <div class="ca-group">
                    <label>Reino</label>
                    <input type="text" name="kingdom"
                           value="<?= htmlspecialchars($editing['kingdom'] ?? '') ?>">
                </div>
                <div class="ca-group">
                    <label>VIP</label>
                    <input type="number" name="vip_level" min="0" max="15"
                           value="<?= $editing['vip_level'] ?? '' ?>"
                           placeholder="0-15">
                </div>
            </div>

            <div class="ca-group">
                <label>Héroes / Items destacados</label>
                <input type="text" name="heroes"
                       value="<?= htmlspecialchars($editing['heroes'] ?? '') ?>"
                       placeholder="Ej: Pact 4 completo, Lyrica…">
            </div>

            <div class="ca-group">
                <label>Descripción completa *</label>
                <textarea name="description" rows="5" required><?= htmlspecialchars($editing['description'] ?? '') ?></textarea>
            </div>

            <div class="ca-row">
                <div class="ca-group">
                    <label>WhatsApp (con código país)</label>
                    <input type="text" name="whatsapp_number"
                           value="<?= htmlspecialchars($editing['whatsapp_number'] ?? '527862286246') ?>"
                           placeholder="527862286246">
                </div>
                <div class="ca-group" style="flex:2">
                    <label>Mensaje de WA personalizado</label>
                    <input type="text" name="whatsapp_msg"
                           value="<?= htmlspecialchars($editing['whatsapp_msg'] ?? '') ?>"
                           placeholder="Hola, me interesa la cuenta…">
                </div>
            </div>

            <div class="ca-group">
                <label>Fotos (hasta 10) — arrastra o haz clic en cada slot</label>

                <div class="ca-dropzone" id="ca-dropzone">
                    <div class="ca-dropzone__hint">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M19.35 10.04A7.49 7.49 0 0 0 12 4C9.11 4 6.6 5.64 5.35 8.04A5.994 5.994 0 0 0 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM14 13v4h-4v-4H7l5-5 5 5h-3z"/></svg>
                        <span>Arrastra fotos aquí o haz clic en un slot</span>
                    </div>

                    <div class="ca-slots" id="ca-slots">
                        <?php for ($slot = 1; $slot <= 10; $slot++):
                            $col     = $slot === 1 ? 'image_url' : "image_url_{$slot}";
                            $current = $editing[$col] ?? null;
                        ?>
                        <div class="ca-slot" id="ca-slot-<?= $slot ?>" data-slot="<?= $slot ?>">
                            <input type="file" name="image_slot_<?= $slot ?>"
                                   id="ca-file-<?= $slot ?>"
                                   accept="image/jpeg,image/png,image/webp"
                                   class="ca-slot__input">

                            <?php if ($current): ?>
                            <div class="ca-slot__preview" id="ca-preview-<?= $slot ?>">
                                <img src="/<?= htmlspecialchars($current) ?>" alt="">
                                <button type="button" class="ca-slot__remove" data-slot="<?= $slot ?>"
                                        title="Eliminar foto">✕</button>
                                <span class="ca-slot__num"><?= $slot ?></span>
                            </div>
                            <?php else: ?>
                            <label for="ca-file-<?= $slot ?>" class="ca-slot__empty">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                                <span><?= $slot ?></span>
                            </label>
                            <div class="ca-slot__preview" id="ca-preview-<?= $slot ?>" style="display:none;">
                                <img src="" alt="">
                                <button type="button" class="ca-slot__remove" data-slot="<?= $slot ?>">✕</button>
                                <span class="ca-slot__num"><?= $slot ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <small style="color:var(--tbt-txt-muted);font-size:.75rem;">JPG, PNG o WEBP · Max 5MB por foto · Slot 1 = foto de portada</small>
            </div>

            <div style="display:flex;gap:.75rem;margin-top:var(--tbt-s2);">
                <button type="submit" class="tbt-btn tbt-btn--jade">
                    <?= $editing ? 'Guardar cambios' : 'Agregar cuenta' ?>
                </button>
                <?php if ($editing): ?>
                <a href="<?= u('/admin/cuentas') ?>" class="tbt-btn tbt-btn--outline">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="tbt-card" style="padding:var(--tbt-s3);">
        <h2 class="ca-section-title">Cuentas (<?= count($cuentas) ?>)</h2>
        <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr><th>Foto</th><th>Título</th><th>Poder</th><th>Precio</th><th>Fotos</th><th>Estado</th><th>Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($cuentas as $c):
                $nFotos = 0;
                foreach ($IMG_COLS as $col) { if (!empty($c[$col])) $nFotos++; }
                $thumb = null;
                foreach ($IMG_COLS as $col) { if (!empty($c[$col])) { $thumb = $c[$col]; break; } }
            ?>
            <tr>
                <td>
                    <?php if ($thumb): ?>
                    <img src="/<?= htmlspecialchars($thumb) ?>" alt=""
                         style="width:52px;height:40px;object-fit:cover;border-radius:4px;border:1px solid var(--tbt-bg-4);">
                    <?php else: ?>
                    <span style="color:var(--tbt-txt-dim);font-size:.75rem;">—</span>
                    <?php endif; ?>
                </td>
                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($c['title']) ?>
                </td>
                <td><?= htmlspecialchars($c['power'] ?? '—') ?></td>
                <td><?= $c['price'] > 0 ? number_format((float)$c['price'], 0, '.', ',') . ' ' . $c['currency'] : 'Consultar' ?></td>
                <td style="font-family:var(--tbt-font-mono);font-size:.78rem;color:var(--tbt-txt-sub);">
                    <?= $nFotos ?> / 10
                </td>
                <td>
                    <span style="color:<?= $c['status']==='active'?'#4ade80':($c['status']==='sold'?'#f87171':'#f59e0b') ?>;">
                        <?= ['active'=>'Disponible','sold'=>'Vendida','hidden'=>'Oculta'][$c['status']] ?? $c['status'] ?>
                    </span>
                </td>
                <td>
                    <div style="display:flex;gap:.4rem;">
                        <a href="<?= u('/cuentas/ver?id=' . $c['id']) ?>" target="_blank" class="admin-btn-sm">👁</a>
                        <a href="<?= u('/admin/cuentas?edit=' . $c['id']) ?>" class="admin-btn-sm">✏</a>
                        <form method="POST" onsubmit="return confirm('¿Eliminar esta cuenta?')" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="cuenta_id" value="<?= $c['id'] ?>">
                            <button class="admin-btn-sm danger">🗑</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>

</main>
</div>

<style>
.ca-section-title { font-family:var(--tbt-font-display);font-size:1.3rem;letter-spacing:.06em;color:var(--tbt-jade);margin-bottom:var(--tbt-s2);font-weight:400; }
.ca-row   { display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem; }
.ca-group { display:flex;flex-direction:column;gap:.35rem;flex:1;min-width:150px;margin-bottom:.75rem; }
.ca-group label { font-family:var(--tbt-font-mono);font-size:.68rem;color:var(--tbt-txt-sub);text-transform:uppercase;letter-spacing:.1em; }
.ca-group input[type=text],
.ca-group input[type=number],
.ca-group select,
.ca-group textarea {
    background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-sm);
    color:var(--tbt-txt-white);padding:.6rem .9rem;font-size:.9rem;outline:none;
    font-family:var(--tbt-font-body);transition:border-color .2s;
}
.ca-group input:focus,.ca-group select:focus,.ca-group textarea:focus { border-color:var(--tbt-jade); }

.ca-dropzone {
    background:var(--tbt-bg-1);
    border:1px dashed var(--tbt-bg-5);
    border-radius:var(--tbt-r-md);
    padding:var(--tbt-s3);
    transition:border-color .2s, background .2s;
}
.ca-dropzone.drag-over {
    border-color:var(--tbt-jade);
    background:var(--tbt-jade-04);
}
.ca-dropzone__hint {
    display:flex;align-items:center;justify-content:center;gap:.75rem;
    color:var(--tbt-txt-muted);font-family:var(--tbt-font-mono);
    font-size:.72rem;letter-spacing:.08em;text-transform:uppercase;
    margin-bottom:var(--tbt-s2);
    pointer-events:none;
}

.ca-slots {
    display:grid;
    grid-template-columns:repeat(5, 1fr);
    gap:.5rem;
}
@media(max-width:600px){ .ca-slots{ grid-template-columns:repeat(3,1fr); } }

.ca-slot { position:relative;aspect-ratio:4/3; }

.ca-slot__input {
    position:absolute;inset:0;opacity:0;cursor:pointer;z-index:2;width:100%;height:100%;
}

.ca-slot__empty {
    display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem;
    width:100%;height:100%;
    background:var(--tbt-bg-2);
    border:1px dashed var(--tbt-bg-5);
    border-radius:var(--tbt-r-sm);
    color:var(--tbt-txt-muted);
    cursor:pointer;
    transition:border-color .2s, background .2s, color .2s;
    font-family:var(--tbt-font-mono);font-size:.7rem;
}
.ca-slot__empty:hover, .ca-slot.drag-over .ca-slot__empty {
    border-color:var(--tbt-jade);
    background:var(--tbt-jade-08);
    color:var(--tbt-jade);
}

.ca-slot__preview {
    position:absolute;inset:0;
    border-radius:var(--tbt-r-sm);
    overflow:hidden;
    border:1px solid var(--tbt-jade-30);
}
.ca-slot__preview img { width:100%;height:100%;object-fit:cover;display:block; }

.ca-slot__remove {
    position:absolute;top:.25rem;right:.25rem;
    width:20px;height:20px;
    background:rgba(0,0,0,.75);
    border:1px solid rgba(255,255,255,.2);
    border-radius:3px;
    color:#fff;font-size:.7rem;
    cursor:pointer;z-index:4;
    display:flex;align-items:center;justify-content:center;
    line-height:1;
    transition:background .15s;
}
.ca-slot__remove:hover { background:#dc3545; }

.ca-slot__num {
    position:absolute;bottom:.25rem;left:.3rem;
    font-family:var(--tbt-font-mono);font-size:.6rem;font-weight:700;
    color:rgba(255,255,255,.7);
    background:rgba(0,0,0,.6);
    padding:1px 4px;border-radius:2px;
}

.admin-btn-sm { background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.75);border-radius:4px;padding:.3rem .6rem;font-size:.78rem;cursor:pointer;text-decoration:none;display:inline-block;transition:all .2s; }
.admin-btn-sm:hover { background:rgba(232,96,44,.15);border-color:var(--tbt-jade);color:var(--tbt-jade-light); }
.admin-btn-sm.danger:hover { background:rgba(220,53,69,.15);border-color:#dc3545;color:#f87171; }
</style>

<script <?= csp_nonce() ?>>
(function(){
    const slotsToDelete = new Set();
    const deleteInput   = document.getElementById('ca-delete-slots');
    const dropzone      = document.getElementById('ca-dropzone');

    dropzone?.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
    dropzone?.addEventListener('drop', e => {
        e.preventDefault();
        dropzone.classList.remove('drag-over');
        const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
        if (!files.length) return;
        let fi = 0;
        for (let slot = 1; slot <= 10 && fi < files.length; slot++) {
            const preview = document.getElementById('ca-preview-' + slot);
            const fileInput = document.getElementById('ca-file-' + slot);
            if (!preview || preview.style.display !== 'none' && preview.querySelector('img').src) continue;
            assignFileToSlot(slot, files[fi++]);
        }
    });

    for (let slot = 1; slot <= 10; slot++) {
        const fi = document.getElementById('ca-file-' + slot);
        fi?.addEventListener('change', function() {
            if (this.files[0]) assignFileToSlot(slot, this.files[0]);
        });
    }

    function assignFileToSlot(slot, file) {
        const preview  = document.getElementById('ca-preview-' + slot);
        const emptyLbl = document.querySelector('#ca-slot-' + slot + ' .ca-slot__empty');
        if (!preview) return;

        const reader = new FileReader();
        reader.onload = e => {
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
            if (emptyLbl) emptyLbl.style.display = 'none';
            // Asignar el file al input correcto
            const fi = document.getElementById('ca-file-' + slot);
            if (fi) {
                // DataTransfer trick para asignar file al input
                try {
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fi.files = dt.files;
                } catch(err) { /* Firefox fallback: el file ya está en el input si vino de él */ }
            }
            // Si estaba marcado para borrar, quitar esa marca
            slotsToDelete.delete(slot);
            updateDeleteInput();
        };
        reader.readAsDataURL(file);
    }

    document.querySelectorAll('.ca-slot__remove').forEach(btn => {
        btn.addEventListener('click', function() {
            const slot    = +this.dataset.slot;
            const preview = document.getElementById('ca-preview-' + slot);
            const fi      = document.getElementById('ca-file-' + slot);
            const empty   = document.querySelector('#ca-slot-' + slot + ' .ca-slot__empty');

            if (preview) { preview.style.display = 'none'; preview.querySelector('img').src = ''; }
            if (fi) fi.value = '';
            if (empty) empty.style.display = '';

            slotsToDelete.add(slot);
            updateDeleteInput();
        });
    });

    function updateDeleteInput() {
        if (deleteInput) deleteInput.value = Array.from(slotsToDelete).join(',');
    }

    document.querySelectorAll('.ca-slot').forEach(slotEl => {
        slotEl.addEventListener('dragover',  e => { e.preventDefault(); slotEl.classList.add('drag-over'); });
        slotEl.addEventListener('dragleave', () => slotEl.classList.remove('drag-over'));
        slotEl.addEventListener('drop', e => {
            e.preventDefault();
            slotEl.classList.remove('drag-over');
            const file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                assignFileToSlot(+slotEl.dataset.slot, file);
            }
        });
    });
})();
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
