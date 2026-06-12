<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

$db      = Database::getInstance();
$msg_ok  = '';
$msg_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    if ($action === 'update_status' && $id) {
        $status   = $_POST['status'] ?? '';
        $notes    = trim($_POST['admin_notes'] ?? '');
        $assigned = trim($_POST['assigned_to'] ?? '');
        $allowed  = ['pendiente','en_proceso','completada','cancelada'];

        if (in_array($status, $allowed)) {
            $db->update(
                "UPDATE farming_configs SET status=?, admin_notes=?, assigned_to=? WHERE id=?",
                [$status, $notes, $assigned, $id]
            );
            $msg_ok = 'Configuración actualizada.';
        }
    }

    if ($action === 'delete' && $id) {
        $db->update("DELETE FROM farming_configs WHERE id=?", [$id]);
        $msg_ok = 'Configuración eliminada.';
    }
}

$filter_status = $_GET['status'] ?? '';
$allowed_filters = ['pendiente','en_proceso','completada','cancelada'];
$where  = '';
$params = [];
if (in_array($filter_status, $allowed_filters)) {
    $where  = ' WHERE status = ?';
    $params = [$filter_status];
}

$configs = $db->fetchAll(
    "SELECT * FROM farming_configs{$where} ORDER BY created_at DESC LIMIT 100",
    $params
);

$detalle = null;
if (isset($_GET['ver'])) {
    $detalle = $db->fetch(
        "SELECT * FROM farming_configs WHERE id=?",
        [(int)$_GET['ver']]
    );
    if ($detalle) {
        $detalle['answers'] = json_decode($detalle['answers_json'], true) ?? [];
    }
}

$counts = [
    'pendiente'  => $db->count("SELECT COUNT(*) FROM farming_configs WHERE status='pendiente'"),
    'en_proceso' => $db->count("SELECT COUNT(*) FROM farming_configs WHERE status='en_proceso'"),
    'completada' => $db->count("SELECT COUNT(*) FROM farming_configs WHERE status='completada'"),
    'cancelada'  => $db->count("SELECT COUNT(*) FROM farming_configs WHERE status='cancelada'"),
];

$page_title = 'Farming Configs | Panel Admin | Latin Shop';
$extra_css  = ['admin.css'];
include INCLUDES_PATH . '/header.php';
?>

