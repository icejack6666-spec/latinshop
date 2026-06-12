
(function() {


    const PRECIOS_PODER = [
        [  789, 3.70],
        [ 1089, 3.80],
        [ 1389, 3.95],
        [ 2489, 4.10],
        [ 2990, 4.30],
    ];

    function getPrecioUsd(poderM) {
    for (const [maxM, precio] of PRECIOS_PODER) {
        if (poderM <= maxM) return precio;
    }
    return null;
}

function calcularUsd(total, precioBase) {
    if (total >= 1000000) {
        return (total / 100000 * (precioBase - 0.10)).toFixed(2);
    }
    return (total / 100000 * precioBase).toFixed(2);
}

    let currentWA = document.querySelector('.gem-vendedor.active')?.dataset.wa || '';

    document.querySelectorAll('.gem-vendedor').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.gem-vendedor').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentWA = btn.dataset.wa;
            document.getElementById('wa-vendedor-nombre').textContent = btn.dataset.nombre;
            buildWALink();
        });
    });

    function getReinoMax() {
        const now = new Date();
        const apertura1848 = new Date(Date.UTC(2026, 1, 21, 4, 15, 0));
        if (now < apertura1848) return 1846;
        const ms3dias = 3 * 24 * 60 * 60 * 1000;
        const periodos = Math.floor((now - apertura1848) / ms3dias);
        return 1848 + periodos * 2;
    }

    function formatPoder(val) {
        if (!val || val <= 0) return { unit: 'M', preview: '', millonesVal: 0 };
        if (val < 1000) {
            return { unit: 'M', preview: val + 'M', millonesVal: val };
        } else {
            const b = (val / 1000).toFixed(2).replace(/\.?0+$/, '');
            return { unit: 'B', preview: b + 'B', millonesVal: val };
        }
    }

    const inputPoder   = document.getElementById('input-poder');
    const poderUnit    = document.getElementById('poder-unit');
    const poderPreview = document.getElementById('poder-preview');

    inputPoder.addEventListener('input', () => {
        const val = parseInt(inputPoder.value) || 0;
        const f = formatPoder(val);
        poderUnit.textContent    = f.unit;
        poderPreview.textContent = f.preview ? '→ ' + f.preview : '';
        updateUsdBlock();
        buildWALink();
    });

    const inputReino  = document.getElementById('input-reino');
    const reinoStatus = document.getElementById('reino-status');
    const reinoInfo   = document.getElementById('reino-max-info');

    function updateReinoStatus() {
        const reino    = parseInt(inputReino.value) || 0;
        const maxReino = getReinoMax();
        reinoInfo.textContent = 'Reino máximo actual: ' + maxReino;
        if (!reino) { reinoStatus.style.display = 'none'; return; }
        reinoStatus.style.display = 'flex';
        if (reino <= maxReino) {
            reinoStatus.className = 'gem-reino-status ok';
            reinoStatus.innerHTML = '✓ Reino ' + reino + ' — entrega disponible';
        } else if (reino === maxReino + 1 || reino === maxReino + 2) {
            reinoStatus.className = 'gem-reino-status soon';
            reinoStatus.innerHTML = '⏳ Reino ' + reino + ' — abre muy pronto';
        } else {
            reinoStatus.className = 'gem-reino-status warn';
            reinoStatus.innerHTML = '✗ Reino ' + reino + ' — aún no disponible (máx. ' + maxReino + ')';
        }
        buildWALink();
    }

    inputReino.addEventListener('input', updateReinoStatus);
    updateReinoStatus();

    let wishlist = {};

    document.querySelectorAll('.gem-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.gem-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.gem-table-wrap').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
        });
    });

    document.querySelectorAll('.gem-qty').forEach(input => {
        input.addEventListener('input', () => {
            const name = input.dataset.name;
            const cost = parseInt(input.dataset.cost);
            const qty  = parseInt(input.value) || 0;
            if (qty > 0) wishlist[name] = { cost, qty };
            else         delete wishlist[name];
            renderWishlist();
        });
    });

    document.getElementById('btn-clear').addEventListener('click', () => {
        wishlist = {};
        document.querySelectorAll('.gem-qty').forEach(i => i.value = 0);
        inputPoder.value = '';
        inputReino.value = '';
        poderUnit.textContent    = 'M';
        poderPreview.textContent = '';
        document.getElementById('gem-usd-block').style.display = 'none';
        document.getElementById('gem-total').classList.remove('has-usd');
        updateReinoStatus();
        renderWishlist();
    });

    function updateUsdBlock() {
        const usdBlock  = document.getElementById('gem-usd-block');
        const usdVal    = document.getElementById('gem-usd-val');
        const usdNote   = document.getElementById('gem-usd-note');
        const totalEl   = document.getElementById('gem-total');

        const items    = Object.entries(wishlist);
        const total    = items.reduce((sum, [, v]) => sum + v.cost * v.qty, 0);
        const poderRaw = parseInt(inputPoder.value) || 0;
        const f        = formatPoder(poderRaw);

        if (total === 0) {
            usdBlock.style.display = 'none';
            totalEl.classList.remove('has-usd');
            return;
        }

        usdBlock.style.display = 'block';
        totalEl.classList.add('has-usd');

        if (poderRaw <= 0) {
            usdVal.textContent  = 'Ingresa tu poder';
            usdNote.textContent = 'Pon tu poder arriba para ver el precio en USD';
            return;
        }

        const precio = getPrecioUsd(f.millonesVal);

        if (precio !== null) {
            const usd = calcularUsd(total, precio);
            usdVal.textContent  = '~$' + usd + ' USD';
            const precioEfectivo = total >= 1000000 ? (precio - 0.10) : precio;
            usdNote.textContent = '$' + precioEfectivo.toFixed(2) + ' por cada 100,000 💎 · poder ' + f.preview
               + (total >= 1000000 ? ' 🎉 Promo +1M aplicada' : '');
        } else {
            usdVal.textContent  = 'Fuera de rango';
            usdNote.textContent = 'El rango de precio cubre hasta 2.99B de poder';
        }
    }

    function renderWishlist() {
        const body     = document.getElementById('wishlist-body');
        const table    = document.getElementById('wishlist-table');
        const empty    = document.getElementById('wishlist-empty');
        const btnClear = document.getElementById('btn-clear');
        const totalEl  = document.getElementById('total-display');

        const items = Object.entries(wishlist);
        const total = items.reduce((sum, [, v]) => sum + v.cost * v.qty, 0);
        totalEl.textContent = total.toLocaleString('es-MX');

        if (items.length === 0) {
            table.style.display    = 'none';
            empty.style.display    = 'block';
            btnClear.style.display = 'none';
            document.getElementById('gem-usd-block').style.display = 'none';
            document.getElementById('gem-total').classList.remove('has-usd');
        } else {
            table.style.display    = '';
            empty.style.display    = 'none';
            btnClear.style.display = '';
            body.innerHTML = items.map(([name, v]) => `
                <tr>
                    <td class="wl-name" title="${name}">${name}</td>
                    <td>${v.qty}</td>
                    <td>${(v.cost * v.qty).toLocaleString('es-MX')}</td>
                </tr>
            `).join('');
            updateUsdBlock();
        }
        buildWALink();
    }

    function buildWALink() {
        const items          = Object.entries(wishlist);
        const total          = items.reduce((sum, [, v]) => sum + v.cost * v.qty, 0);
        const poder          = parseInt(inputPoder.value) || 0;
        const reino          = parseInt(inputReino.value) || 0;
        const pFmt           = formatPoder(poder);
        const vendedorNombre = document.querySelector('.gem-vendedor.active')?.dataset.nombre || 'Eliseo';

        let msg = '💎 *Solicitud de Gemas — Latin Shop*\n';
        msg += '🧑‍💼 Vendedor: *' + vendedorNombre + '*\n';
        if (poder > 0) msg += '⚔️ Mi poder: *' + pFmt.preview + '*\n';
        if (reino  > 0) msg += '🏰 Mi reino: *' + reino + '*\n';

        if (items.length > 0) {
            msg += '\n*Lista de ítems:*\n';
            items.forEach(([name, v]) => {
                msg += `• ${name} x${v.qty} = ${(v.cost * v.qty).toLocaleString('es-MX')} gemas\n`;
            });
            msg += `\n*TOTAL: ${total.toLocaleString('es-MX')} gemas*`;

            const precio = getPrecioUsd(pFmt.millonesVal);
            if (poder > 0 && precio !== null) {
                const usd = calcularUsd(total, precio);
                msg += `\n💵 Precio estimado: ~$${usd} USD`;
            }
        }

        document.getElementById('btn-whatsapp').href =
            'https://api.whatsapp.com/send?phone=' + currentWA +
            '&text=' + encodeURIComponent(msg);
    }

    buildWALink();
})();