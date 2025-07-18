/**
 * Displays a SweetAlert2 notification.
 * @param {string} type - 'success' or 'error'
 * @param {string} message - The message to display.
 * @param {number} [timer=1500] - Duration the alert is shown (in ms).
 */
function showNotification(type, message, timer = 1500) {
    Swal.fire({
        icon: type === 'success' ? 'success' : 'error',
        title: type === 'success' ? 'Berhasil!' : 'Gagal!',
        text: message,
        timer: timer,
        showConfirmButton: false
    });
}

/**
 * Confirms completion of a checker order.
 * @param {Event} event - The event object.
 */
function confirmCheckerComplete(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Selesaikan Order?',
        text: 'Pastikan semua data sudah benar!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Selesaikan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (event.target && typeof event.target.submit === 'function') {
                event.target.submit();
            } else if (event.target.form && typeof event.target.form.submit === 'function') {
                event.target.form.submit();
            } else {
                console.error('Cannot submit form from confirmCheckerComplete: Target is not a form or inside a form.');
            }
        }
    });
    return false;
}

/**
 * Confirms resetting item list.
 * @param {Event} event - The event object.
 */
function confirmResetItems(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Reset Daftar Item?',
        text: 'Daftar item akan dikembalikan ke kondisi awal. Aksi ini tidak dapat dibatalkan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (event.target && typeof event.target.submit === 'function') {
                event.target.submit();
            } else if (event.target.form && typeof event.target.form.submit === 'function') {
                event.target.form.submit();
            } else {
                console.error('Cannot submit form from confirmResetItems: Target is not a form or inside a form.');
            }
        }
    });
    return false;
}

/**
 * Confirms editing data.
 * @param {Event} event - The event object.
 * @param {string} url - The URL to redirect to for editing.
 */
function confirmEdit(event, url) {
    event.preventDefault();
    Swal.fire({
        title: 'Edit Data?',
        text: 'Apakah Anda yakin ingin mengedit data ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Edit!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

/**
 * Confirms deleting data via a form.
 * @param {Event} event - The event object.
 * @param {HTMLFormElement} form - The form to submit for deletion.
 */
function confirmDelete(event, form) {
    event.preventDefault();
    Swal.fire({
        title: 'Hapus Data?',
        text: 'Data yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (form && typeof form.submit === 'function') {
                form.submit();
            } else {
                console.error('Cannot submit form from confirmDelete: Invalid form provided.');
            }
        }
    });
}

/**
 * Displays an address in a modal.
 * @param {string} address - The address to display.
 */
function showAddressModal(address) {
    const modalElement = document.getElementById('addressModal');
    const fullAddressContent = document.getElementById('fullAddressContent');

    if (!modalElement || !fullAddressContent) {
        console.error('Address modal elements not found.');
        showNotification('danger', 'Gagal menampilkan detail alamat. Elemen modal tidak ditemukan.');
        return;
    }
    fullAddressContent.textContent = address;

    const modal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });
    modal.show();
}

/**
 * Copies the address from the address modal to the clipboard.
 */
function copyAddress() {
    const addressTextElement = document.getElementById('fullAddressContent');
    if (!addressTextElement) {
        showNotification('error', 'Elemen alamat tidak ditemukan untuk disalin.');
        return;
    }
    const addressText = addressTextElement.textContent;

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(addressText).then(() => {
            showNotification('success', 'Alamat berhasil disalin ke clipboard!');
        }).catch(err => {
            console.error('Error copying address: ', err);
            legacyCopyAddress(addressText);
        });
    } else {
        legacyCopyAddress(addressText);
    }
}

/**
 * Fallback method for copying text to clipboard using execCommand.
 * @param {string} text - The text to copy.
 */
function legacyCopyAddress(text) {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.left = "-9999px";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand('copy');
        showNotification('success', 'Alamat berhasil disalin ke clipboard! (Fallback)');
    } catch (err) {
        console.error('Fallback copy failed: ', err);
        showNotification('error', 'Gagal menyalin alamat. Silakan coba salin manual.');
    }
    document.body.removeChild(textArea);
}


/**
 * Formats a number as Indonesian Rupiah currency.
 * @param {number|string} amount - The amount to format.
 * @returns {string} The formatted currency string.
 */
function formatCurrency(amount) {
    const numericAmount = parseFloat(String(amount).replace(/[^0-9,-]+/g, "").replace(",", "."));
    if (isNaN(numericAmount)) {
        return 'Rp 0';
    }
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(numericAmount).replace(/\s?Rp/g, 'Rp ');
}

/**
 * Confirms clearing all activity logs.
 * @param {Event} event - The event object.
 * @param {HTMLFormElement} form - The form to submit for clearing logs.
 */
function confirmClearLogs(event, form) {
    event.preventDefault();
    Swal.fire({
        title: 'Hapus Semua Log?',
        text: 'Semua log aktivitas akan dihapus dan tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            if (form && typeof form.submit === 'function') {
                form.submit();
            } else {
                console.error('Cannot submit form from confirmClearLogs: Invalid form provided.');
            }
        }
    });
    return false;
}

