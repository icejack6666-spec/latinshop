(function () {
    document.addEventListener('contextmenu', function (e) {
        e.preventDefault();
        return false;
    });
    document.addEventListener('keydown', function (e) {
        if (e.keyCode === 123) { e.preventDefault(); return false; }                          
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) { e.preventDefault(); return false; } 
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) { e.preventDefault(); return false; } 
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) { e.preventDefault(); return false; } 
        if (e.ctrlKey && e.keyCode === 85) { e.preventDefault(); return false; }               
        if (e.ctrlKey && e.keyCode === 83) { e.preventDefault(); return false; }               
        if (e.ctrlKey && e.keyCode === 65) { e.preventDefault(); return false; }               
    });
    document.addEventListener('selectstart', function (e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
    document.addEventListener('dragstart', function (e) {
        if (e.target.tagName === 'IMG') {
            e.preventDefault();
            return false;
        }
    });
})();
