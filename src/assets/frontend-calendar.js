/**
 * Class Booking - Frontend Calendar
 * Interactive calendar for session booking
 */

(function($) {
    'use strict';

    const ClassBookingCalendar = {
        // Configuration
        config: {
            classId: null,
            availableDates: [],
            selectedDate: null,
            selectedSession: null,
            quantity: 1,
            price: 0,
        },

        // Cache DOM elements
        cache: {},

        /**
         * Initialize the calendar
         */
        init: function(containerId, classId, availableDates, price) {
            this.config.classId = classId;
            this.config.availableDates = availableDates;
            this.config.price = parseFloat(price) || 0;
            
            this.cache.$container = $('#' + containerId);
            if (!this.cache.$container.length) return;

            this.cache.$calendarDays = this.cache.$container.find('.cb-calendar-days');
            this.cache.$calendarTitle = this.cache.$container.find('.cb-calendar-title');
            this.cache.$sessionsList = this.cache.$container.find('.cb-sessions-list');
            this.cache.$sessionsPlaceholder = this.cache.$container.find('.cb-sessions-placeholder');
            this.cache.$bookingForm = this.cache.$container.find('.cb-booking-form');
            this.cache.$quantityInput = this.cache.$container.find('.cb-quantity-input');
            this.cache.$summaryDetails = this.cache.$container.find('.cb-summary-details');
            this.cache.$summaryTotal = this.cache.$container.find('.cb-summary-total');
            this.cache.$submitBtn = this.cache.$container.find('.cb-submit-btn');
            this.cache.$sessionIdInput = this.cache.$container.find('input[name="session_id"]');

            this.currentMonth = new Date();
            this.currentMonth.setDate(1);

            this.bindEvents();
            this.renderCalendar();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            const self = this;

            // Calendar navigation
            this.cache.$container.on('click', '.cb-nav-prev', function() {
                self.navigateMonth(-1);
            });

            this.cache.$container.on('click', '.cb-nav-next', function() {
                self.navigateMonth(1);
            });

            // Day selection
            this.cache.$container.on('click', '.cb-calendar-day.has-sessions', function() {
                const date = $(this).data('date');
                self.selectDate(date);
            });

            // Session selection
            this.cache.$container.on('click', '.cb-session-card:not(.sold-out)', function() {
                const sessionId = $(this).data('session-id');
                const maxCapacity = $(this).data('capacity');
                self.selectSession(sessionId, maxCapacity);
            });

            // Quantity controls
            this.cache.$container.on('click', '.cb-quantity-btn.minus', function() {
                self.updateQuantity(-1);
            });

            this.cache.$container.on('click', '.cb-quantity-btn.plus', function() {
                self.updateQuantity(1);
            });

            this.cache.$quantityInput.on('change', function() {
                const val = parseInt($(this).val()) || 1;
                self.config.quantity = Math.max(1, Math.min(val, self.config.maxCapacity || 10));
                $(this).val(self.config.quantity);
                self.updateSummary();
            });
        },

        /**
         * Navigate to previous/next month
         */
        navigateMonth: function(direction) {
            this.currentMonth.setMonth(this.currentMonth.getMonth() + direction);
            this.renderCalendar();
        },

        /**
         * Render the calendar grid
         */
        renderCalendar: function() {
            const year = this.currentMonth.getFullYear();
            const month = this.currentMonth.getMonth();
            
            // Update title
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                               'July', 'August', 'September', 'October', 'November', 'December'];
            this.cache.$calendarTitle.text(monthNames[month] + ' ' + year);

            // Get first day of month and total days
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            // Adjust for Monday start (0 = Monday, 6 = Sunday)
            const startDay = firstDay === 0 ? 6 : firstDay - 1;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            let html = '';

            // Previous month days
            for (let i = startDay - 1; i >= 0; i--) {
                const day = daysInPrevMonth - i;
                html += '<div class="cb-calendar-day other-month">' + day + '</div>';
            }

            // Current month days
            for (let day = 1; day <= daysInMonth; day++) {
                const dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                const dateObj = new Date(year, month, day);
                
                let classes = ['cb-calendar-day'];
                
                if (dateObj < today) {
                    classes.push('past');
                } else if (this.hasSessionsOnDate(dateStr)) {
                    classes.push('has-sessions');
                }
                
                if (dateObj.toDateString() === today.toDateString()) {
                    classes.push('today');
                }
                
                if (this.config.selectedDate === dateStr) {
                    classes.push('selected');
                }

                html += '<div class="' + classes.join(' ') + '" data-date="' + dateStr + '">' + day + '</div>';
            }

            // Next month days to fill the grid
            const totalCells = startDay + daysInMonth;
            const remainingCells = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
            for (let day = 1; day <= remainingCells; day++) {
                html += '<div class="cb-calendar-day other-month">' + day + '</div>';
            }

            this.cache.$calendarDays.html(html);
        },

        /**
         * Check if a date has available sessions
         */
        hasSessionsOnDate: function(dateStr) {
            return this.config.availableDates.some(function(item) {
                return item.session_date === dateStr;
            });
        },

        /**
         * Select a date and load sessions
         */
        selectDate: function(date) {
            const self = this;
            this.config.selectedDate = date;
            this.config.selectedSession = null;
            this.config.quantity = 1;

            // Update calendar UI
            this.cache.$calendarDays.find('.cb-calendar-day').removeClass('selected');
            this.cache.$calendarDays.find('[data-date="' + date + '"]').addClass('selected');

            // Show loading
            this.cache.$sessionsPlaceholder.hide();
            this.cache.$sessionsList.html('<div class="cb-loading"><div class="cb-spinner"></div></div>');
            this.cache.$bookingForm.hide();

            // Fetch sessions for this date
            $.ajax({
                url: cbCalendarConfig.ajaxUrl,
                method: 'GET',
                data: {
                    action: 'cb_get_sessions_by_date',
                    class_id: this.config.classId,
                    date: date,
                    nonce: cbCalendarConfig.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        self.renderSessions(response.data);
                    } else {
                        self.cache.$sessionsList.html('<div class="cb-sessions-placeholder">No sessions available for this date.</div>');
                    }
                },
                error: function() {
                    self.cache.$sessionsList.html('<div class="cb-message error">Error loading sessions. Please try again.</div>');
                }
            });
        },

        /**
         * Render sessions list
         */
        renderSessions: function(sessions) {
            let html = '';

            sessions.forEach(function(session) {
                const isSoldOut = parseInt(session.remaining_capacity) <= 0;
                const isLow = parseInt(session.remaining_capacity) <= 3 && !isSoldOut;

                let cardClasses = ['cb-session-card'];
                if (isSoldOut) cardClasses.push('sold-out');

                let spotsClasses = ['cb-session-spots'];
                if (isLow) spotsClasses.push('low');
                if (isSoldOut) spotsClasses.push('sold-out');

                html += '<div class="' + cardClasses.join(' ') + '" data-session-id="' + session.id + '" data-capacity="' + session.remaining_capacity + '">';
                html += '  <div class="cb-session-time">' + session.start_time.substring(0, 5) + ' - ' + session.end_time.substring(0, 5) + '</div>';
                html += '  <div class="cb-session-info">';
                html += '    <span class="' + spotsClasses.join(' ') + '">';
                if (isSoldOut) {
                    html += 'Sold out';
                } else {
                    html += session.remaining_capacity + ' spots left';
                }
                html += '    </span>';
                html += '  </div>';
                html += '</div>';
            });

            this.cache.$sessionsList.html(html);
        },

        /**
         * Select a session
         */
        selectSession: function(sessionId, maxCapacity) {
            this.config.selectedSession = sessionId;
            this.config.maxCapacity = parseInt(maxCapacity) || 1;
            this.config.quantity = 1;

            // Update UI
            this.cache.$sessionsList.find('.cb-session-card').removeClass('selected');
            this.cache.$sessionsList.find('[data-session-id="' + sessionId + '"]').addClass('selected');

            // Update form
            this.cache.$sessionIdInput.val(sessionId);
            this.cache.$quantityInput.val(1).attr('max', this.config.maxCapacity);
            this.updateSummary();
            this.cache.$bookingForm.show();
        },

        /**
         * Update quantity
         */
        updateQuantity: function(delta) {
            const newQty = this.config.quantity + delta;
            if (newQty >= 1 && newQty <= this.config.maxCapacity) {
                this.config.quantity = newQty;
                this.cache.$quantityInput.val(newQty);
                this.updateSummary();
            }
        },

        /**
         * Update booking summary
         */
        updateSummary: function() {
            const total = this.config.quantity * this.config.price;
            this.cache.$summaryDetails.html(this.config.quantity + ' person(s) × <strong>' + this.formatPrice(this.config.price) + '</strong>');
            this.cache.$summaryTotal.text(this.formatPrice(total));
            this.cache.$submitBtn.prop('disabled', !this.config.selectedSession);
        },

        /**
         * Format price
         */
        formatPrice: function(price) {
            return price.toFixed(2) + ' €';
        }
    };

    // Expose to global scope
    window.ClassBookingCalendar = ClassBookingCalendar;

})(jQuery);
