/**
 * Class Booking - Admin Sessions Management
 * Handles CRUD operations for class sessions via AJAX
 */

(function($) {
    'use strict';

    const ClassBookingSessions = {
        // Configuration
        config: {
            apiNamespace: 'class-booking/v1',
            apiBase: '/wp-json/class-booking/v1/sessions',
        },

        // Cache DOM elements
        cache: {},

        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
            this.initDateTimePickers();
        },

        /**
         * Initialize Flatpickr date and time pickers
         */
        initDateTimePickers: function() {
            // Pickers are initialized when modal opens
        },

        /**
         * Helper: Position calendar inside modal
         */
        positionCalendar: function(instance, inputElement) {
            const calendar = instance.calendarContainer;
            const modalBody = document.querySelector('.cb-modal-body');

            if (!calendar || !modalBody || !inputElement) return;

            // Use setTimeout to override Flatpickr's automatic positioning
            setTimeout(function() {
                const rect = inputElement.getBoundingClientRect();
                const modalBodyRect = modalBody.getBoundingClientRect();

                calendar.style.top = (rect.bottom - modalBodyRect.top + 4) + 'px';
                calendar.style.left = (rect.left - modalBodyRect.left) + 'px';
            }, 0);
        },

        /**
         * Helper: Setup calendar in modal
         */
        setupCalendarInModal: function(instance) {
            const modalBody = document.querySelector('.cb-modal-body');
            if (!modalBody || !instance.calendarContainer) return;

            modalBody.appendChild(instance.calendarContainer);
            instance.calendarContainer.style.position = 'absolute';
            instance.calendarContainer.style.top = '0px';
            instance.calendarContainer.style.left = '0px';
            instance.calendarContainer.style.display = 'none';
        },

        /**
         * Helper: Hide extra days from next month
         */
        hideExtraDays: function(calendar) {
            const days = calendar.querySelectorAll('.flatpickr-day');
            const nextMonthDays = Array.from(days).filter(day =>
                day.classList.contains('nextMonthDay')
            );

            // Keep only first 6 days of next month (to complete last week)
            if (nextMonthDays.length > 6) {
                nextMonthDays.slice(7).forEach(day => {
                    day.style.display = 'none';
                });
            }
        },

        /**
         * Initialize pickers for the modal (called when modal opens)
         */
        initModalPickers: function() {
            // Check if already initialized
            const dateInput = document.getElementById('cb-session-date');
            if (dateInput && dateInput._flatpickr) {
                return; // Already initialized
            }

            if (typeof flatpickr === 'undefined') {
                console.error('Flatpickr library not loaded');
                return;
            }

            const self = this;

            // Date picker
            const datePicker = flatpickr('#cb-session-date', {
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'F j, Y',
                altInputClass: 'cb-datepicker',
                minDate: 'today',
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    if (instance.altInput) {
                        instance.altInput.style.display = 'block';
                        instance.altInput.style.width = '100%';
                        instance.altInput.setAttribute('aria-label', 'Select session date');
                    }
                    self.setupCalendarInModal(instance);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    self.positionCalendar(instance, instance.altInput);
                    self.hideExtraDays(instance.calendarContainer);
                }
            });

            // Start time picker
            const startTimePicker = flatpickr('#cb-start-time', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 15,
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    instance.element.setAttribute('aria-label', 'Select start time');
                    self.setupCalendarInModal(instance);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    self.positionCalendar(instance, instance.element);
                }
            });

            // End time picker
            const endTimePicker = flatpickr('#cb-end-time', {
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 15,
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    instance.element.setAttribute('aria-label', 'Select end time');
                    self.setupCalendarInModal(instance);
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    self.positionCalendar(instance, instance.element);
                }
            });
        },

        /**
         * Get element (always fresh lookup to avoid caching issues)
         */
        getElement: function(key, selector) {
            return $(selector);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Open modal for new session
            $(document).on('click', '#cb-add-session-btn', function(e) {
                e.preventDefault();
                self.openModal('add');
            });

            // Open modal for editing session
            $(document).on('click', '.cb-edit-session', function(e) {
                e.preventDefault();
                const sessionId = $(this).data('session-id');
                self.openModal('edit', sessionId);
            });

            // Toggle session status
            $(document).on('click', '.cb-toggle-status', function(e) {
                e.preventDefault();
                const sessionId = $(this).data('session-id');
                const currentStatus = $(this).data('current-status');
                const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                self.toggleStatus(sessionId, newStatus);
            });

            // Delete session
            $(document).on('click', '.cb-delete-session', function(e) {
                e.preventDefault();
                const sessionId = $(this).data('session-id');
                self.deleteSession(sessionId);
            });

            // Close modal
            $(document).on('click', '.cb-modal-close, .cb-modal-cancel, .cb-modal-overlay', function(e) {
                e.preventDefault();
                self.closeModal();
            });

            // Save session button
            $(document).on('click', '#cb-save-session', function(e) {
                e.preventDefault();
                self.saveSession();
            });

            // Close modal on ESC key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#cb-session-modal').is(':visible')) {
                    self.closeModal();
                }
            });
        },

        /**
         * Open modal for add or edit
         */
        openModal: function(mode, sessionId = null) {
            const self = this;
            const $modal = this.getElement('$modal', '#cb-session-modal');
            const $modalTitle = this.getElement('$modalTitle', '#cb-modal-title');
            const $sessionId = this.getElement('$sessionId', '#cb-session-id');
            const $status = this.getElement('$status', '#cb-status');
            const $modalError = this.getElement('$modalError', '#cb-modal-error');

            // Show modal first
            $modal.fadeIn(200);
            $modalError.hide();

            // Initialize Flatpickr after modal is visible
            setTimeout(function() {
                self.initModalPickers();

                if (mode === 'add') {
                    $modalTitle.text(classBookingAdmin.i18n.addSession);
                    // Clear all form fields
                    self.clearForm();
                    $sessionId.val('');
                    $status.val('active');
                } else if (mode === 'edit' && sessionId) {
                    $modalTitle.text(classBookingAdmin.i18n.editSession);
                    self.loadSession(sessionId);
                }
            }, 100);
        },

        /**
         * Close modal
         */
        closeModal: function() {
            const $modal = this.getElement('$modal', '#cb-session-modal');
            const $modalError = this.getElement('$modalError', '#cb-modal-error');

            $modal.fadeOut(200);
            this.clearForm();
            $modalError.hide();
        },

        /**
         * Clear all form fields
         */
        clearForm: function() {
            // Clear Flatpickr date picker
            const dateElement = document.getElementById('cb-session-date');
            if (dateElement && dateElement._flatpickr) {
                dateElement._flatpickr.clear();
            }

            // Clear Flatpickr start time picker
            const startTimeElement = document.getElementById('cb-start-time');
            if (startTimeElement && startTimeElement._flatpickr) {
                startTimeElement._flatpickr.clear();
            }

            // Clear Flatpickr end time picker
            const endTimeElement = document.getElementById('cb-end-time');
            if (endTimeElement && endTimeElement._flatpickr) {
                endTimeElement._flatpickr.clear();
            }

            // Clear other fields
            this.getElement('$capacity', '#cb-capacity').val('');
            this.getElement('$status', '#cb-status').val('active');
        },

        /**
         * Load session data for editing
         */
        loadSession: function(sessionId) {
            const self = this;

            $.ajax({
                url: this.config.apiBase + '/' + sessionId,
                method: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', classBookingAdmin.nonce);
                },
                success: function(session) {
                    self.getElement('$sessionId', '#cb-session-id').val(session.id);

                    // Set date using Flatpickr API
                    const dateElement = document.getElementById('cb-session-date');
                    if (dateElement && dateElement._flatpickr) {
                        dateElement._flatpickr.setDate(session.session_date, true);
                    }

                    // Set start time using Flatpickr API
                    const startTimeElement = document.getElementById('cb-start-time');
                    if (startTimeElement && startTimeElement._flatpickr) {
                        startTimeElement._flatpickr.setDate(session.start_time, true);
                    }

                    // Set end time using Flatpickr API
                    const endTimeElement = document.getElementById('cb-end-time');
                    if (endTimeElement && endTimeElement._flatpickr) {
                        endTimeElement._flatpickr.setDate(session.end_time, true);
                    }

                    self.getElement('$capacity', '#cb-capacity').val(session.capacity);
                    self.getElement('$status', '#cb-status').val(session.status);
                },
                error: function(xhr) {
                    self.showError(classBookingAdmin.i18n.loadError);
                }
            });
        },

        /**
         * Save session (create or update)
         */
        saveSession: function() {
            const self = this;
            const sessionId = this.getElement('$sessionId', '#cb-session-id').val();
            const isEdit = sessionId !== '';

            const data = {
                post_id: this.getElement('$postId', '#cb-post-id').val(),
                session_date: this.getElement('$sessionDate', '#cb-session-date').val(),
                start_time: this.getElement('$startTime', '#cb-start-time').val(),
                end_time: this.getElement('$endTime', '#cb-end-time').val(),
                capacity: this.getElement('$capacity', '#cb-capacity').val(),
                status: this.getElement('$status', '#cb-status').val(),
            };

            const url = isEdit
                ? this.config.apiBase + '/' + sessionId
                : this.config.apiBase;

            const method = isEdit ? 'PUT' : 'POST';

            this.setLoading(true);

            $.ajax({
                url: url,
                method: method,
                data: data,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', classBookingAdmin.nonce);
                },
                success: function(response) {
                    self.setLoading(false);
                    self.closeModal();
                    self.showNotice(response.message || classBookingAdmin.i18n.saveSuccess, 'success');
                    self.reloadSessions();
                },
                error: function(xhr) {
                    self.setLoading(false);
                    const message = xhr.responseJSON?.message || classBookingAdmin.i18n.saveError;
                    self.showError(message);
                }
            });
        },

        /**
         * Toggle session status
         */
        toggleStatus: function(sessionId, newStatus) {
            const self = this;

            if (!confirm(classBookingAdmin.i18n.confirmToggle)) {
                return;
            }

            $.ajax({
                url: this.config.apiBase + '/' + sessionId + '/status',
                method: 'PATCH',
                data: { status: newStatus },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', classBookingAdmin.nonce);
                },
                success: function(response) {
                    self.showNotice(response.message || classBookingAdmin.i18n.statusSuccess, 'success');
                    self.reloadSessions();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || classBookingAdmin.i18n.statusError;
                    self.showNotice(message, 'error');
                }
            });
        },

        /**
         * Delete session
         */
        deleteSession: function(sessionId) {
            const self = this;

            if (!confirm(classBookingAdmin.i18n.confirmDelete)) {
                return;
            }

            $.ajax({
                url: this.config.apiBase + '/' + sessionId,
                method: 'DELETE',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', classBookingAdmin.nonce);
                },
                success: function(response) {
                    self.showNotice(response.message || classBookingAdmin.i18n.deleteSuccess, 'success');
                    self.reloadSessions();
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || classBookingAdmin.i18n.deleteError;
                    self.showNotice(message, 'error');
                }
            });
        },

        /**
         * Reload sessions list
         */
        reloadSessions: function() {
            // Reload the page to refresh the sessions list
            // In a more advanced implementation, we could update the DOM directly
            window.location.reload();
        },

        /**
         * Show error in modal
         */
        showError: function(message) {
            const $modalError = this.getElement('$modalError', '#cb-modal-error');
            $modalError.html('<p>' + message + '</p>').fadeIn();
        },

        /**
         * Show WordPress admin notice
         */
        showNotice: function(message, type) {
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');

            $('.wrap h1').first().after($notice);

            // Auto-dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        /**
         * Set loading state
         */
        setLoading: function(isLoading) {
            const $saveBtn = this.getElement('$saveBtn', '#cb-save-session');
            const $saveBtnText = this.getElement('$saveBtnText', '#cb-save-session .cb-btn-text');
            const $saveBtnSpinner = this.getElement('$saveBtnSpinner', '#cb-save-session .spinner');

            if (isLoading) {
                $saveBtn.prop('disabled', true);
                $saveBtnText.hide();
                $saveBtnSpinner.show();
            } else {
                $saveBtn.prop('disabled', false);
                $saveBtnText.show();
                $saveBtnSpinner.hide();
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ClassBookingSessions.init();
    });

})(jQuery);

