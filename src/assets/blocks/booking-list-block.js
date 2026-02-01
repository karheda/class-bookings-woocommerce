(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { InspectorControls, useBlockProps } = wp.blockEditor;
    const { PanelBody, SelectControl, ColorPicker, BaseControl } = wp.components;
    const { __ } = wp.i18n;

    // Get categories from localized data
    const categories = (window.cbBlockData && window.cbBlockData.categories) || [];

    registerBlockType('class-booking/booking-list', {
        title: __('Booking List', 'class-booking'),
        description: __('Display a list of bookings by category with calendar selection.', 'class-booking'),
        category: 'widgets',
        icon: 'calendar-alt',
        keywords: [__('booking', 'class-booking'), __('calendar', 'class-booking'), __('class', 'class-booking')],
        supports: {
            html: false,
            align: ['wide', 'full'],
        },
        attributes: {
            category: {
                type: 'string',
                default: '',
            },
            primaryColor: {
                type: 'string',
                default: '#2271b1',
            },
            secondaryColor: {
                type: 'string',
                default: '#f0f0f1',
            },
            textColor: {
                type: 'string',
                default: '#1d2327',
            },
            accentColor: {
                type: 'string',
                default: '#d63638',
            },
        },

        edit: function(props) {
            const { attributes, setAttributes } = props;
            const { category, primaryColor, secondaryColor, textColor, accentColor } = attributes;
            const blockProps = useBlockProps({
                className: 'cb-booking-list-editor',
            });

            // Category options with empty default
            const categoryOptions = [
                { label: __('— Select Category —', 'class-booking'), value: '' },
                ...categories
            ];

            // Find selected category name
            const selectedCategory = categories.find(cat => cat.value === category);
            const categoryName = selectedCategory ? selectedCategory.label : '';

            return el(Fragment, {},
                // Inspector Controls (Sidebar)
                el(InspectorControls, {},
                    // Category Selection
                    el(PanelBody, { title: __('Category', 'class-booking'), initialOpen: true },
                        el(SelectControl, {
                            label: __('Select Category', 'class-booking'),
                            value: category,
                            options: categoryOptions,
                            onChange: function(value) {
                                setAttributes({ category: value });
                            },
                            help: __('Choose which booking category to display.', 'class-booking'),
                        })
                    ),
                    // Color Settings
                    el(PanelBody, { title: __('Colors', 'class-booking'), initialOpen: false },
                        el(BaseControl, { label: __('Primary Color', 'class-booking'), help: __('Used for buttons and active states.', 'class-booking') },
                            el(ColorPicker, {
                                color: primaryColor,
                                onChangeComplete: function(color) {
                                    setAttributes({ primaryColor: color.hex });
                                },
                                disableAlpha: true,
                            })
                        ),
                        el(BaseControl, { label: __('Secondary Color', 'class-booking'), help: __('Used for backgrounds and borders.', 'class-booking') },
                            el(ColorPicker, {
                                color: secondaryColor,
                                onChangeComplete: function(color) {
                                    setAttributes({ secondaryColor: color.hex });
                                },
                                disableAlpha: true,
                            })
                        ),
                        el(BaseControl, { label: __('Text Color', 'class-booking'), help: __('Main text color.', 'class-booking') },
                            el(ColorPicker, {
                                color: textColor,
                                onChangeComplete: function(color) {
                                    setAttributes({ textColor: color.hex });
                                },
                                disableAlpha: true,
                            })
                        ),
                        el(BaseControl, { label: __('Accent Color', 'class-booking'), help: __('Used for highlights and prices.', 'class-booking') },
                            el(ColorPicker, {
                                color: accentColor,
                                onChangeComplete: function(color) {
                                    setAttributes({ accentColor: color.hex });
                                },
                                disableAlpha: true,
                            })
                        )
                    )
                ),
                // Block Preview
                el('div', blockProps,
                    el('div', {
                        className: 'cb-block-preview',
                        style: {
                            '--cb-primary-color': primaryColor,
                            '--cb-secondary-color': secondaryColor,
                            '--cb-text-color': textColor,
                            '--cb-accent-color': accentColor,
                        }
                    },
                        el('div', { className: 'cb-block-header' },
                            el('span', { className: 'cb-block-icon dashicons dashicons-calendar-alt' }),
                            el('span', { className: 'cb-block-title' }, __('Booking List', 'class-booking'))
                        ),
                        category
                            ? el('div', { className: 'cb-block-info' },
                                el('span', { className: 'cb-block-category' },
                                    __('Category:', 'class-booking') + ' ',
                                    el('strong', {}, categoryName)
                                ),
                                el('div', { className: 'cb-block-color-preview' },
                                    el('span', { className: 'cb-color-swatch', style: { backgroundColor: primaryColor }, title: __('Primary', 'class-booking') }),
                                    el('span', { className: 'cb-color-swatch', style: { backgroundColor: secondaryColor }, title: __('Secondary', 'class-booking') }),
                                    el('span', { className: 'cb-color-swatch', style: { backgroundColor: textColor }, title: __('Text', 'class-booking') }),
                                    el('span', { className: 'cb-color-swatch', style: { backgroundColor: accentColor }, title: __('Accent', 'class-booking') })
                                )
                            )
                            : el('p', { className: 'cb-block-placeholder' },
                                __('Please select a category in the block settings.', 'class-booking')
                            )
                    )
                )
            );
        },

        save: function() {
            // Dynamic block - rendered on server
            return null;
        },
    });
})(window.wp);

