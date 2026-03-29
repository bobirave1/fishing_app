// Index Page JavaScript

// Load edit post form
function loadEditPost(postId) {
    fetch('fe/posts/edit_form.php?id=' + postId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('editPostBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('editPostBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
        });
}

// Submit edit form
function submitEditForm() {
    const form = document.getElementById('editPostForm');
    if (!form) {
        alert('Form not found');
        return;
    }
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);

    fetch('be/posts/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editPostModal'));
            if (modal) modal.hide();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + (data.message || data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the post.');
    });
}

// Load delete confirmation
function loadDeletePost(postId) {
    fetch('fe/posts/delete_confirm.php?id=' + postId)
        .then(response => response.text())
        .then(html => {
            document.getElementById('deletePostBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('deletePostBody').innerHTML = '<p class="text-danger">Error loading confirmation.</p>';
        });
}

// Confirm delete
function confirmDeletePost() {
    const form = document.getElementById('deletePostForm');
    const formData = new FormData(form);

    fetch('be/posts/delete.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and reload
            bootstrap.Modal.getInstance(document.getElementById('deletePostModal')).hide();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the post.');
    });
}



// Clear edit and delete modals when closed
document.getElementById('editPostModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('editPostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
});

document.getElementById('deletePostModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('deletePostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
});

// Weather widget with improved error handling
const weatherInfoEl = document.getElementById('weather-info');
const lang = document.documentElement.lang && document.documentElement.lang.toLowerCase().startsWith('bg') ? 'bg' : 'en';
const isBg = lang === 'bg';
const ui = isBg ? {
    temperature: 'Температура',
    wind: 'Вятър',
    humidity: 'Влажност',
    visibility: 'Видимост',
    pressure: 'Налягане',
    seaLevel: 'Ниво на морето',
    fishingTip: 'Съвет за риболов',
    greatTip: 'Отличен ден за риболов! Ниски скорости на вятъра са идеални.',
    moderateTip: 'Умерен вятър - все още подходящ за повечето видове риболов.',
    highTip: 'Силен вятър може да затрудни (или да направи по-рисков) риболова.',
    errorTitle: 'Грешка при зареждане на времето',
    retry: 'Опритай пак',
    locationDenied: 'Достъпът до локация беше отказан. Включете разрешение в настройките на браузъра.',
    positionUnavailable: 'Информацията за местоположението не е налична. Проверете настройките на устройството.',
    timeout: 'Заявката за местоположение изтече. Опитайте отново.',
    unknownLocation: 'Възникна неизвестна грешка при получаване на местоположение.',
    locationAccessNeeded: 'Достъп до местоположението',
    browserNoGeo: 'Вашият браузър не поддържа геолокация.',
    browserHint: 'Моля, използвайте модерен браузър като Chrome, Firefox или Edge.',
} : {
    temperature: 'Temperature',
    wind: 'Wind',
    humidity: 'Humidity',
    visibility: 'Visibility',
    pressure: 'Pressure',
    seaLevel: 'Sea Level',
    fishingTip: 'Fishing Tip',
    greatTip: 'Great day for fishing! Low wind speeds are ideal.',
    moderateTip: 'Moderate wind, still suitable for most fishing activities.',
    highTip: 'High wind speeds may make fishing challenging or unsafe.',
    errorTitle: 'Error loading weather',
    retry: 'Retry',
    locationDenied: 'Location access was denied. Please enable location services in your browser settings.',
    positionUnavailable: 'Location information is unavailable. Please check your device settings.',
    timeout: 'Location request timed out. Please try again.',
    unknownLocation: 'An unknown error occurred while getting your location.',
    locationAccessNeeded: 'Location Access Needed',
    browserNoGeo: "Your browser doesn't support geolocation.",
    browserHint: 'Please use a modern browser like Chrome, Firefox, or Edge.',
};

