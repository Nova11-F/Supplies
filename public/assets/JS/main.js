function openModal(modalId) {
    const modalWrapper = document.getElementById(modalId);
    
    if (!modalWrapper) {
        console.error(`Modal dengan ID '${modalId}' tidak ditemukan!`);
        return;
    }

    const backdrop = modalWrapper.querySelector('.modal-backdrop');
    const content = modalWrapper.querySelector('.modal-content-box');

    modalWrapper.style.display = 'block';
    
    void modalWrapper.offsetWidth; 

    requestAnimationFrame(() => {
        if(backdrop) backdrop.style.opacity = '1';
        if(content) {
            content.style.transform = 'scale(1)';
            content.style.opacity = '1';
        }
    });
    document.body.classList.add('modal-open');
}


function closeModal(modalId) {
    const modalWrapper = document.getElementById(modalId);
    if (!modalWrapper) return;

    const backdrop = modalWrapper.querySelector('.modal-backdrop');
    const content = modalWrapper.querySelector('.modal-content-box');

    // animasi keluar
    if(backdrop) backdrop.style.opacity = '0';
    if(content) {
        content.style.transform = 'scale(0.9)';
        content.style.opacity = '0';
    }

    // tunggu animasi selesai (500ms sesuai CSS), baru sembunyikan div
    setTimeout(() => {
        modalWrapper.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // reset Form otomatis jika ada form di dalamnya
        const form = modalWrapper.querySelector('form');
        if (form) form.reset();

    }, 500);
}

// menutup modal jika ESC ditekan
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        const visibleModal = document.querySelector('.custom-modal[style*="display: block"]');
        if (visibleModal) {
            closeModal(visibleModal.id);
        }
    }
});


function updateFileName(input) {
    const wrapper = input.closest('.group'); 
    const display = wrapper.querySelector('.file-name-display');
    
    if (input.files && input.files.length > 0) {
        const fileName = input.files[0].name;
        display.innerHTML = `<span class="text-[#092363] font-bold">${fileName}</span>`;
    } else {
        display.innerHTML = "Klik untuk upload gambar";
    }
}

function toggleNotification() {
    const dropdown = document.getElementById('notificationDropdown');
    
    if (!dropdown) {
        console.error("Dropdown not found!"); 
        return;
    }

    if (dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('hidden');
        setTimeout(() => {
            dropdown.classList.remove('scale-95', 'opacity-0');
            dropdown.classList.add('scale-100', 'opacity-100');
        }, 10);
    } else {
        dropdown.classList.remove('scale-100', 'opacity-100');
        dropdown.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 200);
    }
}

window.addEventListener('click', function(e) {
    const dropdown = document.getElementById('notificationDropdown');
    const button = document.getElementById('notifButton');
    
    if (dropdown && button) {
        if (!dropdown.contains(e.target) && !button.contains(e.target)) {
            if (!dropdown.classList.contains('hidden')) {
                dropdown.classList.remove('scale-100', 'opacity-100');
                dropdown.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    dropdown.classList.add('hidden');
                }, 200);
            }
        }
    }
});

function openEditModal(modalId, button) {
    const data = button.dataset;
    for (const key in data) {
        const input = document.getElementById('edit_' + key);
        if (input) {
            if (input.tagName === 'SELECT') {
                input.value = data[key]; // pastikan option value cocok
            } else {
                input.value = data[key];
            }
        }
    }
    openModal(modalId);
}

function openViewModal(modalId, button) {
    const data = button.dataset;
    for (const key in data) {
        const input = document.getElementById('view_' + key);
        
        if (input) {
            input.innerText = data[key];
        }
    }

    openModal(modalId);
}

