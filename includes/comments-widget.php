<?php

if (!defined('LATINSHOP')) die('Acceso directo no permitido.');

$auth     = Auth::getInstance();
$comments = Comments::getInstance();

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path        = parse_url($request_uri, PHP_URL_PATH);
$script_dir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($script_dir !== '' && strpos($path, $script_dir) === 0) {
    $path = substr($path, strlen($script_dir));
}
$page_slug = trim(strtok($path, '?'), '/');
if (empty($page_slug)) $page_slug = 'home';

$usuario        = $auth->getUser();
$logueado       = $auth->isLoggedIn();
$puede_comentar = $logueado && in_array($usuario['role'] ?? '', ['client', 'verified', 'admin'], true);

$flash_ok  = null;
$flash_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_comment_id'])) {
    header('Content-Type: application/json');

    if (!$logueado) {
        echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para votar.']);
        exit;
    }
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'error' => 'Token inválido.']);
        exit;
    }

    $comment_id = (int)$_POST['vote_comment_id'];
    $vote_type  = $_POST['vote_type'] ?? '';
    $resultado  = $comments->vote($comment_id, $usuario['id'], $vote_type);

    echo json_encode($resultado);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comentario_content'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $flash_err = 'Token de seguridad inválido. Recarga la página.';
    } elseif (!$logueado) {
        $flash_err = 'Debes iniciar sesión para comentar.';
    } else {
        $resultado = $comments->add(
            $usuario['id'],
            $page_slug,
            $_POST['comentario_content'] ?? '',
            $usuario['role']
        );

        if ($resultado['success']) {
            $rating = (int)($_POST['rating'] ?? 0);
            if ($rating >= 1 && $rating <= 5 && !empty($resultado['comment_id'])) {
                $comments->setRating((int)$resultado['comment_id'], $rating);
            }
            $flash_ok = $resultado['message'];
        } else {
            $flash_err = $resultado['error'];
        }
    }
}

$lista_comentarios = $comments->getApproved($page_slug);
$total_comentarios = count($lista_comentarios);
$avg_rating        = $comments->getAverageRating($page_slug);

$user_votes = [];
if ($logueado) {
    $user_votes = $comments->getUserVotesForPage($page_slug, $usuario['id']);
}

$vote_counts = [];
foreach ($lista_comentarios as $c) {
    $vote_counts[$c['id']] = $comments->getVoteCounts($c['id']);
}
?>

