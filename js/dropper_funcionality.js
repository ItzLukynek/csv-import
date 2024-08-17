document.addEventListener('DOMContentLoaded', () => {
    const dropArea = document.getElementById('drop-area');

// prevent default
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, () => {
        dropArea.classList.add('dragging');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, () => {
        dropArea.classList.remove('dragging');
    }, false);
});

// handle dropped files
dropArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const data = e.dataTransfer;
    const files = data.files;
    handleFiles(files);
}
})

function handleFiles(files) {
    const file = files[0];
    if (file.type === 'text/csv') {
        uploadFile(file);
    } else {
        alert('Prosím nahrajte platné CSV');
    }
}
//upload file
function uploadFile(file) {
    const url = "controllers/process_data.php";
    const formData = new FormData();
    formData.append('file', file);
    document.querySelector("#loader").classList.remove("d-none")
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert('Zvalidovaná data byla uložena do databáze');  
        document.querySelector("#loader").classList.add("d-none")
    })
    .catch(error => {
        alert('Při ukládání dat došlo k chybě');
        console.error(error);
    });
}
