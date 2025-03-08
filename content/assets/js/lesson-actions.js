/**
 * Lesson Actions JavaScript
 * =======================
 * Handles UI interactions for:
 * - Completion toggle
 * - Review toggle
 * - ChatGPT integration
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));
    
    // Initialize Toastr options
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "3000",
        "rtl": true
    };
    
    // Toggle completion status
    const completionBtn = document.getElementById('toggleCompletion');
    if (completionBtn) {
        completionBtn.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            
            fetch('/content/api/lesson-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_completion&lesson_id=${lessonId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    try {
                        // Update button state
                        const icon = this.querySelector('i.fas');
                        const buttonText = this.querySelector('.button-text');
                        
                        if (icon) {
                            if (data.completed) {
                                icon.classList.remove('fa-circle');
                                icon.classList.add('fa-check-circle');
                                this.classList.remove('btn-outline-secondary');
                                this.classList.add('btn-success');
                            } else {
                                icon.classList.remove('fa-check-circle');
                                icon.classList.add('fa-circle');
                                this.classList.remove('btn-success');
                                this.classList.add('btn-outline-secondary');
                            }
                        }

                        if (buttonText) {
                            buttonText.textContent = data.completed ? 'مكتمل' : 'غير مكتمل';
                        }
                        
                        // Show toast message
                        toastr.success(data.message);
                        
                        // Update tooltip
                        const tooltip = bootstrap.Tooltip.getInstance(this);
                        if (tooltip) {
                            this.setAttribute('data-bs-original-title', data.completed ? 'تحديد كغير مكتمل' : 'تحديد كمكتمل');
                            tooltip.dispose();
                            new bootstrap.Tooltip(this);
                        }
                    } catch (err) {
                        console.error('Error updating UI:', err);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('حدث خطأ أثناء تحديث حالة الدرس');
            });
        });
    }
    
    // Toggle review status
    const reviewBtn = document.getElementById('toggleReview');
    if (reviewBtn) {
        reviewBtn.addEventListener('click', function() {
            const lessonId = this.dataset.lessonId;
            
            fetch('/content/api/lesson-actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle_review&lesson_id=${lessonId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    try {
                        // Update button state
                        const icon = this.querySelector('i.fas');
                        const buttonText = this.querySelector('.button-text');
                        
                        if (icon) {
                            if (data.is_reviewed) {
                                icon.classList.remove('fa-bookmark-o');
                                icon.classList.add('fa-bookmark');
                                this.classList.remove('btn-outline-secondary');
                                this.classList.add('btn-info');
                            } else {
                                icon.classList.remove('fa-bookmark');
                                icon.classList.add('fa-bookmark-o');
                                this.classList.remove('btn-info');
                                this.classList.add('btn-outline-secondary');
                            }
                        }

                        if (buttonText) {
                            buttonText.textContent = data.is_reviewed ? 'في المراجعة' : 'إضافة للمراجعة';
                        }
                        
                        // Show toast message
                        toastr.success(data.message);
                        
                        // Update tooltip
                        const tooltip = bootstrap.Tooltip.getInstance(this);
                        if (tooltip) {
                            this.setAttribute('data-bs-original-title', data.is_reviewed ? 'إزالة من المراجعة' : 'إضافة للمراجعة');
                            tooltip.dispose();
                            new bootstrap.Tooltip(this);
                        }
                    } catch (err) {
                        console.error('Error updating UI:', err);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('حدث خطأ أثناء تحديث حالة المراجعة');
            });
        });
    }
}); 