<section class="cw-seccion" id="comentarios">
    <div class="cw-inner">

        <!-- Encabezado -->
        <div class="cw-header">
            <div class="cw-header__left">
                <h2 class="cw-titulo">
                    <span class="cw-titulo__icono">💬</span>
                    Comentarios
                    <?php if ($total_comentarios > 0): ?>
                        <span class="cw-badge"><?= $total_comentarios ?></span>
                    <?php endif; ?>
                </h2>
                <p class="cw-subtitulo">Comparte tu experiencia con la comunidad</p>
            </div>

            <?php if ($avg_rating > 0): ?>
                <div class="cw-avg-rating">
                    <div class="cw-stars-display">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="cw-star-static <?= $i <= round($avg_rating) ? 'active' : '' ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="cw-avg-num"><?= number_format($avg_rating, 1) ?></span>
                    <span class="cw-avg-label">(<?= $total_comentarios ?> reseña<?= $total_comentarios !== 1 ? 's' : '' ?>)</span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($flash_ok): ?>
            <div class="cw-alert cw-alert--ok">✓ <?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?php if ($flash_err): ?>
            <div class="cw-alert cw-alert--err">✕ <?= htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <?php if ($puede_comentar): ?>
            <div class="cw-form-wrap">
                <div class="cw-form-avatar">
                    <?php if (!empty($usuario['avatar'])): ?>
                        <img src="<?= htmlspecialchars($usuario['avatar'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="cw-avatar-img">
                    <?php else: ?>
                        <div class="cw-avatar-default"><?= Comments::getInitials($usuario['username']) ?></div>
                    <?php endif; ?>
                </div>

                <form class="cw-form" method="POST" action="<?= u('/' . $page_slug) ?>#comentarios">
                    <?= csrf_field() ?>

                    <div class="cw-rating-wrap">
                        <span class="cw-rating-label">Valoración (opcional):</span>
                        <div class="cw-stars" id="cw-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="cw-star" data-value="<?= $i ?>" title="<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="cw-rating-text" id="cw-rating-text"></span>
                        <input type="hidden" name="rating" id="cw-rating-input" value="0">
                    </div>

                    <div class="cw-textarea-wrap">
                        <textarea name="comentario_content" class="cw-textarea"
                            placeholder="Escribe tu comentario... (máx. 1000 caracteres)"
                            maxlength="1000" rows="4" required id="cw-textarea"></textarea>
                        <span class="cw-contador"><span id="cw-chars">0</span> / 1000</span>
                    </div>

                    <div class="cw-form-footer">
                        <span class="cw-form-user">
                            Comentando como <strong><?= htmlspecialchars($usuario['username'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (in_array($usuario['role'], ['admin', 'verified'])): ?>
                                <span class="cw-badge-role cw-badge-role--<?= $usuario['role'] ?>">
                                    <?= $usuario['role'] === 'admin' ? 'Admin' : '✓ Verificado' ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <button type="submit" class="cw-btn-enviar">Publicar comentario</button>
                    </div>
                </form>
            </div>

        <?php elseif ($logueado && $usuario['role'] === 'pending'): ?>
            <div class="cw-estado cw-estado--pending">
                <div class="cw-estado__icono">⏳</div>
                <div>
                    <p class="cw-estado__titulo">Cuenta pendiente de verificación</p>
                    <p class="cw-estado__desc">Tu cuenta está siendo revisada. Una vez aprobada podrás comentar.</p>
                </div>
            </div>

        <?php elseif ($logueado && $usuario['role'] === 'banned'): ?>
            <div class="cw-estado cw-estado--banned">
                <div class="cw-estado__icono">🚫</div>
                <div>
                    <p class="cw-estado__titulo">Cuenta suspendida</p>
                    <p class="cw-estado__desc">Tu cuenta ha sido suspendida. Contacta al administrador.</p>
                </div>
            </div>

        <?php else: ?>
            <div class="cw-estado cw-estado--guest">
                <div class="cw-estado__icono">🔐</div>
                <div>
                    <p class="cw-estado__titulo">¿Quieres dejar un comentario?</p>
                    <p class="cw-estado__desc">Inicia sesión o regístrate para participar.</p>
                    <div class="cw-estado__botones">
                        <a href="<?= u('/login') ?>" class="cw-btn-login">Iniciar sesión</a>
                        <a href="<?= u('/registrar') ?>" class="cw-btn-registro">Registrarse</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- LISTA DE COMENTARIOS -->
        <div class="cw-lista">
            <?php if ($total_comentarios === 0): ?>
                <div class="cw-vacio"><p>Sé el primero en comentar en esta página.</p></div>
            <?php else: ?>
                <?php foreach ($lista_comentarios as $c):
                    $likes    = $vote_counts[$c['id']]['likes']    ?? 0;
                    $dislikes = $vote_counts[$c['id']]['dislikes'] ?? 0;
                    $my_vote  = $user_votes[$c['id']]              ?? null;
                ?>
                    <div class="cw-item" id="cw-comment-<?= $c['id'] ?>">
                        <div class="cw-item__avatar">
                            <?php if (!empty($c['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars($c['avatar_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" class="cw-avatar-img">
                            <?php else: ?>
                                <div class="cw-avatar-default"><?= Comments::getInitials($c['username']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="cw-item__body">
                            <div class="cw-item__meta">
                                <span class="cw-item__username"><?= htmlspecialchars($c['username'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (in_array($c['user_role'], ['admin', 'verified'])): ?>
                                    <span class="cw-badge-role cw-badge-role--<?= $c['user_role'] ?>">
                                        <?= $c['user_role'] === 'admin' ? 'Admin' : '✓ Verificado' ?>
                                    </span>
                                <?php endif; ?>
                                <?php if (!empty($c['rating'])): ?>
                                    <div class="cw-stars-comment">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="cw-star-static <?= $i <= (int)$c['rating'] ? 'active' : '' ?>">★</span>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                                <span class="cw-item__fecha"><?= Comments::timeAgo($c['created_at']) ?></span>
                            </div>

                            <p class="cw-item__texto"><?= nl2br(htmlspecialchars($c['content'], ENT_QUOTES, 'UTF-8')) ?></p>

                            <div class="cw-votes" data-comment-id="<?= $c['id'] ?>">
                                <button class="cw-vote-btn cw-vote-btn--like <?= $my_vote === 'like' ? 'active' : '' ?>"
                                        data-type="like"
                                        <?= !$logueado ? 'title="Inicia sesión para votar" disabled' : '' ?>>
                                    👍 <span class="cw-vote-count"><?= $likes ?></span>
                                </button>
                                <button class="cw-vote-btn cw-vote-btn--dislike <?= $my_vote === 'dislike' ? 'active' : '' ?>"
                                        data-type="dislike"
                                        <?= !$logueado ? 'title="Inicia sesión para votar" disabled' : '' ?>>
                                    👎 <span class="cw-vote-count"><?= $dislikes ?></span>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</section>

<style>
.cw-seccion{padding:var(--tbt-s5) 0;border-top:1px solid var(--tbt-bg-4);margin-top:var(--tbt-s5)}
.cw-inner{max-width:var(--tbt-max-w);margin:0 auto;padding:0 var(--tbt-s3)}
.cw-header{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:var(--tbt-s2);margin-bottom:var(--tbt-s4)}
.cw-titulo{font-family:var(--tbt-font-display);font-size:var(--tbt-text-2xl);font-weight:700;color:var(--tbt-txt-white);display:flex;align-items:center;gap:var(--tbt-s1);margin-bottom:.4rem}
.cw-titulo__icono{font-size:1.4rem}
.cw-badge{background:var(--tbt-jade-15);color:var(--tbt-jade-light);border:1px solid var(--tbt-jade-30);font-size:var(--tbt-text-xs);font-weight:600;padding:2px 10px;border-radius:var(--tbt-r-full);font-family:var(--tbt-font-mono)}
.cw-subtitulo{font-size:var(--tbt-text-sm);color:var(--tbt-txt-sub)}
.cw-avg-rating{display:flex;align-items:center;gap:.5rem;background:var(--tbt-bg-1);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);padding:.6rem var(--tbt-s2)}
.cw-stars-display,.cw-stars-comment{display:flex;gap:2px}
.cw-star-static{font-size:1rem;color:var(--tbt-bg-5);line-height:1}
.cw-star-static.active{color:var(--tbt-amber)}
.cw-avg-num{font-size:var(--tbt-text-lg);font-weight:700;color:var(--tbt-txt-white);font-family:var(--tbt-font-mono)}
.cw-avg-label{font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted)}
.cw-alert{display:flex;align-items:center;gap:var(--tbt-s1);padding:var(--tbt-s2) var(--tbt-s3);border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:500;margin-bottom:var(--tbt-s3)}
.cw-alert--ok{background:rgba(34,197,94,.08);color:#4ade80;border:1px solid rgba(34,197,94,.2)}
.cw-alert--err{background:rgba(239,68,68,.08);color:#f87171;border:1px solid rgba(239,68,68,.2)}
.cw-form-wrap{display:flex;gap:var(--tbt-s2);margin-bottom:var(--tbt-s4);background:var(--tbt-bg-1);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);padding:var(--tbt-s3)}
.cw-form{flex:1;display:flex;flex-direction:column;gap:var(--tbt-s2)}
.cw-rating-wrap{display:flex;align-items:center;gap:var(--tbt-s1);flex-wrap:wrap}
.cw-rating-label{font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted)}
.cw-stars{display:flex;gap:2px;cursor:pointer}
.cw-star{font-size:1.4rem;color:var(--tbt-bg-5);transition:color var(--tbt-t1),transform var(--tbt-t1);line-height:1;user-select:none}
.cw-star:hover,.cw-star.hovered,.cw-star.selected{color:var(--tbt-amber);transform:scale(1.15)}
.cw-rating-text{font-size:var(--tbt-text-xs);color:var(--tbt-amber);font-weight:600;min-width:60px}
.cw-textarea-wrap{position:relative}
.cw-textarea{width:100%;background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-5);border-radius:var(--tbt-r-md);color:var(--tbt-txt-white);font-family:var(--tbt-font-body);font-size:var(--tbt-text-sm);padding:var(--tbt-s2);resize:vertical;min-height:90px;transition:border-color var(--tbt-t1) var(--tbt-ease);outline:none}
.cw-textarea:focus{border-color:var(--tbt-jade-40);box-shadow:0 0 0 3px var(--tbt-jade-08)}
.cw-textarea::placeholder{color:var(--tbt-txt-muted)}
.cw-contador{position:absolute;bottom:8px;right:12px;font-size:var(--tbt-text-2xs);font-family:var(--tbt-font-mono);color:var(--tbt-txt-muted);pointer-events:none}
.cw-form-footer{display:flex;align-items:center;justify-content:space-between;gap:var(--tbt-s2);flex-wrap:wrap}
.cw-form-user{font-size:var(--tbt-text-xs);color:var(--tbt-txt-sub)}
.cw-form-user strong{color:var(--tbt-txt-light)}
.cw-btn-enviar{background:var(--tbt-jade);color:#fff;border:none;border-radius:var(--tbt-r-md);font-family:var(--tbt-font-display);font-size:var(--tbt-text-sm);font-weight:600;padding:.55rem 1.4rem;cursor:pointer;transition:opacity var(--tbt-t1),transform var(--tbt-t1)}
.cw-btn-enviar:hover{opacity:.85;transform:translateY(-1px)}
.cw-avatar-img{width:40px;height:40px;border-radius:50%;object-fit:cover;border:2px solid var(--tbt-bg-4);flex-shrink:0}
.cw-avatar-default{width:40px;height:40px;border-radius:50%;background:var(--tbt-jade-15);border:2px solid var(--tbt-jade-30);display:flex;align-items:center;justify-content:center;font-size:var(--tbt-text-xs);font-weight:700;color:var(--tbt-jade-light);font-family:var(--tbt-font-mono);flex-shrink:0}
.cw-badge-role{font-size:var(--tbt-text-2xs);font-weight:700;font-family:var(--tbt-font-mono);padding:2px 7px;border-radius:var(--tbt-r-full);text-transform:uppercase;letter-spacing:.05em}
.cw-badge-role--admin{background:var(--tbt-amber-15);color:var(--tbt-amber);border:1px solid var(--tbt-amber-30)}
.cw-badge-role--verified{background:rgba(59,130,246,.1);color:#60a5fa;border:1px solid rgba(59,130,246,.25)}
.cw-estado{display:flex;align-items:flex-start;gap:var(--tbt-s2);padding:var(--tbt-s3);border-radius:var(--tbt-r-md);margin-bottom:var(--tbt-s4);border:1px solid}
.cw-estado--pending{background:var(--tbt-jade-08);border-color:var(--tbt-jade-30)}
.cw-estado--banned{background:rgba(239,68,68,.06);border-color:rgba(239,68,68,.2)}
.cw-estado--guest{background:var(--tbt-bg-1);border-color:var(--tbt-bg-4)}
.cw-estado__icono{font-size:1.6rem;line-height:1;flex-shrink:0}
.cw-estado__titulo{font-size:var(--tbt-text-md);font-weight:600;color:var(--tbt-txt-white);margin-bottom:.3rem}
.cw-estado__desc{font-size:var(--tbt-text-sm);color:var(--tbt-txt-sub)}
.cw-estado__botones{display:flex;gap:var(--tbt-s1);margin-top:var(--tbt-s2);flex-wrap:wrap}
.cw-btn-login{background:var(--tbt-jade);color:#fff;text-decoration:none;padding:.45rem 1.2rem;border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:600;transition:opacity var(--tbt-t1)}
.cw-btn-login:hover{opacity:.85}
.cw-btn-registro{background:transparent;color:var(--tbt-txt-light);text-decoration:none;padding:.45rem 1.2rem;border-radius:var(--tbt-r-md);font-size:var(--tbt-text-sm);font-weight:600;border:1px solid var(--tbt-bg-5);transition:border-color var(--tbt-t1),color var(--tbt-t1)}
.cw-btn-registro:hover{border-color:var(--tbt-jade-40);color:var(--tbt-jade-light)}
.cw-lista{display:flex;flex-direction:column;gap:var(--tbt-s2)}
.cw-vacio{text-align:center;padding:var(--tbt-s4);color:var(--tbt-txt-muted);font-size:var(--tbt-text-sm);background:var(--tbt-bg-1);border:1px dashed var(--tbt-bg-4);border-radius:var(--tbt-r-md)}
.cw-item{display:flex;gap:var(--tbt-s2);padding:var(--tbt-s3);background:var(--tbt-bg-1);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-md);transition:border-color var(--tbt-t1)}
.cw-item:hover{border-color:var(--tbt-bg-5)}
.cw-item__body{flex:1;min-width:0}
.cw-item__meta{display:flex;align-items:center;gap:var(--tbt-s1);flex-wrap:wrap;margin-bottom:.5rem}
.cw-item__username{font-size:var(--tbt-text-sm);font-weight:700;color:var(--tbt-txt-white)}
.cw-item__fecha{font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);font-family:var(--tbt-font-mono);margin-left:auto}
.cw-item__texto{font-size:var(--tbt-text-sm);color:var(--tbt-txt-base);line-height:1.65;word-break:break-word;margin-bottom:var(--tbt-s1)}
.cw-votes{display:flex;gap:var(--tbt-s1);align-items:center;margin-top:var(--tbt-s1)}
.cw-vote-btn{display:inline-flex;align-items:center;gap:5px;background:var(--tbt-bg-2);border:1px solid var(--tbt-bg-4);border-radius:var(--tbt-r-full);padding:.25rem .75rem;font-size:var(--tbt-text-xs);color:var(--tbt-txt-muted);cursor:pointer;transition:all var(--tbt-t1);font-family:var(--tbt-font-mono)}
.cw-vote-btn:hover:not(:disabled){border-color:var(--tbt-bg-5);color:var(--tbt-txt-white)}
.cw-vote-btn--like.active{background:rgba(34,197,94,.1);border-color:rgba(34,197,94,.3);color:#4ade80}
.cw-vote-btn--dislike.active{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.25);color:#f87171}
.cw-vote-btn:disabled{cursor:not-allowed;opacity:.5}
.cw-vote-count{font-weight:600}
@media(max-width:640px){.cw-form-wrap{flex-direction:column}.cw-form-footer{flex-direction:column;align-items:flex-start}.cw-btn-enviar{width:100%;text-align:center}.cw-header{flex-direction:column}}
</style>

<script>
(function(){
    const ta=document.getElementById('cw-textarea');
    const ch=document.getElementById('cw-chars');
    if(!ta||!ch)return;
    ta.addEventListener('input',function(){
        const len=this.value.length;
        ch.textContent=len;
        ch.style.color=len>900?'var(--tbt-amber)':len>=1000?'#f87171':'';
    });
})();

(function(){
    const stars=document.querySelectorAll('#cw-stars .cw-star');
    const input=document.getElementById('cw-rating-input');
    const text=document.getElementById('cw-rating-text');
    if(!stars.length)return;
    const labels=['','Muy malo','Malo','Regular','Bueno','Excelente'];
    stars.forEach(star=>{
        star.addEventListener('mouseenter',function(){
            const val=+this.dataset.value;
            stars.forEach(s=>s.classList.toggle('hovered',+s.dataset.value<=val));
            text.textContent=labels[val]||'';
        });
        star.addEventListener('mouseleave',function(){
            const sel=+(input.value||0);
            stars.forEach(s=>{s.classList.remove('hovered');s.classList.toggle('selected',+s.dataset.value<=sel);});
            text.textContent=labels[sel]||'';
        });
        star.addEventListener('click',function(){
            const val=+this.dataset.value;
            const prev=+input.value;
            const newVal=prev===val?0:val;
            input.value=newVal;
            stars.forEach(s=>s.classList.toggle('selected',+s.dataset.value<=newVal));
            text.textContent=labels[newVal]||'';
        });
    });
})();

(function(){
    const csrfInput=document.querySelector('input[name="csrf_token"]');
    if(!csrfInput)return;
    const csrf=csrfInput.value;
    document.querySelectorAll('.cw-votes').forEach(votesEl=>{
        const commentId=votesEl.dataset.commentId;
        votesEl.querySelectorAll('.cw-vote-btn').forEach(btn=>{
            btn.addEventListener('click',async function(){
                if(this.disabled)return;
                const type=this.dataset.type;
                try{
                    const res=await fetch(window.location.pathname+'#comentarios',{
                        method:'POST',
                        headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:new URLSearchParams({vote_comment_id:commentId,vote_type:type,csrf_token:csrf})
                    });
                    const data=await res.json();
                    if(!data.success){console.error(data.error);return;}
                    const likeBtn=votesEl.querySelector('.cw-vote-btn--like');
                    const dislikeBtn=votesEl.querySelector('.cw-vote-btn--dislike');
                    likeBtn.querySelector('.cw-vote-count').textContent=data.likes;
                    dislikeBtn.querySelector('.cw-vote-count').textContent=data.dislikes;
                    likeBtn.classList.remove('active');
                    dislikeBtn.classList.remove('active');
                    if(data.action!=='removed'){
                        if(type==='like')likeBtn.classList.add('active');
                        else dislikeBtn.classList.add('active');
                    }
                }catch(e){console.error('Error al votar:',e);}
            });
        });
    });
})();
</script>
