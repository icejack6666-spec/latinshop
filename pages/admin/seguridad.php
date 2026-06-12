<?php
if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth = Auth::getInstance();
$auth->requireRole('admin');

$security = Security::getInstance();
$usuario  = $auth->getUser();

$msg_ok  = null;
$msg_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'bloquear_ip') {
            $ip        = trim($_POST['ip'] ?? '');
            $razon     = sanitize_string($_POST['razon'] ?? '', 255);
            $permanente = isset($_POST['permanente']);
            $horas     = (int)($_POST['horas'] ?? 24);

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                $msg_err = 'IP no válida.';
            } else {
                $ok = $security->blockIP($ip, $razon ?: 'Bloqueada manualmente', $permanente, $horas);
                $msg_ok  = $ok  ? "IP {$ip} bloqueada correctamente." : null;
                $msg_err = !$ok ? 'No se pudo bloquear la IP.' : null;
            }

        } elseif ($accion === 'desbloquear_ip') {
            $ip = trim($_POST['ip'] ?? '');
            $ok = $security->unblockIP($ip);
            $msg_ok  = $ok  ? "IP {$ip} desbloqueada." : null;
            $msg_err = !$ok ? 'No se pudo desbloquear.' : null;

        } elseif ($accion === 'limpiar_logs') {
            $dias = (int)($_POST['dias'] ?? 30);
            $security->cleanOldLogs($dias);
            $msg_ok = "Logs de más de {$dias} días eliminados.";
        }
    }
}

$filtro_evento = sanitize_string($_GET['evento'] ?? '', 50);
$stats         = $security->getStats();
$logs          = $security->getLogs(100, $filtro_evento);
$ips_bloqueadas = $security->getBlockedIPs();

$page_title = 'Seguridad | Panel Admin | Latin Shop';
$extra_css = ['admin.css'];
include INCLUDES_PATH . '/header.php';
?>

