</main>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButton = document.getElementById('toggle-theme');
        const themeStylesheet = document.getElementById('theme-stylesheet');
        // Verificar tema guardado en localStorage
        const currentTheme = localStorage.getItem('theme') || 'light';
        applyTheme(currentTheme);
        // Configurar el botón
        toggleButton.addEventListener('click', () => {
            const newTheme = themeStylesheet.getAttribute('href').includes('black') ? 'light' : 'dark';
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme);
        });
        function applyTheme(theme) {
            const newHref = theme === 'dark' 
                ? '../../assets/css/style_black.css' 
                : '../../assets/css/style.css';
            
            themeStylesheet.setAttribute('href', newHref);
            toggleButton.textContent = theme === 'dark' 
                ? 'Modo Claro' 
                : 'Modo Oscuro';
        }
    });
</script>
    <script src="../../assets/js/toaster.js"></script>
    <footer class="footer">
        <p>&copy; <?= date('Y'); ?> Cuxco - Electro Hogar</p>
        <p>By TSR</p>
    </footer>
    <?php if (isset($toast)) echo $toast; ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.toastMsg) {
            toastr(window.toastMsg.type, window.toastMsg.message);
        }
    });
    </script>
</body>
</html>