<style>
.afc-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; margin-bottom: 1.5rem; }
@media (max-width:600px) { .afc-stats { grid-template-columns: repeat(2,1fr); } }
.afc-stat {
    background: var(--tbt-bg-2);
    border: 1px solid var(--tbt-bg-4);
    border-radius: 10px;
    padding: 1rem;
    text-align: center;
}
.afc-stat__num { font-family: var(--tbt-font-display); font-size: 2rem; font-weight: 700; color: var(--tbt-jade); }
.afc-stat__lbl { font-size: .72rem; color: var(--tbt-txt-muted); text-transform: uppercase; letter-spacing: .07em; margin-top: 2px; }
.afc-stat--warn .afc-stat__num { color: #f0b429; }
.afc-stat--ok   .afc-stat__num { color: #4ade80; }
.afc-stat--muted .afc-stat__num { color: var(--tbt-txt-muted); }

.afc-filter { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.afc-pill {
    padding: .3rem .9rem; border-radius: 50px; font-size: .75rem; font-weight: 700;
    border: 1px solid var(--tbt-bg-4); background: var(--tbt-bg-2);
    color: var(--tbt-txt-muted); text-decoration: none; transition: all .15s;
}
.afc-pill:hover { border-color: var(--tbt-jade); color: var(--tbt-txt-white); }
.afc-pill.active { background: rgba(232,96,44,.12); border-color: var(--tbt-jade); color: var(--tbt-jade); }

.afc-table { width: 100%; border-collapse: collapse; font-size: .88rem; }
.afc-table th {
    text-align: left; padding: 10px 12px;
    font-size: .7rem; font-weight: 700; letter-spacing: 1.5px;
    text-transform: uppercase; color: var(--tbt-txt-muted);
    border-bottom: 1px solid var(--tbt-bg-4);
}
.afc-table td {
    padding: 11px 12px; border-bottom: 1px solid rgba(255,255,255,.03);
    color: var(--tbt-txt-light); vertical-align: middle;
}
.afc-table tr:hover td { background: rgba(255,255,255,.02); }

.sbadge {
    display: inline-block; padding: 2px 10px;
    border-radius: 50px; font-size: .65rem; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
}
.sbadge--pendiente  { background: rgba(240,180,41,.12); border: 1px solid rgba(240,180,41,.3); color: #f0b429; }
.sbadge--en_proceso { background: rgba(85,153,255,.12); border: 1px solid rgba(85,153,255,.3); color: #88bbff; }
.sbadge--completada { background: rgba(34,197,94,.12);  border: 1px solid rgba(34,197,94,.3);  color: #4ade80; }
.sbadge--cancelada  { background: rgba(239,68,68,.1);   border: 1px solid rgba(239,68,68,.25); color: #f87171; }

.afc-answer { padding: .6rem .75rem; border-bottom: 1px solid var(--tbt-bg-3); font-size: .875rem; }
.afc-answer:last-child { border-bottom: none; }
.afc-answer__q { font-size: .72rem; font-weight: 700; color: var(--tbt-txt-muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
.afc-answer__a { color: var(--tbt-txt-white); }

.afc-form-inline { display: flex; gap: .5rem; align-items: center; flex-wrap: wrap; }
.afc-input, .afc-select {
    background: var(--tbt-bg-2); border: 1px solid var(--tbt-bg-4);
    border-radius: 8px; color: var(--tbt-txt-white);
    font-family: var(--tbt-font-body); font-size: .85rem;
    padding: .45rem .75rem; outline: none; transition: border-color .2s;
}
.afc-input:focus, .afc-select:focus { border-color: var(--tbt-jade); }
.afc-select option { background: var(--tbt-bg-2); }
.afc-textarea { min-height: 70px; resize: vertical; width: 100%; margin-top: .5rem; }

.afc-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: .45rem 1rem; border-radius: 8px;
    font-family: var(--tbt-font-body); font-size: .8rem; font-weight: 700;
    cursor: pointer; border: none; transition: opacity .2s;
}
.afc-btn:hover { opacity: .85; }
.afc-btn--primary { background: var(--tbt-jade); color: #000; }
.afc-btn--ghost   { background: var(--tbt-bg-4); color: var(--tbt-txt-light); border: 1px solid var(--tbt-bg-5); }
.afc-btn--danger  { background: rgba(239,68,68,.15); color: #f87171; border: 1px solid rgba(239,68,68,.3); }
.afc-btn--sm      { padding: .3rem .7rem; font-size: .73rem; }

.afc-panel {
    background: var(--tbt-bg-1); border: 1px solid var(--tbt-bg-4);
    border-radius: 14px; padding: 1.5rem; margin-bottom: 1.5rem;
    position: relative; overflow: hidden;
}
.afc-panel::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--tbt-jade), transparent);
}
.afc-panel__title {
    font-family: var(--tbt-font-display); font-size: 1.2rem;
    letter-spacing: 2px; color: var(--tbt-txt-white);
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
}
</style>

<div class="admin-layout">
    <?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">⚙ Configuraciones Farming</h1>
                <p class="admin-page-sub">Solicitudes de instalación de bot recibidas</p>
            </div>
            <?php if ($detalle): ?>
            <a href="<?= u('/admin/farming') ?>" class="afc-btn afc-btn--ghost">← Volver</a>
            <?php endif; ?>
        </div>

        <!-- Alertas -->
        <?php if ($msg_ok): ?>
        <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);color:#4ade80;border-radius:10px;padding:12px 16px;margin-bottom:1rem;font-size:.9rem;">
            ✓ <?= htmlspecialchars($msg_ok) ?>
        </div>
        <?php endif; ?>

        <?php if ($detalle): ?>
        <div class="afc-panel">
            <div class="afc-panel__title">
                🔍 Configuración #<?= $detalle['id'] ?>
                <span class="sbadge sbadge--<?= $detalle['status'] ?>"><?= str_replace('_',' ', $detalle['status']) ?></span>
                <span style="font-size:.8rem;color:var(--tbt-txt-muted);font-family:var(--tbt-font-body);font-weight:400;letter-spacing:0;margin-left:auto">
                    <?= date('d/m/Y H:i', strtotime($detalle['created_at'])) ?>
                </span>
            </div>

            <?php if ($detalle['contact_email'] || $detalle['contact_whatsapp'] || $detalle['contact_telegram']): ?>
            <div style="background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:10px;padding:1rem;margin-bottom:1.25rem;display:flex;flex-wrap:wrap;gap:1rem;">
                <?php if ($detalle['contact_email']): ?>
                <div><span style="font-size:.72rem;color:var(--tbt-txt-muted);">EMAIL</span><br>
                    <a href="mailto:<?= htmlspecialchars($detalle['contact_email']) ?>" style="color:var(--tbt-jade)"><?= htmlspecialchars($detalle['contact_email']) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($detalle['contact_whatsapp']): ?>
                <div><span style="font-size:.72rem;color:var(--tbt-txt-muted);">WHATSAPP</span><br>
                    <a href="https://wa.me/<?= preg_replace('/\D/','',$detalle['contact_whatsapp']) ?>" target="_blank" style="color:#25d366"><?= htmlspecialchars($detalle['contact_whatsapp']) ?></a>
                </div>
                <?php endif; ?>
                <?php if ($detalle['contact_telegram']): ?>
                <div><span style="font-size:.72rem;color:var(--tbt-txt-muted);">TELEGRAM</span><br>
                    <span style="color:var(--tbt-txt-white)"><?= htmlspecialchars($detalle['contact_telegram']) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div style="background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:10px;margin-bottom:1.25rem;overflow:hidden;">
                <?php foreach ($detalle['answers'] as $a): ?>
                <div class="afc-answer">
                    <div class="afc-answer__q"><?= htmlspecialchars($a['label']) ?></div>
                    <div class="afc-answer__a"><?= htmlspecialchars($a['answer']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $detalle['id'] ?>">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;">
                    <div>
                        <label style="font-size:.75rem;font-weight:700;color:var(--tbt-txt-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em">Estado</label>
                        <select name="status" class="afc-select" style="width:100%">
                            <?php foreach (['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'Completada','cancelada'=>'Cancelada'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $detalle['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-size:.75rem;font-weight:700;color:var(--tbt-txt-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em">Asignado a</label>
                        <input type="text" name="assigned_to" class="afc-input" style="width:100%"
                               placeholder="Tu nombre" value="<?= htmlspecialchars($detalle['assigned_to'] ?? '') ?>">
                    </div>
                </div>

                <div style="margin-bottom:1rem">
                    <label style="font-size:.75rem;font-weight:700;color:var(--tbt-txt-muted);display:block;margin-bottom:5px;text-transform:uppercase;letter-spacing:.05em">Notas internas</label>
                    <textarea name="admin_notes" class="afc-input afc-textarea"
                              placeholder="Notas de configuración, problemas, observaciones..."><?= htmlspecialchars($detalle['admin_notes'] ?? '') ?></textarea>
                </div>

                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <button type="submit" class="afc-btn afc-btn--primary">💾 Guardar cambios</button>
                    <a href="<?= u('/admin/farming') ?>" class="afc-btn afc-btn--ghost">← Volver</a>

                    <form method="POST" action="" style="margin-left:auto"
                          onsubmit="return confirm('¿Eliminar esta configuración? No se puede deshacer.')">
                        <input type="hidden" name="_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $detalle['id'] ?>">
                        <button type="submit" class="afc-btn afc-btn--danger">🗑 Eliminar</button>
                    </form>
                </div>
            </form>
        </div>

        <?php else: ?>

        <div class="afc-stats">
            <div class="afc-stat afc-stat--warn">
                <div class="afc-stat__num"><?= $counts['pendiente'] ?></div>
                <div class="afc-stat__lbl">Pendientes</div>
            </div>
            <div class="afc-stat">
                <div class="afc-stat__num" style="color:#88bbff"><?= $counts['en_proceso'] ?></div>
                <div class="afc-stat__lbl">En proceso</div>
            </div>
            <div class="afc-stat afc-stat--ok">
                <div class="afc-stat__num"><?= $counts['completada'] ?></div>
                <div class="afc-stat__lbl">Completadas</div>
            </div>
            <div class="afc-stat afc-stat--muted">
                <div class="afc-stat__num"><?= $counts['cancelada'] ?></div>
                <div class="afc-stat__lbl">Canceladas</div>
            </div>
        </div>

        <div class="afc-filter">
            <a href="<?= u('/admin/farming') ?>" class="afc-pill <?= $filter_status === '' ? 'active' : '' ?>">Todas</a>
            <a href="?status=pendiente"  class="afc-pill <?= $filter_status === 'pendiente'  ? 'active' : '' ?>">⏳ Pendientes</a>
            <a href="?status=en_proceso" class="afc-pill <?= $filter_status === 'en_proceso' ? 'active' : '' ?>">🔧 En proceso</a>
            <a href="?status=completada" class="afc-pill <?= $filter_status === 'completada' ? 'active' : '' ?>">✅ Completadas</a>
            <a href="?status=cancelada"  class="afc-pill <?= $filter_status === 'cancelada'  ? 'active' : '' ?>">❌ Canceladas</a>
        </div>

        <div class="afc-panel">
            <div class="afc-panel__title">📋 Solicitudes</div>
            <?php if (empty($configs)): ?>
            <p style="color:var(--tbt-txt-muted);text-align:center;padding:2rem 0">No hay configuraciones<?= $filter_status ? ' con este filtro' : '' ?>.</p>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table class="afc-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Banco</th>
                            <th>Estado</th>
                            <th>Asignado</th>
                            <th>Contacto</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($configs as $c):
                        $answers = json_decode($c['answers_json'], true) ?? [];
                        $banco   = '';
                        foreach ($answers as $a) {
                            if ($a['q_id'] === 1) { $banco = $a['answer']; break; }
                        }
                    ?>
                    <tr>
                        <td style="font-family:var(--tbt-font-mono);color:var(--tbt-txt-muted);font-size:.8rem">#<?= $c['id'] ?></td>
                        <td style="font-weight:700;color:var(--tbt-txt-white)"><?= htmlspecialchars($banco ?: '—') ?></td>
                        <td><span class="sbadge sbadge--<?= $c['status'] ?>"><?= str_replace('_',' ', $c['status']) ?></span></td>
                        <td style="font-size:.82rem;color:var(--tbt-txt-sub)"><?= htmlspecialchars($c['assigned_to'] ?? '—') ?></td>
                        <td style="font-size:.78rem;color:var(--tbt-txt-muted)">
                            <?php if ($c['contact_whatsapp']): ?>
                            <a href="https://wa.me/<?= preg_replace('/\D/','',$c['contact_whatsapp']) ?>" target="_blank" style="color:#25d366">WA</a>
                            <?php elseif ($c['contact_email']): ?>
                            <a href="mailto:<?= htmlspecialchars($c['contact_email']) ?>" style="color:var(--tbt-jade)">Email</a>
                            <?php else: echo '—'; endif; ?>
                        </td>
                        <td style="font-size:.78rem;color:var(--tbt-txt-muted)"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="?ver=<?= $c['id'] ?>" class="afc-btn afc-btn--ghost afc-btn--sm">👁 Ver</a>
                                <!-- Cambio rápido de estado -->
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <input type="hidden" name="admin_notes" value="<?= htmlspecialchars($c['admin_notes'] ?? '') ?>">
                                    <input type="hidden" name="assigned_to" value="<?= htmlspecialchars($c['assigned_to'] ?? '') ?>">
                                    <select name="status" class="afc-select" style="font-size:.72rem;padding:.25rem .5rem"
                                            onchange="this.form.submit()">
                                        <?php foreach (['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'✅ Completada','cancelada'=>'❌ Cancelada'] as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= $c['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </main>
</div>

<?php include INCLUDES_PATH . '/footer.php'; ?>