/**
 * Displays order items in a modal.
 * @param {string|number} orderId - The ID of the order.
 */
function showItems(orderId) {
    const modalElement = document.getElementById('itemsModal');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const itemsTableElement = document.getElementById('itemsTable');
    const itemsList = document.getElementById('itemsList');
    const itemsFooter = document.getElementById('itemsFooter');
    const modalTitle = modalElement ? modalElement.querySelector('.modal-title') : null;

    if (!modalElement || !loadingIndicator || !itemsTableElement || !itemsList || !itemsFooter || !modalTitle) {
        console.error('One or more elements for itemsModal are missing.');
        showNotification('danger', 'Gagal menampilkan detail item. Elemen modal tidak lengkap.');
        return;
    }

    const modal = new bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
    });

    modalTitle.textContent = '📦 Detail Items';
    loadingIndicator.style.display = 'block';
    itemsTableElement.style.display = 'none';
    itemsList.innerHTML = '';
    itemsFooter.innerHTML = '';

    fetch(`get_order_items.php?order_id=${orderId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Network response was not ok (${response.status} ${response.statusText})`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && Array.isArray(data.items)) {
                let allRowsHTML = '';

                if (data.items.length > 0) {
                    data.items.forEach(item => {
                        const skuDisplay = item.kode_barang || '-';
                        const itemName = item.nama_barang || '-';
                        const itemQtyVal = parseInt(item.qty) || 0;
                        const itemPriceVal = parseFloat(item.harga_jual) || 0;
                        const itemDiskonVal = parseFloat(item.diskon_item) || 0;
                        const itemSubtotalVal = parseFloat(item.sub_total) || 0;

                        allRowsHTML += `
                            <tr>
                                <td>${skuDisplay}</td>
                                <td>${itemName}</td>
                                <td class="text-end">${itemQtyVal}</td>
                                <td class="text-end">${formatCurrency(itemPriceVal)}</td>
                                <td class="text-end">${formatCurrency(itemDiskonVal)}</td>
                                <td class="text-end">${formatCurrency(itemSubtotalVal)}</td>
                            </tr>
                        `;
                    });
                    itemsList.innerHTML = allRowsHTML;

                    // Menggunakan total dari respons AJAX (data.total_qty, data.total_diskon_item, data.total_saat_ini)
                    itemsFooter.innerHTML = `
    <tr>
        <td colspan="2" class="text-end"><strong>Total</strong></td>
        <td class="text-end"><strong>${new Intl.NumberFormat('id-ID').format(data.total_qty || 0)}</strong></td>
        <td></td>
        <td class="text-end"><strong>${formatCurrency(data.total_diskon_item || 0)}</strong></td>
        <td class="text-end"><strong>${formatCurrency(data.total_saat_ini || 0)}</strong></td>
    </tr>
`;
                } else {
                    itemsList.innerHTML = `<tr><td colspan="6" class="text-center">Tidak ada item untuk order ini.</td></tr>`;
                    itemsFooter.innerHTML = '';
                }
            } else {
                console.warn('showItems received no items or unsuccessful response:', data.message || data.error);
                itemsList.innerHTML = `<tr><td colspan="6" class="text-center">${data.message || data.error || 'Tidak ada item yang ditemukan atau terjadi kesalahan.'}</td></tr>`;
                itemsFooter.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error fetching/rendering items in showItems:', error);
            if (modalTitle) modalTitle.textContent = '⚠️ Gagal Memuat';
            itemsList.innerHTML = `<tr><td colspan="6" class="text-center">Gagal memuat data item: ${error.message}</td></tr>`;
            itemsFooter.innerHTML = '';
        })
        .finally(() => {
            loadingIndicator.style.display = 'none';
            itemsTableElement.style.display = 'table';
            modal.show();
        });
}


/**
 * Initializes theme toggle functionality.
 */
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        const html = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        html.setAttribute('data-bs-theme', savedTheme);

        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.classList.toggle('fa-moon', savedTheme === 'light');
            icon.classList.toggle('fa-sun', savedTheme === 'dark');
        }

        themeToggle.addEventListener('click', () => {
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            if (icon) {
                icon.classList.toggle('fa-moon', newTheme === 'light');
                icon.classList.toggle('fa-sun', newTheme === 'dark');
            }
        });
    }

    // --- KODE BARU UNTUK MEMASTIKAN INPUT HANYA ANGKA ---
    // Fungsi untuk memfilter input agar hanya menerima angka
    const forceNumericInput = (event) => {
        // Mengganti karakter apa pun yang bukan digit (0-9) dengan string kosong
        event.target.value = event.target.value.replace(/[^0-9]/g, '');
    };

    // Terapkan fungsi di atas ke semua input telepon di seluruh aplikasi
    document.querySelectorAll('input[name="telepon_customer"], input[name="telepon_penerima"]').forEach(input => {
        input.addEventListener('input', forceNumericInput);
    });
});