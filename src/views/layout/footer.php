<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');
    if(!empty($js)){
        foreach($js as $item){
            echo '<script src="'. _JS .$item.'.js?v='.time().'"></script>';
        }
    }
?>

<script>
(function(){
    var toasts = document.querySelectorAll('.toast');
    if (!toasts.length) return;
    toasts.forEach(function(t){
        if (t.parentNode) document.body.appendChild(t);
        var bar = t.querySelector('.toast-bar');
        var duration = 4000;
        var startTime = performance.now();
        var rafId = null;
        function animate(now) {
            var elapsed = now - startTime;
            var pct = Math.max(0, 1 - elapsed / duration);
            if (bar) bar.style.width = (pct * 100) + '%';
            if (pct > 0) {
                rafId = requestAnimationFrame(animate);
            }
        }
        rafId = requestAnimationFrame(animate);
        var timer = setTimeout(function(){
            if (rafId) cancelAnimationFrame(rafId);
            if (t.parentNode) {
                t.classList.add('removing');
                setTimeout(function(){ if (t.parentNode) t.remove() }, 300);
            }
        }, duration);
        var closeBtn = t.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function(){
                if (rafId) cancelAnimationFrame(rafId);
                clearTimeout(timer);
                t.classList.add('removing');
                setTimeout(function(){ if (t.parentNode) t.remove() }, 300);
            });
        }
    });
})();
</script>

</body>
</html> 


