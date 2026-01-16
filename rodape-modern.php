<?php
// Footer component for DocMark Modern UI
?>
<footer class="footer">
    <div class="footer-content">
        <p class="footer-text">
            &copy; <span id="year"></span> <strong>DocMark</strong> | Desenvolvido por 
            <a href="https://backupcloud.site/" target="_blank" class="footer-link">Backup Cloud</a>. 
            Todos os direitos reservados.
        </p>
    </div>
</footer>

<script>
    // Set current year
    document.getElementById("year").textContent = new Date().getFullYear();
</script>
