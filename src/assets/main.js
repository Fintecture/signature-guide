window.addEventListener('load', (event) => {
    document.getElementById('informationForm').reset();

    var methodSelect = document.getElementById('method-select');
    var requestTarget = document.getElementById('request-target');
    var payload = document.getElementById('payload');

    methodSelect.addEventListener('change', (e) => {
        if (methodSelect.value == 1) {
            requestTarget.value = requestTarget.value.replace(/post/gi, 'get');
            payload.style.display = 'none';
        } else {
            requestTarget.value = requestTarget.value.replace(/get/gi, 'post');
            payload.style.display = 'block';
        }
    });
});
