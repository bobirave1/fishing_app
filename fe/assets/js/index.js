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

// Load login form
document.getElementById('loginModal').addEventListener('show.bs.modal', function () {
    fetch('fe/auth/login_form.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('loginModalBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('loginModalBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
        });
});

// Load register form
document.getElementById('registerModal').addEventListener('show.bs.modal', function () {
    fetch('fe/auth/register_form.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('registerModalBody').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('registerModalBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
        });
});

// Clear bodies when modals close
['loginModal', 'registerModal'].forEach(id => {
    document.getElementById(id).addEventListener('hidden.bs.modal', function () {
        document.getElementById(
            id === 'loginModal' ? 'loginModalBody' : 'registerModalBody'
        ).innerHTML = '<p class="text-center">Loading form...</p>';
    });
});

// Clear edit and delete modals when closed
document.getElementById('editPostModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('editPostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
});

document.getElementById('deletePostModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('deletePostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
});

// Weather widget with improved error handling
if (navigator.geolocation) {
    console.log('Geolocation supported - requesting position...');
    
    navigator.geolocation.getCurrentPosition(function(position) {
        console.log('Position obtained:', position.coords.latitude, position.coords.longitude);
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;
        
        document.getElementById('weather-info').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading weather...</p>';
        
        fetch(`be/weather/get_weather.php?lat=${lat}&lon=${lon}`)
            .then(response => {
                console.log('Weather API response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Weather data:', data);
                if (data.error) {
                    document.getElementById('weather-info').innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error}
                            <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                                <i class="fas fa-sync"></i> Retry
                            </button>
                        </div>
                    `;
                } else {
                    let fishingTip = '';
                    if (data.wind_speed < 5) fishingTip = 'Great day for fishing! Low wind speeds are ideal.';
                    else if (data.wind_speed < 10) fishingTip = 'Moderate wind, still suitable for most fishing activities.';
                    else fishingTip = 'High wind speeds may make fishing challenging or unsafe.';

                    document.getElementById('weather-info').innerHTML = `
                        <div class="row text-center">
                            <div class="col-12 mb-3">
                                <h5><i class="fas fa-map-marker-alt"></i> ${data.location}${data.country ? ', ' + data.country : ''}</h5>
                                <img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="weather icon" class="img-fluid weather-icon">
                                <p class="mb-0 fs-5">${data.description}</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-thermometer-half text-danger"></i> <strong>Temperature:</strong> ${data.temperature}°C</p>
                                <p><i class="fas fa-wind text-info"></i> <strong>Wind:</strong> ${data.wind_speed} m/s (${data.wind_direction}°)</p>
                                <p><i class="fas fa-tint text-primary"></i> <strong>Humidity:</strong> ${data.humidity}%</p>
                            </div>
                            <div class="col-md-6">
                                <p><i class="fas fa-eye text-success"></i> <strong>Visibility:</strong> ${data.visibility} km</p>
                                <p><i class="fas fa-gauge text-warning"></i> <strong>Pressure:</strong> ${data.pressure} hPa</p>
                                ${data.sea_level ? `<p><i class="fas fa-water"></i> <strong>Sea Level:</strong> ${data.sea_level} hPa</p>` : ''}
                            </div>
                            <div class="col-12 mt-3">
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-fish"></i> <strong>Fishing Tip:</strong> ${fishingTip}
                                </div>
                            </div>
                        </div>
                    `;
                }
            })
            .catch((error) => {
                console.error('Weather fetch error:', error);
                document.getElementById('weather-info').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <strong>Error loading weather</strong><br>
                        ${error.message || 'Network error'}
                        <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                            <i class="fas fa-sync"></i> Retry
                        </button>
                    </div>
                `;
            });
    }, function(error) {
        console.error('Geolocation error:', error.code, error.message);
        
        let errorMessage = '';
        switch(error.code) {
            case error.PERMISSION_DENIED:
                errorMessage = 'Location access was denied. Please enable location services in your browser settings.';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage = 'Location information is unavailable. Please check your device settings.';
                break;
            case error.TIMEOUT:
                errorMessage = 'Location request timed out. Please try again.';
                break;
            default:
                errorMessage = 'An unknown error occurred while getting your location.';
        }
        
        document.getElementById('weather-info').innerHTML = `
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> 
                <strong>Location Access Needed</strong><br>
                ${errorMessage}
                <button class="btn btn-sm btn-primary mt-2 d-block" onclick="location.reload()">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        `;
    }, {
        enableHighAccuracy: false,
        timeout: 10000,
        maximumAge: 300000 // Cache for 5 minutes
    });
} else {
    console.error('Geolocation not supported');
    document.getElementById('weather-info').innerHTML = `
        <div class="alert alert-secondary">
            <i class="fas fa-times-circle"></i> Your browser doesn't support geolocation.
            <br><small class="text-muted">Please use a modern browser like Chrome, Firefox, or Edge.</small>
        </div>
    `;
}
