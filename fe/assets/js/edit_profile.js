// Edit Profile Page JavaScript

// Character counter for bio
const bioTextarea = document.getElementById('editBio');
const bioCount = document.getElementById('bioCount');

function updateBioCount() {
    const length = bioTextarea.value.length;
    bioCount.textContent = length;
    if (length > 500) {
        bioCount.classList.add('text-danger');
        bioTextarea.value = bioTextarea.value.substring(0, 500);
    } else {
        bioCount.classList.remove('text-danger');
    }
}

bioTextarea.addEventListener('input', updateBioCount);
updateBioCount();

// Avatar preview
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        // Check file size
        if (file.size > 5 * 1024 * 1024) {
            showAppNotice('File is too large! Maximum size is 5MB.', 'warning');
            event.target.value = '';
            return;
        }

        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            showAppNotice('Invalid file type! Please use JPG, PNG, GIF, or WebP.', 'warning');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Form submission
document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    const formData = new FormData(this);
    formData.append('csrf_token', document.body.dataset.csrfToken);

    try {
        const response = await fetch(resolvePath('be/users/edit_profile.php'), {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        const messageDiv = document.getElementById('profileMessage');

        if (data.success) {
            messageDiv.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <strong>Success!</strong> Your profile has been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = resolvePath('index.php');
            }, 2000);
        } else {
            messageDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> ${data.error || 'Failed to update profile'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }

        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

    } catch (error) {
        debugLog('error', 'Profile update failed', { error: getErrorMessage(error, 'Network error') });
        document.getElementById('profileMessage').innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error!</strong> Network error. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});
