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
            console.log('Initializing Flatpickr...');
            console.log('Flatpickr available:', typeof flatpickr !== 'undefined');

            // Wait for modal to be in DOM before initializing
            // We'll initialize pickers when modal opens instead
        },

        /**
         * Initialize pickers for the modal (called when modal opens)
         */
        initModalPickers: function() {
            // Check if already initialized
            const dateInput = document.getElementById('cb-session-date');
            if (dateInput && dateInput._flatpickr) {
                console.log('Pickers already initialized, skipping...');
                return; // Already initialized
            }

            console.log('Initializing modal pickers...');
            console.log('Flatpickr available:', typeof flatpickr);
            console.log('Date input exists:', !!dateInput);
            console.log('Start time input exists:', !!document.getElementById('cb-start-time'));
            console.log('End time input exists:', !!document.getElementById('cb-end-time'));

            if (typeof flatpickr === 'undefined') {
                console.error('Flatpickr is not loaded!');
                return;
            }

            // Date picker
            const datePicker = flatpickr('#cb-session-date', {
                locale: 'es',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'j F, Y',
                altInputClass: 'cb-datepicker',
                minDate: 'today',
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                inline: false,
                onReady: function(selectedDates, dateStr, instance) {
                    console.log('Date picker ready!');
                    // Ensure altInput is visible and styled
                    if (instance.altInput) {
                        instance.altInput.style.display = 'block';
                        instance.altInput.style.width = '100%';
                    }
                    // Move calendar to modal-body and position it
                    const modalBody = document.querySelector('.cb-modal-body');
                    if (modalBody && instance.calendarContainer) {
                        modalBody.appendChild(instance.calendarContainer);
                        // Set initial position and hide it
                        instance.calendarContainer.style.position = 'absolute';
                        instance.calendarContainer.style.top = '0px';
                        instance.calendarContainer.style.left = '0px';
                        instance.calendarContainer.style.display = 'none';
                    }
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    console.log('Date picker opened!');
                    // Force position after Flatpickr's positioning
                    setTimeout(function() {
                        const altInput = instance.altInput;
                        const calendar = instance.calendarContainer;
                        if (altInput && calendar) {
                            const rect = altInput.getBoundingClientRect();
                            const modalBody = document.querySelector('.cb-modal-body');
                            const modalBodyRect = modalBody.getBoundingClientRect();
                            const top = rect.bottom - modalBodyRect.top + 4;
                            const left = rect.left - modalBodyRect.left;
                            console.log('Date picker position:', { top, left, rect, modalBodyRect });
                            calendar.style.top = top + 'px';
                            calendar.style.left = left + 'px';

                            // Hide extra nextMonthDay elements that are not needed
                            const days = calendar.querySelectorAll('.flatpickr-day');
                            const nextMonthDays = Array.from(days).filter(day => day.classList.contains('nextMonthDay'));

                            // If there are more than 6 nextMonthDay elements, hide the extras
                            if (nextMonthDays.length > 6) {
                                nextMonthDays.slice(6).forEach(day => {
                                    day.style.display = 'none';
                                });
                            }
                        }
                    }, 0);
                }
            });
            console.log('Date picker instance:', datePicker);
            console.log('Date picker element:', datePicker.element);
            console.log('Date picker altInput:', datePicker.altInput);

            // Start time picker (no altInput needed for time)
            const startTimePicker = flatpickr('#cb-start-time', {
                locale: 'es',
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 15,
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    console.log('Start time picker ready!');
                    // Move calendar to modal-body and position it
                    const modalBody = document.querySelector('.cb-modal-body');
                    if (modalBody && instance.calendarContainer) {
                        modalBody.appendChild(instance.calendarContainer);
                        // Set initial position
                        instance.calendarContainer.style.position = 'absolute';
                        instance.calendarContainer.style.top = '0px';
                        instance.calendarContainer.style.left = '0px';
                    }
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    console.log('Start time picker opened!');
                    // Force position after Flatpickr's positioning
                    setTimeout(function() {
                        const input = instance.element;
                        const calendar = instance.calendarContainer;
                        if (input && calendar) {
                            const rect = input.getBoundingClientRect();
                            const modalBody = document.querySelector('.cb-modal-body');
                            const modalBodyRect = modalBody.getBoundingClientRect();
                            const top = rect.bottom - modalBodyRect.top + 4;
                            const left = rect.left - modalBodyRect.left;
                            console.log('Start time picker position:', { top, left, rect, modalBodyRect });
                            calendar.style.top = top + 'px';
                            calendar.style.left = left + 'px';
                        }
                    }, 0);
                }
            });
            console.log('Start time picker instance:', startTimePicker);

            // End time picker (no altInput needed for time)
            const endTimePicker = flatpickr('#cb-end-time', {
                locale: 'es',
                enableTime: true,
                noCalendar: true,
                dateFormat: 'H:i',
                time_24hr: true,
                minuteIncrement: 15,
                clickOpens: true,
                allowInput: true,
                disableMobile: true,
                onReady: function(selectedDates, dateStr, instance) {
                    console.log('End time picker ready!');
                    // Move calendar to modal-body and position it
                    const modalBody = document.querySelector('.cb-modal-body');
                    if (modalBody && instance.calendarContainer) {
                        modalBody.appendChild(instance.calendarContainer);
                        // Set initial position
                        instance.calendarContainer.style.position = 'absolute';
                        instance.calendarContainer.style.top = '0px';
                        instance.calendarContainer.style.left = '0px';
                    }
                },
                onOpen: function(selectedDates, dateStr, instance) {
                    console.log('End time picker opened!');
                    // Force position after Flatpickr's positioning
                    setTimeout(function() {
                        const input = instance.element;
                        const calendar = instance.calendarContainer;
                        if (input && calendar) {
                            const rect = input.getBoundingClientRect();
                            const modalBody = document.querySelector('.cb-modal-body');
                            const modalBodyRect = modalBody.getBoundingClientRect();
                            const top = rect.bottom - modalBodyRect.top + 4;
                            const left = rect.left - modalBodyRect.left;
                            console.log('End time picker position:', { top, left, rect, modalBodyRect });
                            calendar.style.top = top + 'px';
                            calendar.style.left = left + 'px';
                        }
                    }, 0);
                }
            });
            console.log('End time picker instance:', endTimePicker);

            console.log('Pickers initialized!');
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

