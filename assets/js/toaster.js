function toastr(type, message) {
    const div = document.createElement('div');
    div.className = `toast toast-${type}`;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 3000);
}

// Ejemplo: para llamar desde PHP:
// echo "<script>toastr('success', 'Guardado!');</script>";
