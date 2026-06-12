
(function () {
    'use strict';

    function getActiveWA() {
        var active = document.querySelector('.gem-vendedor.active');
        return active ? active.dataset.wa : '';
    }

    function updateLinks() {
        var currentWA = getActiveWA();
        if (!currentWA) return;

        document.querySelectorAll('.bf-contratar').forEach(function (a) {
            var text = encodeURIComponent(a.dataset.waText || '');
            a.href = 'https://api.whatsapp.com/send?phone=' + currentWA + '&text=' + text;
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateLinks();

        document.querySelectorAll('.gem-vendedor').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.gem-vendedor').forEach(function (b) {
                    b.classList.remove('active');
                });
                btn.classList.add('active');

                updateLinks();
            });
        });
    });
})();