if (weatherInfoEl && navigator.geolocation) {
    console.log('Geolocation supported - requesting position...');
    
    navigator.geolocation.getCurrentPosition(function(position) {
        console.log('Position obtained:', position.coords.latitude, position.coords.longitude);
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        
        weatherInfoEl.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading weather...</p>';
        
        fetch(`be/weather/get_weather.php?lat=${lat}&lon=${lon}&lang=${encodeURIComponent(lang)}`)
            .then(response => {
                console.log('Weather API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Weather data:', data);
                if (data.error) {
                    weatherInfoEl.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                            <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                                <i class="fas fa-sync"></i> ${ui.retry}
                            </button>
                        </div>
                    `;
                } else {
                    let fishingTip = '';
                    if (data.wind_speed < 5) fishingTip = ui.greatTip;
                    else if (data.wind_speed < 10) fishingTip = ui.moderateTip;
                    else fishingTip = ui.highTip;

                    weatherInfoEl.innerHTML = `
                        <div class="row text-center">
                            <div class="col-12 mb-3">
                                <h5><i class="fas fa-map-marker-alt"></i> ${data.location}${data.country ? ', ' + data.country : ''}</h5>
                                <img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="weather icon" class="img-fluid weather-icon">
                                <p class="mb-0 fs-5">${data.description}</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-thermometer-half text-danger"></i> <strong>${ui.temperature}:</strong> ${data.temperature}°C</p>
                                <p><i class="fas fa-wind text-info"></i> <strong>${ui.wind}:</strong> ${data.wind_speed} m/s (${data.wind_direction}°)</p>
                                <p><i class="fas fa-tint text-primary"></i> <strong>${ui.humidity}:</strong> ${data.humidity}%</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-eye text-success"></i> <strong>${ui.visibility}:</strong> ${data.visibility} km</p>
                                <p><i class="fas fa-gauge text-warning"></i> <strong>${ui.pressure}:</strong> ${data.pressure} hPa</p>
                                ${data.sea_level ? `<p><i class="fas fa-water"></i> <strong>${ui.seaLevel}:</strong> ${data.sea_level} hPa</p>` : ''}
                            </div>
                            <div class="col-12 mt-3">
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-fish"></i> <strong>${ui.fishingTip}:</strong> ${fishingTip}
                                </div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch((error) => {
                console.error('Weather fetch error:', error);
                weatherInfoEl.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <strong>${ui.errorTitle}</strong><br>
                        ${error.message || 'Network error'}
                        <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                            <i class="fas fa-sync"></i> ${ui.retry}
                        </button>
                    </div>
                `;
            });
    }, function(error) {
        console.error('Geolocation error:', error.code, error.message);
        
        let errorMessage = '';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = ui.locationDenied;
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = ui.positionUnavailable;
                break;
            case error.TIMEOUT:
                errorMessage = ui.timeout;
                break;
            default:
                errorMessage = ui.unknownLocation;
        }
        
        weatherInfoEl.innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> 
                <strong>${ui.locationAccessNeeded}</strong><br>
                ${errorMessage}
                <button class="btn btn-sm btn-primary mt-2 d-block" onclick="location.reload()">
                    <i class="fas fa-redo"></i> ${ui.retry}
                </button>
            </div>
        `;
    }, {
        enableHighAccuracy: false,
        timeout: 10000,
        maximumAge: 300000 // Cache for 5 minutes
    });
} else if (weatherInfoEl) {
    console.error('Geolocation not supported');
    weatherInfoEl.innerHTML = `
        <div class="alert alert-secondary">
            <i class="fas fa-times-circle"></i> ${ui.browserNoGeo}
            <br><small class="text-muted">${ui.browserHint}</small>
        </div>
    `;
}

// ==================== PHOTO LIGHTBOX ====================

function escapeHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(String(str)));
    return d.innerHTML;
}

function setTextWithNewlines(el, text) {
    el.innerHTML = '';
    String(text).split('\n').forEach(function(line, i) {
        if (i > 0) el.appendChild(document.createElement('br'));
        el.appendChild(document.createTextNode(line));
    });
}

const LIGHTBOX_ANIMATION_MS = 220;
let lightboxCloseTimer = null;

function openPhotoLightbox(img) {
    const lb = document.getElementById('photoLightbox');
    const postId = img.dataset.postId;

    if (lightboxCloseTimer) {
        clearTimeout(lightboxCloseTimer);
        lightboxCloseTimer = null;
    }
    lb.classList.remove('closing');

    document.getElementById('lightboxImage').src = img.src;
    document.getElementById('lightboxAvatar').src = img.dataset.avatar || '';

    const usernameLink = document.getElementById('lightboxUsernameLink');
    usernameLink.textContent = img.dataset.username || '';
    usernameLink.href = 'be/users/profile.php?id=' + (img.dataset.userId || '');

    const isoDate = img.dataset.isoDate;
    document.getElementById('lightboxTimestamp').textContent = isoDate ? formatDate(isoDate) : '';

    const titleEl = document.getElementById('lightboxTitle');
    if (img.dataset.title) {
        titleEl.textContent = img.dataset.title;
        titleEl.style.display = '';
    } else {
        titleEl.textContent = '';
        titleEl.style.display = 'none';
    }
    setTextWithNewlines(document.getElementById('lightboxContent'), img.dataset.content || '');

    const likeBtn = document.getElementById('lightboxLikeBtn');
    const isLiked = img.dataset.userLiked === '1';
    likeBtn.dataset.postId = postId;
    likeBtn.className = 'action-btn' + (isLiked ? ' liked' : '');
    likeBtn.innerHTML = '<i class="' + (isLiked ? 'fas' : 'far') + ' fa-heart"></i> <span>' + (img.dataset.likeCount || 0) + '</span>';

    document.getElementById('lightboxCommentCnt').textContent = img.dataset.commentCount || 0;

    lb.dataset.postId = postId;
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';

    const commentInput = document.getElementById('lightboxCommentInput');
    if (commentInput) commentInput.value = '';

    loadLightboxComments(postId);
}

function closeLightbox() {
    const lb = document.getElementById('photoLightbox');
    if (!lb.classList.contains('active') || lb.classList.contains('closing')) {
        return;
    }

    lb.classList.add('closing');

    lightboxCloseTimer = setTimeout(function() {
        lb.classList.remove('closing');
        lb.classList.remove('active');
        lightboxCloseTimer = null;
    }, LIGHTBOX_ANIMATION_MS);

    document.body.style.overflow = '';
}

document.getElementById('photoLightbox').addEventListener('click', function(e) {
    if (e.target === this) closeLightbox();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

function loadLightboxComments(postId) {
    const container = document.getElementById('lightboxComments');
    container.innerHTML = '<p class="text-center text-muted small py-2"><i class="fas fa-spinner fa-spin"></i></p>';

    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('action', 'get');

    fetch('be/posts/comment.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.success || !data.comments.length) {
                container.innerHTML = '<p class="text-center text-muted small py-3">No comments yet.</p>';
                return;
            }
            container.innerHTML = data.comments.map(function(c) {
                const av = (typeof getAvatarUrl === 'function') ? getAvatarUrl(c.avatar_url) : (c.avatar_url || 'fe/assets/img/avatars/default.png');
                return '<div class="d-flex gap-2 mb-3">' +
                    '<img src="' + av + '" class="rounded-circle flex-shrink-0" width="32" height="32" style="object-fit:cover;">' +
                    '<div>' +
                        '<div style="background:var(--surface-2);border-radius:8px;padding:6px 10px;">' +
                            '<strong style="color:var(--text-primary);font-size:0.8rem;">' + escapeHtml(c.username) + '</strong>' +
                            '<p style="margin:3px 0 0;color:var(--text-primary);font-size:0.85rem;">' + escapeHtml(c.content) + '</p>' +
                        '</div>' +
                        '<small style="color:var(--text-muted);margin-left:4px;">' + escapeHtml(c.created_at) + '</small>' +
                    '</div>' +
                '</div>';
            }).join('');
        })
        .catch(function() {
            container.innerHTML = '<p class="text-danger text-center small py-2">Failed to load comments.</p>';
        });
}

function addLightboxComment() {
    const lb = document.getElementById('photoLightbox');
    const postId = lb.dataset.postId;
    const input = document.getElementById('lightboxCommentInput');
    const content = input.value.trim();
    if (!content) return;

    const fd = new FormData();
    fd.append('post_id', postId);
    fd.append('content', content);
    fd.append('action', 'add');
    fd.append('csrf_token', getCsrfToken());

    fetch('be/posts/comment.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                input.value = '';
                loadLightboxComments(postId);
                const lbCnt = document.getElementById('lightboxCommentCnt');
                if (lbCnt) lbCnt.textContent = parseInt(lbCnt.textContent || '0') + 1;
                const feedCnt = document.getElementById('comment-count-' + postId);
                if (feedCnt) feedCnt.textContent = parseInt(feedCnt.textContent || '0') + 1;
            } else {
                alert('Error: ' + (data.error || 'Failed to add comment'));
            }
        });
}

// ==================== CREATE POST FILE PREVIEW ====================
document.addEventListener('DOMContentLoaded', function() {
    const mediaInput = document.getElementById('postMediaInput');
    const preview = document.getElementById('postMediaPreview');
    const fileNameEl = document.getElementById('postMediaFileName');
    if (!mediaInput || !preview || !fileNameEl) return;

    const previewObjectUrls = [];
    const defaultFileName = fileNameEl.dataset.noFile || fileNameEl.textContent;
    const selectedFileLabel = fileNameEl.dataset.selectedFile || 'Selected file';
    const filesSelectedLabel = fileNameEl.dataset.filesSelected || 'files selected';

    mediaInput.addEventListener('change', function() {
        while (previewObjectUrls.length) {
            URL.revokeObjectURL(previewObjectUrls.pop());
        }

        const files = Array.from(mediaInput.files || []);
        if (files.length === 0) {
            fileNameEl.textContent = defaultFileName;
            preview.classList.remove('show');
            preview.innerHTML = '';
            return;
        }

        if (files.length === 1) {
            fileNameEl.textContent = selectedFileLabel + ': ' + files[0].name;
        } else {
            fileNameEl.textContent = files.length + ' ' + filesSelectedLabel;
        }

        const previewParts = files.map(function(file) {
            if (file.type.startsWith('image/')) {
                const objectUrl = URL.createObjectURL(file);
                previewObjectUrls.push(objectUrl);
                return '<img src="' + objectUrl + '" alt="Selected image preview">';
            }

            return '<div class="file-name"><i class="fas fa-file me-1"></i>' + escapeHtml(file.name) + '</div>';
        });

        preview.innerHTML = previewParts.join('');
        preview.classList.add('show');
    });
});