<div class="admin-layout">
    <?php include INCLUDES_PATH . '/admin-sidebar.php'; ?>

    <main class="admin-main">

        <div class="admin-topbar">
            <div>
                <h1 class="admin-page-title">Seguridad</h1>
                <p class="admin-page-sub">Monitoreo y protección del sitio</p>
            </div>
        </div>

        <?php if ($msg_ok): ?>
            <div class="admin-alert admin-alert--ok">✓ <?= htmlspecialchars($msg_ok, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($msg_err): ?>
            <div class="admin-alert admin-alert--err">✕ <?= htmlspecialchars($msg_err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="admin-stats-grid" style="margin-bottom:var(--tbt-s3);">

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--red">🛡</div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $stats['ips_bloqueadas'] ?></span>
                    <span class="admin-stat-card__label">IPs bloqueadas</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--amber">⚠</div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $stats['logs_hoy'] ?></span>
                    <span class="admin-stat-card__label">Eventos hoy</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--red">💉</div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $stats['intentos_sqli'] ?></span>
                    <span class="admin-stat-card__label">Intentos SQLi</span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-card__icon admin-stat-card__icon--amber">🤖</div>
                <div class="admin-stat-card__info">
                    <span class="admin-stat-card__num"><?= $stats['bots_bloqueados'] ?></span>
                    <span class="admin-stat-card__label">Bots bloqueados</span>
                </div>
            </div>

        </div>

        <div class="sec-grid">

            <div>

                <div class="admin-panel" style="margin-bottom:var(--tbt-s3);">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">🚫 Bloquear IP</h2>
                    </div>
                    <div style="padding:var(--tbt-s3);">
                        <form method="POST" action="<?= u('/admin/seguridad') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="bloquear_ip">

                            <div class="sec-field">
                                <label class="sec-label">Dirección IP</label>
                                <input type="text" name="ip" class="sec-input"
                                       placeholder="192.168.1.1" required>
                            </div>
                            <div class="sec-field">
                                <label class="sec-label">Razón</label>
                                <input type="text" name="razon" class="sec-input"
                                       placeholder="Actividad sospechosa...">
                            </div>
                            <div class="sec-field">
                                <label class="sec-label">Duración (horas)</label>
                                <input type="number" name="horas" class="sec-input"
                                       value="24" min="1" max="8760">
                            </div>
                            <div class="sec-check-row">
                                <label class="sec-check-label">
                                    <input type="checkbox" name="permanente" value="1">
                                    <span>Bloqueo permanente</span>
                                </label>
                            </div>
                            <button type="submit" class="admin-btn admin-btn--sm admin-btn--red"
                                    style="margin-top:var(--tbt-s2);width:100%;">
                                🚫 Bloquear IP
                            </button>
                        </form>
                    </div>
                </div>

                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">IPs bloqueadas (<?= count($ips_bloqueadas) ?>)</h2>
                    </div>
                    <div class="admin-table-wrap">
                        <?php if (empty($ips_bloqueadas)): ?>
                            <div class="admin-empty" style="padding:var(--tbt-s3);">
                                <p>No hay IPs bloqueadas</p>
                            </div>
                        <?php else: ?>
                            <table class="admin-table">
                                <thead><tr><th>IP</th><th>Razón</th><th>Expira</th><th></th></tr></thead>
                                <tbody>
                                    <?php foreach ($ips_bloqueadas as $blocked): ?>
                                        <tr>
                                            <td><code style="font-family:var(--tbt-font-mono);font-size:var(--tbt-text-xs);color:var(--tbt-jade-light);"><?= htmlspecialchars($blocked['ip_address'],ENT_QUOTES,'UTF-8') ?></code></td>
                                            <td class="admin-table__muted" style="font-size:var(--tbt-text-xs);"><?= htmlspecialchars($blocked['reason'] ?? '—',ENT_QUOTES,'UTF-8') ?></td>
                                            <td class="admin-table__mono admin-table__muted">
                                                <?= $blocked['permanent'] ? '<span style="color:#f87171;">Permanente</span>' : ($blocked['expires_at'] ? date('d/m H:i',strtotime($blocked['expires_at'])) : '—') ?>
                                            </td>
                                            <td>
                                                <form method="POST" action="<?= u('/admin/seguridad') ?>"
                                                      onsubmit="return confirm('¿Desbloquear esta IP?')">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="accion" value="desbloquear_ip">
                                                    <input type="hidden" name="ip" value="<?= htmlspecialchars($blocked['ip_address'],ENT_QUOTES,'UTF-8') ?>">
                                                    <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost">↩</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div>
                <div class="admin-panel">
                    <div class="admin-panel__header">
                        <h2 class="admin-panel__title">📋 Log de eventos</h2>
                        <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap;">
                            <!-- Filtro por tipo -->
                            <?php
                            $tipos = [''=>'Todos','xss_attempt'=>'XSS','sqli_attempt'=>'SQLi','bad_bot'=>'Bots','blocked_ip_attempt'=>'IPs bloq.','ip_blocked'=>'Bloqueadas'];
                            foreach ($tipos as $key => $label):
                                $activo = $filtro_evento === $key ? 'admin-badge--purple' : 'admin-badge--outline';
                            ?>
                                <a href="<?= u('/admin/seguridad') ?>?evento=<?= $key ?>"
                                   class="admin-badge <?= $activo ?>" style="text-decoration:none;cursor:pointer;">
                                    <?= $label ?>
                                </a>
                            <?php endforeach; ?>

                            <form method="POST" action="<?= u('/admin/seguridad') ?>" style="margin-left:auto;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="accion" value="limpiar_logs">
                                <input type="hidden" name="dias" value="30">
                                <button type="submit" class="admin-btn admin-btn--sm admin-btn--ghost"
                                        onclick="return confirm('¿Eliminar logs de más de 30 días?')">
                                    🗑 Limpiar 30d+
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="admin-table-wrap" style="max-height:500px;overflow-y:auto;">
                        <?php if (empty($logs)): ?>
                            <div class="admin-empty" style="padding:var(--tbt-s3);">
                                <p>No hay logs de seguridad</p>
                            </div>
                        <?php else: ?>
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Evento</th>
                                        <th>IP</th>
                                        <th>Usuario</th>
                                        <th>Descripción</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log):
                                        $badge_class = match($log['event_type']) {
                                            'xss_attempt', 'sqli_attempt' => 'admin-badge--red',
                                            'bad_bot', 'blocked_ip_attempt' => 'admin-badge--amber',
                                            'ip_blocked' => 'admin-badge--red',
                                            default => 'admin-badge--outline',
                                        };
                                    ?>
                                        <tr>
                                            <td>
                                                <span class="admin-badge <?= $badge_class ?>" style="font-size:9px;">
                                                    <?= htmlspecialchars($log['event_type'],ENT_QUOTES,'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <code style="font-family:var(--tbt-font-mono);font-size:var(--tbt-text-xs);color:var(--tbt-jade-light);">
                                                    <?= htmlspecialchars($log['ip_address'],ENT_QUOTES,'UTF-8') ?>
                                                </code>
                                            </td>
                                            <td class="admin-table__muted" style="font-size:var(--tbt-text-xs);">
                                                <?= $log['username'] ? htmlspecialchars($log['username'],ENT_QUOTES,'UTF-8') : '—' ?>
                                            </td>
                                            <td class="admin-table__muted" style="font-size:var(--tbt-text-xs);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($log['description']??'',ENT_QUOTES,'UTF-8') ?>">
                                                <?= htmlspecialchars(substr($log['description']??'',0,80),ENT_QUOTES,'UTF-8') ?>
                                            </td>
                                            <td class="admin-table__mono admin-table__muted" style="white-space:nowrap;">
                                                <?= date('d/m H:i',strtotime($log['created_at'])) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div><!-- /sec-grid -->

    </main>
</div>

<style>
.sec-grid{display:grid;grid-template-columns:320px 1fr;gap:var(--tbt-s3);}
.sec-field{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.75rem;}
.sec-label{font-size:var(--tbt-text-xs);font-weight:600;color:var(--tbt-txt-light);text-transform:uppercase;letter-spacing:.04em;}
.sec-input{background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-5);border-radius:var(--tbt-r-md);color:var(--tbt-txt-white);font-family:var(--tbt-font-mono);font-size:var(--tbt-text-sm);padding:.55rem .75rem;outline:none;width:100%;transition:border-color var(--tbt-t1);}
.sec-input:focus{border-color:var(--tbt-jade-40);}
.sec-input::placeholder{color:var(--tbt-txt-dim);}
.sec-check-row{display:flex;align-items:center;}
.sec-check-label{display:flex;align-items:center;gap:.5rem;font-size:var(--tbt-text-sm);color:var(--tbt-txt-sub);cursor:pointer;}
/* Reutiliza estilos de admin */
.admin-alert{padding:var(--tbt-s2) var(--tbt-s3);border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:500;margin-bottom:var(--tbt-s3);}
.admin-alert--ok{background:rgba(34,197,94,.08);color:#4ade80;border:1px solid rgba(34,197,94,.2);}
.admin-alert--err{background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.2);}
.admin-btn{display:inline-flex;align-items:center;justify-content:center;gap:4px;border:none;border-radius:var(--tbt-r-md);font-family:var(--tbt-font-display);font-weight:600;cursor:pointer;text-decoration:none;transition:opacity var(--tbt-t1),transform var(--tbt-t1);white-space:nowrap;}
.admin-btn:hover{opacity:.82;transform:translateY(-1px);}
.admin-btn--sm{font-size:var(--tbt-text-xs);padding:.35rem .75rem;}
.admin-btn--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25);}
.admin-btn--ghost{background:var(--tbt-bg-2);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5);}
.admin-badge{display:inline-block;font-size:var(--tbt-text-2xs);font-weight:700;font-family:var(--tbt-font-mono);padding:2px 8px;border-radius:var(--tbt-r-full);text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}
.admin-badge--purple{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30);}
.admin-badge--amber{background:var(--tbt-amber-15);color:var(--tbt-amber);border:1px solid var(--tbt-amber-30);}
.admin-badge--red{background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.25);}
.admin-badge--outline{background:var(--tbt-bg-3);color:var(--tbt-txt-sub);border:1px solid var(--tbt-bg-5);}
.admin-empty{display:flex;flex-direction:column;align-items:center;gap:var(--tbt-s1);color:var(--tbt-txt-muted);font-size:var(--tbt-text-sm);text-align:center;}
@media(max-width:900px){.sec-grid{grid-template-columns:1fr;}}
</style>

<?php include INCLUDES_PATH . '/footer.php'; ?>
